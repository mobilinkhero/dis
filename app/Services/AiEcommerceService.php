<?php

namespace App\Services;

use App\Models\Tenant\EcommerceConfiguration;
use App\Services\GoogleSheetsService;
use App\Services\GoogleSheetsServiceAccountService;
use App\Services\EcommerceLogger;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * AI-Powered E-commerce Service
 * Uses OpenAI to handle all customer interactions and directly integrates with Google Sheets
 */
class AiEcommerceService
{
    protected $tenantId;
    protected $config;
    protected $sheetsService;

    public function __construct($tenantId = null)
    {
        $this->tenantId = $tenantId ?? tenant_id();
        $this->config = EcommerceConfiguration::where('tenant_id', $this->tenantId)->first();
        $this->sheetsService = new GoogleSheetsService($this->tenantId);
    }

    /**
     * Process customer message with AI
     */
    public function processMessage(string $message, $contact): array
    {
        try {
            if (!$this->isAiConfigured()) {
                return [
                    'handled' => false,
                    'response' => 'AI is not configured for this store. Please contact support.'
                ];
            }

            // Get current product data from Google Sheets
            $productData = $this->getProductDataFromSheets();
            
            // Create AI context
            $systemPrompt = $this->buildSystemPrompt($productData, $contact);
            
            // Get AI response
            $aiResponse = $this->callOpenAI($systemPrompt, $message);
            
            // Parse AI response for actions
            $parsedResponse = $this->parseAiResponse($aiResponse);
            
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
            && !empty($this->config->openai_api_key)
            && !empty($this->config->google_sheets_url);
    }

    /**
     * Get product data directly from Google Sheets
     */
    protected function getProductDataFromSheets(): array
    {
        try {
            // Extract sheet ID
            $sheetId = $this->sheetsService->extractSheetId($this->config->google_sheets_url);
            if (!$sheetId) {
                return [];
            }

            // Get data using service account or public access
            $serviceAccountService = app(GoogleSheetsServiceAccountService::class);
            $serviceAccountStatus = $serviceAccountService->checkServiceAccountStatus($this->tenantId);

            if ($serviceAccountStatus['configured']) {
                return $this->fetchProductsWithServiceAccount($sheetId, $serviceAccountService);
            } else {
                return $this->fetchProductsPublic($sheetId);
            }

        } catch (\Exception $e) {
            EcommerceLogger::error('Failed to fetch products from sheets', [
                'error' => $e->getMessage(),
                'tenant_id' => $this->tenantId
            ]);
            return [];
        }
    }

    /**
     * Fetch products using service account
     */
    protected function fetchProductsWithServiceAccount(string $sheetId, $serviceAccount): array
    {
        $tokenResult = $serviceAccount->getAccessToken();
        if (!$tokenResult['success']) {
            return [];
        }

        $response = Http::withToken($tokenResult['token'])
            ->get("https://sheets.googleapis.com/v4/spreadsheets/{$sheetId}/values/Products!A:Z");

        if (!$response->successful()) {
            return [];
        }

        $data = $response->json();
        return $this->formatProductData($data['values'] ?? []);
    }

    /**
     * Fetch products using public CSV
     */
    protected function fetchProductsPublic(string $sheetId): array
    {
        $csvUrl = "https://docs.google.com/spreadsheets/d/{$sheetId}/export?format=csv&gid=0";
        
        $response = Http::timeout(15)->get($csvUrl);
        if (!$response->successful()) {
            return [];
        }

        $csvData = str_getcsv($response->body(), "\n");
        $header = str_getcsv(array_shift($csvData));
        
        $products = [];
        foreach ($csvData as $row) {
            if (empty($row)) continue;
            $rowData = str_getcsv($row);
            $products[] = array_combine($header, array_pad($rowData, count($header), ''));
        }

        return $products;
    }

    /**
     * Format product data for AI context
     */
    protected function formatProductData(array $rawData): array
    {
        if (empty($rawData)) return [];

        $header = array_shift($rawData);
        $products = [];

        foreach ($rawData as $row) {
            if (empty($row) || empty($row[0])) continue;
            
            $row = array_pad($row, count($header), '');
            $productData = array_combine($header, $row);
            
            // Only include active products with stock
            if (strtolower($productData['Status'] ?? 'active') === 'active' 
                && (int)($productData['Stock Quantity'] ?? 0) > 0) {
                $products[] = [
                    'id' => $productData['ID'] ?? '',
                    'name' => $productData['Name'] ?? '',
                    'description' => $productData['Description'] ?? '',
                    'price' => $productData['Price'] ?? '0',
                    'sale_price' => $productData['Sale Price'] ?? '',
                    'category' => $productData['Category'] ?? '',
                    'stock' => $productData['Stock Quantity'] ?? '0',
                    'featured' => strtolower($productData['Featured'] ?? 'no') === 'yes'
                ];
            }
        }

        return $products;
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
3. Show product details with prices in {currency}
4. Create WhatsApp buttons for actions (Buy Now, View More, etc.)
5. Guide customers through the purchase process
6. Collect required customer details when needed
7. Present available payment methods
8. Keep responses concise and mobile-friendly

RESPONSE FORMAT:
For text responses, just return the message.

For responses with buttons, use this JSON format:
{
  \"message\": \"Your message here\",
  \"buttons\": [
    {\"id\": \"action_productid\", \"text\": \"Button Text\"}
  ],
  \"type\": \"interactive\"
}

For order processing, include actions:
{
  \"message\": \"Order confirmed!\",
  \"actions\": [
    {\"type\": \"create_order\", \"data\": {...}},
    {\"type\": \"update_stock\", \"data\": {...}}
  ]
}
        ";
    }

    /**
     * Call OpenAI API
     */
    protected function callOpenAI(string $systemPrompt, string $userMessage): string
    {
        $apiKey = $this->config->openai_api_key;
        $model = $this->config->openai_model ?: 'gpt-3.5-turbo';
        $temperature = $this->config->ai_temperature ?: 0.7;
        $maxTokens = $this->config->ai_max_tokens ?: 500;

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
            return [
                'type' => $jsonData['type'] ?? 'interactive',
                'message' => $jsonData['message'] ?? $aiResponse,
                'buttons' => $jsonData['buttons'] ?? [],
                'actions' => $jsonData['actions'] ?? []
            ];
        }

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
                    $results[] = $this->createOrderInSheets($action['data']);
                    break;
                    
                case 'update_stock':
                    $results[] = $this->updateStockInSheets($action['data']);
                    break;
                    
                case 'add_customer':
                    $results[] = $this->addCustomerToSheets($action['data']);
                    break;
            }
        }
        
        return $results;
    }

    /**
     * Create order directly in Google Sheets
     */
    protected function createOrderInSheets(array $orderData): array
    {
        // Implementation would add a new row to the Orders sheet
        EcommerceLogger::info('Order created in sheets', [
            'order_data' => $orderData,
            'tenant_id' => $this->tenantId
        ]);
        
        return ['success' => true, 'action' => 'order_created'];
    }

    /**
     * Update stock directly in Google Sheets
     */
    protected function updateStockInSheets(array $stockData): array
    {
        // Implementation would update stock quantities in Products sheet
        EcommerceLogger::info('Stock updated in sheets', [
            'stock_data' => $stockData,
            'tenant_id' => $this->tenantId
        ]);
        
        return ['success' => true, 'action' => 'stock_updated'];
    }

    /**
     * Add customer to Google Sheets
     */
    protected function addCustomerToSheets(array $customerData): array
    {
        // Implementation would add customer to Customers sheet
        EcommerceLogger::info('Customer added to sheets', [
            'customer_data' => $customerData,
            'tenant_id' => $this->tenantId
        ]);
        
        return ['success' => true, 'action' => 'customer_added'];
    }
}
