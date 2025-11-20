<?php

namespace App\Services;

use App\Models\Tenant\EcommerceConfiguration;
use App\Models\Tenant\Product;
use App\Services\EcommerceLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-Powered E-commerce Service
 * Uses OpenAI to handle all customer interactions with local products database
 */
class AiEcommerceService
{
    protected $tenantId;
    protected $config;

    public function __construct($tenantId = null)
    {
        $this->tenantId = $tenantId ?? tenant_id();
        $this->config = EcommerceConfiguration::where('tenant_id', $this->tenantId)->first();
    }

    /**
     * Process customer message with AI
     */
    public function processMessage(string $message, $contact): array
    {
        EcommerceLogger::info('🤖 AI-SERVICE: Starting message processing', [
            'tenant_id' => $this->tenantId,
            'message' => $message,
            'contact_phone' => $contact->phone ?? 'unknown'
        ]);

        try {
            if (!$this->isAiConfigured()) {
                EcommerceLogger::error('🤖 AI-CONFIG: AI not properly configured', [
                    'tenant_id' => $this->tenantId,
                    'config_exists' => $this->config ? 'yes' : 'no',
                    'ai_mode' => $this->config->ai_powered_mode ?? 'unknown',
                    'api_key_exists' => !empty($this->config->openai_api_key ?? '')
                ]);
                return [
                    'handled' => true, // Return true to prevent fallbacks
                    'response' => 'AI is not properly configured. Please set up your OpenAI API key in the e-commerce settings.'
                ];
            }

            EcommerceLogger::info('🤖 AI-CONFIG: AI configuration validated', [
                'tenant_id' => $this->tenantId,
                'model' => $this->config->openai_model,
                'temperature' => $this->config->ai_temperature,
                'max_tokens' => $this->config->ai_max_tokens,
                'direct_sheets' => $this->config->direct_sheets_integration
            ]);

            // Get current product data from local database
            EcommerceLogger::info('🤖 AI-DATABASE: Fetching products from local database', [
                'tenant_id' => $this->tenantId
            ]);

            $productData = $this->getProductDataFromDatabase();

            EcommerceLogger::info('🤖 AI-DATABASE: Products fetched', [
                'tenant_id' => $this->tenantId,
                'products_count' => count($productData),
                'products_preview' => array_slice($productData, 0, 3)
            ]);

            if (empty($productData)) {
                EcommerceLogger::error('🤖 AI-DATABASE: No products available', [
                    'tenant_id' => $this->tenantId
                ]);
                return [
                    'handled' => false,
                    'response' => 'No products available'
                ];
            }

            // Create AI context
            EcommerceLogger::info('🤖 AI-PROMPT: Building AI prompt', [
                'tenant_id' => $this->tenantId,
                'message' => $message
            ]);

            $systemPrompt = $this->buildSystemPrompt($productData, $contact);

            EcommerceLogger::info('🤖 AI-PROMPT: Prompt built', [
                'tenant_id' => $this->tenantId,
                'prompt_length' => strlen($systemPrompt),
                'prompt_preview' => substr($systemPrompt, 0, 200) . '...'
            ]);

            // Get AI response
            EcommerceLogger::info('🤖 AI-OPENAI: Calling OpenAI API', [
                'tenant_id' => $this->tenantId,
                'model' => $this->config->openai_model,
                'temperature' => $this->config->ai_temperature,
                'max_tokens' => $this->config->ai_max_tokens
            ]);

            $aiResponse = $this->callOpenAI($systemPrompt, $message);

            EcommerceLogger::info('🤖 AI-OPENAI: OpenAI response received', [
                'tenant_id' => $this->tenantId,
                'response_received' => !empty($aiResponse),
                'response_length' => strlen($aiResponse ?? ''),
                'response_preview' => substr($aiResponse ?? '', 0, 200) . '...',
                'full_response' => $aiResponse // Log full response to debug format
            ]);

            if (!$aiResponse) {
                EcommerceLogger::error('🤖 AI-OPENAI: No response from OpenAI', [
                    'tenant_id' => $this->tenantId
                ]);
                return [
                    'handled' => false,
                    'response' => 'AI service unavailable'
                ];
            }

            // Parse AI response for actions
            EcommerceLogger::info('🤖 AI-PARSE: Parsing AI response', [
                'tenant_id' => $this->tenantId
            ]);

            $parsedResponse = $this->parseAiResponse($aiResponse);

            EcommerceLogger::info('🤖 AI-PARSE: AI response parsed', [
                'tenant_id' => $this->tenantId,
                'type' => $parsedResponse['type'] ?? 'unknown',
                'response_length' => strlen($parsedResponse['message'] ?? ''),
                'has_buttons' => !empty($parsedResponse['buttons']),
                'button_count' => count($parsedResponse['buttons'] ?? []),
                'has_actions' => !empty($parsedResponse['actions']),
                'full_parsed_response' => $parsedResponse
            ]);

            EcommerceLogger::info('AI processed message', [
                'tenant_id' => $this->tenantId,
                'contact_id' => $contact->id,
                'message' => substr($message, 0, 100),
                'ai_response_type' => $parsedResponse['type'] ?? 'text'
            ]);

            return [
                'handled' => true,
                'response' => $parsedResponse['message'],
                'buttons' => $parsedResponse['buttons'] ?? [],
                'actions' => $parsedResponse['actions'] ?? []
            ];

        } catch (\Exception $e) {
            EcommerceLogger::error('AI processing failed', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantId
            ]);

            return [
                'handled' => false,
                'response' => 'I apologize, but I encountered an error. Please try again.'
            ];
        }
    }

    /**
     * Check if AI is properly configured
     */
    protected function isAiConfigured(): bool
    {
        return $this->config 
            && $this->config->ai_powered_mode 
            && !empty($this->config->openai_api_key);
    }

    /**
     * Get product data from local database
     */
    protected function getProductDataFromDatabase(): array
    {
        try {
            EcommerceLogger::info('🤖 AI-DATABASE: Fetching products from local database', [
                'tenant_id' => $this->tenantId
            ]);

            // Get active, in-stock products from the database
            $products = Product::where('tenant_id', $this->tenantId)
                ->active()
                ->inStock()
                ->get();

            EcommerceLogger::info('🤖 AI-DATABASE: Products query executed', [
                'tenant_id' => $this->tenantId,
                'total_products_found' => $products->count()
            ]);

            // Convert to array format expected by AI
            $productData = [];
            foreach ($products as $product) {
                $productArray = [
                    'id' => $product->id,
                    'name' => $product->name,
                    'description' => $product->description,
                    'price' => $product->effective_price,
                    'original_price' => $product->price,
                    'sale_price' => $product->sale_price,
                    'stock_quantity' => $product->stock_quantity,
                    'category' => $product->category,
                    'subcategory' => $product->subcategory,
                    'sku' => $product->sku,
                    'status' => $product->status,
                    'featured' => $product->featured,
                    'is_on_sale' => $product->is_on_sale,
                    'is_low_stock' => $product->is_low_stock,
                    'formatted_price' => $product->formatted_price,
                    'tags' => $product->tags,
                    'images' => $product->images,
                    'primary_image' => $product->primary_image
                ];
                
                $productData[] = $productArray;
            }

            EcommerceLogger::info('🤖 AI-DATABASE: Product data formatted for AI', [
                'tenant_id' => $this->tenantId,
                'products_count' => count($productData),
                'sample_product' => $productData[0] ?? null
            ]);

            return $productData;

        } catch (\Exception $e) {
            EcommerceLogger::error('🤖 AI-DATABASE: Failed to get product data from database', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            return [];
        }
    }

    /**
     * Build system prompt for AI
     */
    protected function buildSystemPrompt(array $products, $contact): string
    {
        $customerName = trim($contact->firstname . ' ' . $contact->lastname);
        $productsJson = json_encode($products, JSON_UNESCAPED_UNICODE);
        
        $systemPrompt = $this->config->ai_system_prompt ?: $this->getDefaultSystemPrompt();
        
        $context = [
            'store_name' => $this->config->store_name ?? 'Our Store',
            'currency' => $this->config->currency ?? 'USD',
            'customer_name' => $customerName ?: 'Customer',
            'customer_phone' => $contact->phone ?? '',
            'products_data' => $productsJson,
            'total_products' => count($products),
            'payment_methods' => $this->getEnabledPaymentMethods(),
            'collection_policy' => $this->getCustomerDetailsPolicy()
        ];

        // Replace placeholders in system prompt
        foreach ($context as $key => $value) {
            $systemPrompt = str_replace("{{$key}}", $value, $systemPrompt);
        }

        return $systemPrompt;
    }

    /**
     * Get default system prompt
     */
    protected function getDefaultSystemPrompt(): string
    {
        return "
You are an AI shopping assistant for {store_name}. You help customers find and purchase products via WhatsApp.

CUSTOMER INFO:
- Name: {customer_name}
- Phone: {customer_phone}

AVAILABLE PRODUCTS:
{products_data}

PAYMENT METHODS:
{payment_methods}

CUSTOMER DETAILS POLICY:
{collection_policy}

INSTRUCTIONS:
1. Be friendly, helpful, and conversational
2. Help customers find products by understanding their needs
3. Show product details with clean formatting (minimal use of asterisks)
4. Use emojis for visual appeal instead of excessive formatting
5. Create interactive buttons for product actions
6. Keep responses concise and mobile-friendly

FORMATTING RULES:
- Use emojis for bullets: 📦 for products, 💰 for price, 📋 for description
- Only use *asterisks* for product names, avoid for labels like Price, Description
- Use clean, readable text format
- When showing multiple products, use numbered lists

GOOD EXAMPLE:
\"Here are the jeans available:

1. *Slim Fit Blue Jeans*
💰 \$30
📋 Comfortable slim fit blue jeans for everyday wear
📦 Available in all sizes

2. *Distressed Denim Jeans*  
💰 \$35
📋 Trendy distressed denim jeans for a stylish look
📦 Limited stock available

Which one interests you?\"

RESPONSE FORMAT:
For simple text: return clean formatted message like above.

For responses with buttons, return EXACT JSON:
{
  \"message\": \"Here are the available jeans:\\n\\n1. *Slim Fit Blue Jeans*\\n💰 \$30\\n📋 Comfortable and stylish\\n\\n2. *Distressed Denim*\\n💰 \$35\\n📋 Trendy design\\n\\nWhich interests you?\",
  \"buttons\": [
    {\"id\": \"buy_1\", \"text\": \"🛒 Buy Slim Fit\"},
    {\"id\": \"buy_2\", \"text\": \"🛒 Buy Distressed\"},
    {\"id\": \"info_more\", \"text\": \"ℹ️ More Info\"}
  ],
  \"type\": \"interactive\"
}

CRITICAL: Return ONLY JSON when buttons needed, no mixed text+JSON.
        ";
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAI(string $systemPrompt, string $userMessage): string
    {
        $apiKey = $this->config->openai_api_key;
        $model = $this->config->openai_model ?: 'gpt-3.5-turbo';
        $temperature = (float) ($this->config->ai_temperature ?: 0.7);
        $maxTokens = (int) ($this->config->ai_max_tokens ?: 500);

        $response = Http::withToken($apiKey)
            ->timeout(30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model,
                'messages' => [
                    ['role' => 'system', 'content' => $systemPrompt],
                    ['role' => 'user', 'content' => $userMessage]
                ],
                'temperature' => $temperature,
                'max_tokens' => $maxTokens
            ]);

        if (!$response->successful()) {
            throw new \Exception('OpenAI API request failed: ' . $response->body());
        }

        $data = $response->json();
        return $data['choices'][0]['message']['content'] ?? '';
    }

    /**
     * Parse AI response for actions and buttons
     */
    protected function parseAiResponse(string $aiResponse): array
    {
        // Try to parse as JSON first
        $jsonData = json_decode($aiResponse, true);
        
        if (json_last_error() === JSON_ERROR_NONE && is_array($jsonData)) {
            EcommerceLogger::info('🤖 AI-JSON: Successfully parsed JSON response', [
                'tenant_id' => $this->tenantId,
                'has_message' => !empty($jsonData['message']),
                'has_buttons' => !empty($jsonData['buttons']),
                'button_count' => count($jsonData['buttons'] ?? []),
                'buttons_preview' => $jsonData['buttons'] ?? []
            ]);
            
            return [
                'type' => $jsonData['type'] ?? 'interactive',
                'message' => $jsonData['message'] ?? $aiResponse,
                'buttons' => $jsonData['buttons'] ?? [],
                'actions' => $jsonData['actions'] ?? []
            ];
        }

        EcommerceLogger::info('🤖 AI-TEXT: Using plain text response (not JSON)', [
            'tenant_id' => $this->tenantId,
            'response_preview' => substr($aiResponse, 0, 100) . '...'
        ]);

        // Return as plain text response
        return [
            'type' => 'text',
            'message' => $aiResponse,
            'buttons' => [],
            'actions' => []
        ];
    }

    /**
     * Get enabled payment methods for context
     */
    protected function getEnabledPaymentMethods(): string
    {
        $methods = $this->config->getEnabledPaymentMethods();
        $enabled = [];
        
        foreach ($methods as $key => $enabled_status) {
            if ($enabled_status) {
                $enabled[] = match($key) {
                    'cod' => 'Cash on Delivery',
                    'bank_transfer' => 'Bank Transfer', 
                    'card' => 'Credit/Debit Card',
                    'online' => 'Online Payment',
                    default => ucfirst(str_replace('_', ' ', $key))
                };
            }
        }

        return implode(', ', $enabled) ?: 'Cash on Delivery';
    }

    /**
     * Get customer details collection policy
     */
    protected function getCustomerDetailsPolicy(): string
    {
        if (!$this->config->collect_customer_details) {
            return 'No customer details required';
        }

        $fields = $this->config->getRequiredCustomerFields();
        $required = [];
        
        foreach ($fields as $field => $isRequired) {
            if ($isRequired) {
                $required[] = match($field) {
                    'name' => 'Full Name',
                    'phone' => 'Phone Number',
                    'address' => 'Address', 
                    'city' => 'City',
                    'email' => 'Email',
                    'notes' => 'Special Instructions',
                    default => ucfirst($field)
                };
            }
        }

        return 'Required: ' . implode(', ', $required);
    }

    /**
     * Process AI-generated actions
     */
    public function executeActions(array $actions): array
    {
        $results = [];
        
        foreach ($actions as $action) {
            switch ($action['type']) {
                case 'create_order':
                    // TODO: Implement local database order creation
                    $results[] = ['success' => true, 'action' => 'order_created', 'message' => 'Order functionality not implemented yet'];
                    break;
                    
                case 'update_stock':
                    // TODO: Implement local database stock updates
                    $results[] = ['success' => true, 'action' => 'stock_updated', 'message' => 'Stock update functionality not implemented yet'];
                    break;
                    
                case 'add_customer':
                    // TODO: Implement local database customer management
                    $results[] = ['success' => true, 'action' => 'customer_added', 'message' => 'Customer management functionality not implemented yet'];
                    break;
            }
        }
        
        return $results;
    }
}
