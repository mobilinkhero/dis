<?php

namespace App\Services;

use App\Models\Tenant\EcommerceConfiguration;
use App\Models\Tenant\Product;
use App\Models\Tenant\Order;
use App\Models\Tenant\Contact;
use App\Services\EcommerceLogger;
use Illuminate\Support\Facades\Http;

/**
 * AI Service for E-commerce Bot
 * Handles ChatGPT integration for product recommendations, order processing, and customer support
 */
class EcommerceAiService
{
    protected $config;
    protected $tenantId;

    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
        $this->config = EcommerceConfiguration::where('tenant_id', $tenantId)->first();
    }

    /**
     * Process customer message using AI
     */
    public function processMessage(string $message, Contact $contact, array $context = []): array
    {
        if (!$this->isAiEnabled()) {
            return [
                'handled' => false,
                'response' => 'AI is not enabled for this store.',
            ];
        }

        try {
            // Get products context
            $products = $this->getProductsContext();
            
            // Get conversation history
            $conversationHistory = $this->getConversationHistory($contact);
            
            // Build system prompt
            $systemPrompt = $this->buildSystemPrompt($products, $contact);
            
            // Build conversation messages
            $messages = [
                ['role' => 'system', 'content' => $systemPrompt],
                ...$conversationHistory,
                ['role' => 'user', 'content' => $message]
            ];

            // Call AI API
            $response = $this->callAiApi($messages);
            
            if ($response['success']) {
                // Update conversation history
                $this->updateConversationHistory($contact, $message, $response['response']);
                
                // Parse AI response for actions
                $parsedResponse = $this->parseAiResponse($response['response'], $contact);
                
                return [
                    'handled' => true,
                    'response' => $parsedResponse['message'],
                    'buttons' => $parsedResponse['buttons'] ?? null,
                    'actions' => $parsedResponse['actions'] ?? [],
                ];
            } else {
                EcommerceLogger::error('AI API call failed', [
                    'tenant_id' => $this->tenantId,
                    'error' => $response['error']
                ]);
                
                return $this->getFallbackResponse($message, $contact);
            }
            
        } catch (\Exception $e) {
            EcommerceLogger::error('AI processing failed', [
                'tenant_id' => $this->tenantId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            return $this->getFallbackResponse($message, $contact);
        }
    }

    /**
     * Build system prompt for AI
     */
    protected function buildSystemPrompt(array $products, Contact $contact): string
    {
        $basePrompt = $this->config->ai_system_prompt ?: "You are a helpful e-commerce assistant for a WhatsApp store. Your goal is to help customers find products, place orders, and provide excellent customer service.";
        
        $prompt = $basePrompt . "\n\n";
        
        // Add store context
        $prompt .= "**STORE INFORMATION:**\n";
        $prompt .= "- Currency: " . ($this->config->currency ?? 'USD') . "\n";
        $prompt .= "- Available payment methods: " . implode(', ', $this->config->payment_methods ?? ['Cash on Delivery']) . "\n\n";
        
        // Add products context
        $prompt .= "**AVAILABLE PRODUCTS:**\n";
        foreach ($products as $product) {
            $prompt .= "- {$product['name']}: {$product['price']} ({$product['description']})\n";
            $prompt .= "  Stock: {$product['stock']} units, Category: {$product['category']}\n";
        }
        
        // Add customer context
        $prompt .= "\n**CUSTOMER INFO:**\n";
        $prompt .= "- Name: {$contact->firstname} {$contact->lastname}\n";
        $prompt .= "- Phone: {$contact->phone}\n";
        
        // Add instructions
        $prompt .= "\n**INSTRUCTIONS:**\n";
        $prompt .= "1. Always be friendly and helpful\n";
        $prompt .= "2. When showing product details, include price, description, and stock\n";
        $prompt .= "3. For single products, suggest buttons: 'Buy Now', 'Add to Cart', 'More Info'\n";
        $prompt .= "4. For orders, collect: product, quantity, payment method, delivery address\n";
        $prompt .= "5. Use emojis to make messages engaging\n";
        $prompt .= "6. Keep responses concise but informative\n\n";
        
        $prompt .= "**RESPONSE FORMAT:**\n";
        $prompt .= "- For product details with buttons: End with [BUTTONS:product_id]\n";
        $prompt .= "- For order creation: End with [ORDER:product_id:quantity]\n";
        $prompt .= "- For regular chat: Just respond normally\n";
        
        return $prompt;
    }

    /**
     * Call AI API (ChatGPT)
     */
    protected function callAiApi(array $messages): array
    {
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->config->ai_api_key,
                'Content-Type' => 'application/json',
            ])
            ->timeout($this->config->ai_response_timeout ?? 30)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->config->ai_model ?? 'gpt-3.5-turbo',
                'messages' => $messages,
                'temperature' => $this->config->ai_temperature ?? 0.7,
                'max_tokens' => $this->config->ai_max_tokens ?? 1000,
            ]);

            if ($response->successful()) {
                $data = $response->json();
                
                // Update usage statistics
                $this->updateUsageStats(true);
                
                return [
                    'success' => true,
                    'response' => $data['choices'][0]['message']['content'] ?? '',
                    'usage' => $data['usage'] ?? []
                ];
            } else {
                return [
                    'success' => false,
                    'error' => 'API call failed: ' . $response->status() . ' - ' . $response->body()
                ];
            }
            
        } catch (\Exception $e) {
            $this->updateUsageStats(false);
            
            return [
                'success' => false,
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Parse AI response for special actions
     */
    protected function parseAiResponse(string $response, Contact $contact): array
    {
        $result = [
            'message' => $response,
            'buttons' => null,
            'actions' => []
        ];

        // Check for button trigger
        if (preg_match('/\[BUTTONS:(\d+)\]/', $response, $matches)) {
            $productId = $matches[1];
            $product = Product::where('tenant_id', $this->tenantId)
                ->where('id', $productId)
                ->first();
                
            if ($product) {
                $result['buttons'] = [
                    [
                        'id' => 'buy_' . $productId,
                        'title' => '🛒 Buy Now'
                    ],
                    [
                        'id' => 'add_cart_' . $productId, 
                        'title' => '➕ Add to Cart'
                    ],
                    [
                        'id' => 'more_info_' . $productId,
                        'title' => 'ℹ️ More Info'
                    ]
                ];
            }
            
            // Remove the button trigger from message
            $result['message'] = preg_replace('/\[BUTTONS:\d+\]/', '', $response);
        }

        // Check for order creation trigger
        if (preg_match('/\[ORDER:(\d+):(\d+)\]/', $response, $matches)) {
            $productId = $matches[1];
            $quantity = $matches[2];
            
            $result['actions'][] = [
                'type' => 'create_order',
                'product_id' => $productId,
                'quantity' => $quantity,
                'contact_id' => $contact->id
            ];
            
            // Remove the order trigger from message
            $result['message'] = preg_replace('/\[ORDER:\d+:\d+\]/', '', $response);
        }

        return $result;
    }

    /**
     * Get products context for AI
     */
    protected function getProductsContext(): array
    {
        return Product::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->limit(20) // Limit to avoid token overflow
            ->get()
            ->map(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'price' => $product->formatted_price,
                    'description' => substr($product->description, 0, 100),
                    'category' => $product->category,
                    'stock' => $product->stock_quantity
                ];
            })
            ->toArray();
    }

    /**
     * Get conversation history for context
     */
    protected function getConversationHistory(Contact $contact): array
    {
        $memory = $this->config->ai_conversation_memory ?? [];
        $contactKey = 'contact_' . $contact->id;
        
        return $memory[$contactKey] ?? [];
    }

    /**
     * Update conversation history
     */
    protected function updateConversationHistory(Contact $contact, string $userMessage, string $aiResponse): void
    {
        $memory = $this->config->ai_conversation_memory ?? [];
        $contactKey = 'contact_' . $contact->id;
        
        if (!isset($memory[$contactKey])) {
            $memory[$contactKey] = [];
        }
        
        // Add new messages
        $memory[$contactKey][] = ['role' => 'user', 'content' => $userMessage];
        $memory[$contactKey][] = ['role' => 'assistant', 'content' => $aiResponse];
        
        // Keep only last 10 messages per contact to avoid token overflow
        $memory[$contactKey] = array_slice($memory[$contactKey], -10);
        
        // Update configuration
        $this->config->update(['ai_conversation_memory' => $memory]);
    }

    /**
     * Update AI usage statistics
     */
    protected function updateUsageStats(bool $success): void
    {
        $this->config->increment('ai_requests_count');
        
        if ($success) {
            $totalRequests = $this->config->ai_requests_count;
            $currentSuccessRate = $this->config->ai_success_rate;
            $successfulRequests = ($currentSuccessRate / 100) * ($totalRequests - 1) + 1;
            $newSuccessRate = ($successfulRequests / $totalRequests) * 100;
            
            $this->config->update([
                'ai_success_rate' => round($newSuccessRate, 2),
                'ai_last_used_at' => now()
            ]);
        } else {
            $totalRequests = $this->config->ai_requests_count;
            $currentSuccessRate = $this->config->ai_success_rate;
            $successfulRequests = ($currentSuccessRate / 100) * ($totalRequests - 1);
            $newSuccessRate = ($successfulRequests / $totalRequests) * 100;
            
            $this->config->update([
                'ai_success_rate' => round($newSuccessRate, 2)
            ]);
        }
    }

    /**
     * Get fallback response when AI fails
     */
    protected function getFallbackResponse(string $message, Contact $contact): array
    {
        if ($this->config->ai_fallback_to_manual && $this->config->ai_fallback_message) {
            return [
                'handled' => true,
                'response' => $this->config->ai_fallback_message
            ];
        }

        // Default fallback to existing manual system
        return [
            'handled' => false,
            'response' => ''
        ];
    }

    /**
     * Check if AI is enabled and configured
     */
    public function isAiEnabled(): bool
    {
        return $this->config && 
               $this->config->ai_enabled && 
               !empty($this->config->ai_api_key) && 
               !empty($this->config->ai_model);
    }
}
