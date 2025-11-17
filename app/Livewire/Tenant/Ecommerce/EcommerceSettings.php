<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\EcommerceConfiguration;
use App\Services\GoogleSheetsService;
use App\Services\GoogleSheetsDirectApiService;
use App\Services\GoogleSheetsServiceAccountService;
use App\Services\EcommerceLogger;
use Livewire\Component;
use Livewire\WithFileUploads;

class EcommerceSettings extends Component
{
    use WithFileUploads;

    public $config;
    public $generatedScript = '';
    public $showScriptModal = false;
    public $showImportModal = false;
    public $importData = [];
    public $serviceAccountStatus = [];
    public $settings = [
        'currency' => 'USD',
        'tax_rate' => '0.00',
        'payment_methods' => [],
        'order_confirmation_message' => '',
        'payment_confirmation_message' => '',
        'ai_recommendations_enabled' => true,
        'abandoned_cart_settings' => [
            'enabled' => false,
            'delay_hours' => 24,
            'message' => ''
        ],
        'upselling_settings' => [
            'enabled' => true,
            'threshold_amount' => 50,
            'message_template' => ''
        ],
        'shipping_settings' => [
            'enabled' => false,
            'free_shipping_threshold' => 0,
            'default_shipping_cost' => 0
        ],
        // AI Settings
        'ai_enabled' => false,
        'ai_provider' => 'openai',
        'ai_api_key' => '',
        'ai_model' => 'gpt-3.5-turbo',
        'ai_temperature' => 0.7,
        'ai_max_tokens' => 1000,
        'ai_system_prompt' => '',
        'ai_product_recommendations' => true,
        'ai_order_processing' => true,
        'ai_customer_support' => true,
        'ai_response_timeout' => 30,
        'ai_fallback_message' => 'I apologize, but I\'m temporarily unavailable. A human agent will assist you shortly.'
    ];

    public $availableAiModels = [
        'openai' => [
            ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo (Fast & Cost-effective)'],
            ['id' => 'gpt-4', 'name' => 'GPT-4 (Most Capable)'],
            ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo (Balanced)'],
        ]
    ];

    public $testingApiKey = false;
    public $apiKeyTestResult = '';

    public $availablePaymentMethods = [
        'cash_on_delivery' => 'Cash on Delivery',
        'bank_transfer' => 'Bank Transfer',
        'upi' => 'UPI Payment',
        'credit_card' => 'Credit Card',
        'debit_card' => 'Debit Card',
        'paypal' => 'PayPal',
        'stripe' => 'Stripe',
        'razorpay' => 'Razorpay'
    ];

    public $availableCurrencies = [
        'USD' => 'US Dollar ($)',
        'EUR' => 'Euro (€)',
        'GBP' => 'British Pound (£)',
        'INR' => 'Indian Rupee (₹)',
        'JPY' => 'Japanese Yen (¥)',
        'AUD' => 'Australian Dollar (A$)',
        'CAD' => 'Canadian Dollar (C$)'
    ];

    protected $rules = [
        'settings.currency' => 'required|string|in:USD,EUR,GBP,INR,JPY,AUD,CAD',
        'settings.tax_rate' => 'required|numeric|min:0|max:100',
        'settings.payment_methods' => 'required|array|min:1',
        'settings.order_confirmation_message' => 'nullable|string|max:1000',
        'settings.payment_confirmation_message' => 'nullable|string|max:1000',
        'settings.ai_recommendations_enabled' => 'boolean',
        'settings.abandoned_cart_settings.enabled' => 'boolean',
        'settings.abandoned_cart_settings.delay_hours' => 'required_if:settings.abandoned_cart_settings.enabled,true|integer|min:1|max:168',
        'settings.abandoned_cart_settings.message' => 'required_if:settings.abandoned_cart_settings.enabled,true|string|max:1000',
        'settings.upselling_settings.enabled' => 'boolean',
        'settings.upselling_settings.threshold_amount' => 'required_if:settings.upselling_settings.enabled,true|numeric|min:0',
        'settings.shipping_settings.enabled' => 'boolean',
        'settings.shipping_settings.free_shipping_threshold' => 'required_if:settings.shipping_settings.enabled,true|numeric|min:0',
        'settings.shipping_settings.default_shipping_cost' => 'required_if:settings.shipping_settings.enabled,true|numeric|min:0',
        
        // AI Settings Validation (relaxed for debugging)
        'settings.ai_enabled' => 'boolean',
        'settings.ai_provider' => 'nullable|string',
        'settings.ai_api_key' => 'nullable|string',
        'settings.ai_model' => 'nullable|string',
        'settings.ai_temperature' => 'nullable|numeric|min:0|max:1',
        'settings.ai_max_tokens' => 'nullable|integer|min:100|max:4000',
        'settings.ai_system_prompt' => 'nullable|string|max:2000',
        'settings.ai_product_recommendations' => 'boolean',
        'settings.ai_order_processing' => 'boolean',
        'settings.ai_customer_support' => 'boolean',
        'settings.ai_response_timeout' => 'nullable|integer|min:5|max:120',
        'settings.ai_fallback_message' => 'nullable|string|max:500',
    ];

    public function mount()
    {
        if (!checkPermission('tenant.ecommerce.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $this->loadSettings();
        $this->checkServiceAccountStatus();
    }

    public function loadSettings()
    {
        $this->config = EcommerceConfiguration::where('tenant_id', tenant_id())->first();
        
        if ($this->config) {
            $this->settings = [
                'currency' => $this->config->currency ?? 'USD',
                'tax_rate' => number_format($this->config->tax_rate ?? 0, 2),
                'payment_methods' => $this->config->payment_methods ?? ['cash_on_delivery'],
                'order_confirmation_message' => $this->config->order_confirmation_message ?? $this->getDefaultOrderMessage(),
                'payment_confirmation_message' => $this->config->payment_confirmation_message ?? $this->getDefaultPaymentMessage(),
                'ai_recommendations_enabled' => $this->config->ai_recommendations_enabled ?? true,
                
                // AI Settings
                'ai_enabled' => $this->config->ai_enabled ?? false,
                'ai_provider' => $this->config->ai_provider ?? 'openai',
                'ai_api_key' => $this->config->ai_api_key ?? '',
                'ai_model' => $this->config->ai_model ?? 'gpt-3.5-turbo',
                'ai_temperature' => $this->config->ai_temperature ?? 0.7,
                'ai_max_tokens' => $this->config->ai_max_tokens ?? 1000,
                'ai_system_prompt' => $this->config->ai_system_prompt ?? $this->getDefaultSystemPrompt(),
                'ai_product_recommendations' => $this->config->ai_product_recommendations ?? true,
                'ai_order_processing' => $this->config->ai_order_processing ?? true,
                'ai_customer_support' => $this->config->ai_customer_support ?? true,
                'ai_response_timeout' => $this->config->ai_response_timeout ?? 30,
                'ai_fallback_message' => $this->config->ai_fallback_message ?? 'I apologize, but I\'m temporarily unavailable. A human agent will assist you shortly.',
                'abandoned_cart_settings' => $this->config->abandoned_cart_settings ?? [
                    'enabled' => false,
                    'delay_hours' => 24,
                    'message' => $this->getDefaultAbandonedCartMessage()
                ],
                'upselling_settings' => $this->config->upselling_settings ?? [
                    'enabled' => true,
                    'threshold_amount' => 50,
                    'message_template' => $this->getDefaultUpsellingMessage()
                ],
                'shipping_settings' => $this->config->shipping_settings ?? [
                    'enabled' => false,
                    'free_shipping_threshold' => 0,
                    'default_shipping_cost' => 0
                ]
            ];
        }
    }

    public function saveSettings()
    {
        try {
            // Add debugging
            \Log::info('Saving AI settings', [
                'ai_enabled' => $this->settings['ai_enabled'] ?? 'not_set',
                'ai_api_key' => isset($this->settings['ai_api_key']) ? 'SET' : 'NOT_SET',
                'ai_model' => $this->settings['ai_model'] ?? 'not_set'
            ]);

            $this->validate();

            if (!$this->config) {
                $this->notify(['type' => 'danger', 'message' => 'Please complete e-commerce setup first']);
                return redirect()->to(tenant_route('tenant.ecommerce.setup'));
            }

            $updateData = [
                'currency' => $this->settings['currency'],
                'tax_rate' => (float) $this->settings['tax_rate'],
                'payment_methods' => $this->settings['payment_methods'],
                'order_confirmation_message' => $this->settings['order_confirmation_message'],
                'payment_confirmation_message' => $this->settings['payment_confirmation_message'],
                'ai_recommendations_enabled' => $this->settings['ai_recommendations_enabled'],
                'abandoned_cart_settings' => $this->settings['abandoned_cart_settings'],
                'upselling_settings' => $this->settings['upselling_settings'],
                'shipping_settings' => $this->settings['shipping_settings'],
                
                // AI Settings
                'ai_enabled' => $this->settings['ai_enabled'] ?? false,
                'ai_provider' => $this->settings['ai_provider'] ?? 'openai',
                'ai_api_key' => $this->settings['ai_api_key'] ?? '',
                'ai_model' => $this->settings['ai_model'] ?? 'gpt-3.5-turbo',
                'ai_temperature' => (float) ($this->settings['ai_temperature'] ?? 0.7),
                'ai_max_tokens' => (int) ($this->settings['ai_max_tokens'] ?? 1000),
                'ai_system_prompt' => $this->settings['ai_system_prompt'] ?? '',
                'ai_product_recommendations' => $this->settings['ai_product_recommendations'] ?? true,
                'ai_order_processing' => $this->settings['ai_order_processing'] ?? true,
                'ai_customer_support' => $this->settings['ai_customer_support'] ?? true,
                'ai_response_timeout' => (int) ($this->settings['ai_response_timeout'] ?? 30),
                'ai_fallback_message' => $this->settings['ai_fallback_message'] ?? '',
            ];

            \Log::info('Updating config with data', $updateData);
            
            $this->config->update($updateData);

            \Log::info('Settings saved successfully');
            $this->notify(['type' => 'success', 'message' => 'E-commerce settings updated successfully including AI configuration!']);

        } catch (\Illuminate\Validation\ValidationException $e) {
            \Log::error('Validation failed', ['errors' => $e->errors()]);
            $errorMessages = collect($e->errors())->flatten()->implode(', ');
            $this->notify(['type' => 'danger', 'message' => 'Validation failed: ' . $errorMessages]);
        } catch (\Exception $e) {
            \Log::error('Save settings failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            $this->notify(['type' => 'danger', 'message' => 'Error updating settings: ' . $e->getMessage()]);
        }
    }

    public function resetToDefaults()
    {
        $this->settings = [
            'currency' => 'USD',
            'tax_rate' => '0.00',
            'payment_methods' => ['cash_on_delivery'],
            'order_confirmation_message' => $this->getDefaultOrderMessage(),
            'payment_confirmation_message' => $this->getDefaultPaymentMessage(),
            'ai_recommendations_enabled' => true,
            'abandoned_cart_settings' => [
                'enabled' => false,
                'delay_hours' => 24,
                'message' => $this->getDefaultAbandonedCartMessage()
            ],
            'upselling_settings' => [
                'enabled' => true,
                'threshold_amount' => 50,
                'message_template' => $this->getDefaultUpsellingMessage()
            ],
            'shipping_settings' => [
                'enabled' => false,
                'free_shipping_threshold' => 0,
                'default_shipping_cost' => 0
            ],
            // AI Settings Defaults
            'ai_enabled' => false,
            'ai_provider' => 'openai',
            'ai_api_key' => '',
            'ai_model' => 'gpt-3.5-turbo',
            'ai_temperature' => 0.7,
            'ai_max_tokens' => 1000,
            'ai_system_prompt' => $this->getDefaultSystemPrompt(),
            'ai_product_recommendations' => true,
            'ai_order_processing' => true,
            'ai_customer_support' => true,
            'ai_response_timeout' => 30,
            'ai_fallback_message' => 'I apologize, but I\'m temporarily unavailable. A human agent will assist you shortly.'
        ];

        $this->notify(['type' => 'info', 'message' => 'Settings reset to defaults']);
    }

    public function syncWithGoogleSheets()
    {
        try {
            $sheetsService = new GoogleSheetsService();
            $result = $sheetsService->syncProductsFromSheets();
            
            if ($result['success']) {
                $this->notify(['type' => 'success', 'message' => $result['message']]);
            } else {
                $this->notify(['type' => 'danger', 'message' => $result['message']]);
            }
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    public function syncSheets()
    {
        try {
            if (!$this->config) {
                $this->notify(['type' => 'danger', 'message' => 'Please complete e-commerce setup first']);
                return;
            }

            EcommerceLogger::info('One-click sheet creation initiated', [
                'tenant_id' => tenant_id(),
                'user_id' => auth()->id()
            ]);

            // Try Service Account first (fully automatic)
            $serviceAccountService = new GoogleSheetsServiceAccountService();
            $result = $serviceAccountService->createEcommerceSheetsAutomatic($this->config);
            
            // If Service Account fails, fall back to import method
            if (!$result['success']) {
                $apiService = new GoogleSheetsDirectApiService();
                $result = $apiService->createEcommerceSheetsOneClick($this->config);
            }
            
            if ($result['success']) {
                if (isset($result['method']) && $result['method'] === 'import') {
                    // Show import modal with prepared data
                    $this->importData = $result['import_data'];
                    $this->showImportModal = true;
                    $this->notify(['type' => 'info', 'message' => 'Sheet structure prepared! Please follow the import instructions.']);
                } else {
                    // Sheets were created successfully via API
                    $this->notify(['type' => 'success', 'message' => $result['message']]);
                }
                
                EcommerceLogger::info('One-click sheet creation completed', [
                    'tenant_id' => tenant_id(),
                    'method' => $result['method'] ?? 'api',
                    'created_sheets' => $result['created_sheets'] ?? []
                ]);
            } else {
                EcommerceLogger::error('One-click sheet creation failed', [
                    'tenant_id' => tenant_id(),
                    'error' => $result['message']
                ]);
                $this->notify(['type' => 'danger', 'message' => $result['message']]);
            }
        } catch (\Exception $e) {
            EcommerceLogger::error('One-click sheet creation exception', [
                'tenant_id' => tenant_id(),
                'exception' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            $this->notify(['type' => 'danger', 'message' => 'Sheet creation failed: ' . $e->getMessage()]);
        }
    }

    public function closeScriptModal()
    {
        $this->showScriptModal = false;
        $this->generatedScript = '';
    }

    public function closeImportModal()
    {
        $this->showImportModal = false;
        $this->importData = [];
    }

    public function checkServiceAccountStatus()
    {
        $service = new GoogleSheetsServiceAccountService();
        $this->serviceAccountStatus = $service->checkServiceAccountSetup();
    }

    public function disconnectGoogleSheets()
    {
        try {
            if (!$this->config) {
                $this->notify(['type' => 'danger', 'message' => 'No e-commerce configuration found']);
                return;
            }

            EcommerceLogger::info('Google Sheets disconnection initiated', [
                'tenant_id' => tenant_id(),
                'user_id' => auth()->id(),
                'previous_url' => $this->config->google_sheets_url
            ]);

            // Clear Google Sheets configuration
            $this->config->update([
                'google_sheets_url' => null,
                'google_sheets_enabled' => false,
                'last_sync_at' => null
            ]);

            // Update local settings
            $this->settings['google_sheets_url'] = '';
            $this->settings['google_sheets_enabled'] = false;

            EcommerceLogger::info('Google Sheets disconnected successfully', [
                'tenant_id' => tenant_id(),
                'user_id' => auth()->id()
            ]);

            $this->notify([
                'type' => 'success', 
                'message' => 'Google Sheets disconnected successfully! All sheet connection data has been removed.'
            ]);

            // Refresh the component
            $this->loadSettings();

        } catch (\Exception $e) {
            EcommerceLogger::error('Google Sheets disconnection failed', [
                'tenant_id' => tenant_id(),
                'user_id' => auth()->id(),
                'exception' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            $this->notify([
                'type' => 'danger', 
                'message' => 'Failed to disconnect Google Sheets: ' . $e->getMessage()
            ]);
        }
    }


    protected function getDefaultOrderMessage()
    {
        return "🎉 Order Confirmed! Your order #{order_number} has been received and is being processed. Total: {total_amount}. We'll keep you updated via WhatsApp!";
    }

    protected function getDefaultPaymentMessage()
    {
        return "✅ Payment Received! Thank you for your payment of {payment_amount} for order #{order_number}. Your order will be shipped soon!";
    }

    protected function getDefaultAbandonedCartMessage()
    {
        return "🛒 You left something in your cart! Don't miss out on: {cart_items}. Complete your order now and get it delivered to your doorstep!";
    }

    protected function getDefaultUpsellingMessage()
    {
        return "🔥 Special Offer! Since you're ordering {current_items}, how about adding {recommended_item} for just {additional_cost} more? Perfect combo!";
    }

    public function testApiKey()
    {
        if (!$this->settings['ai_api_key']) {
            $this->notify(['type' => 'danger', 'message' => 'Please enter an API key first.']);
            return;
        }

        $this->testingApiKey = true;
        $this->apiKeyTestResult = '';

        try {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->settings['ai_api_key'],
                'Content-Type' => 'application/json',
            ])
            ->timeout(10)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->settings['ai_model'],
                'messages' => [
                    ['role' => 'user', 'content' => 'Hello, this is a test message.']
                ],
                'max_tokens' => 50,
            ]);

            if ($response->successful()) {
                $this->apiKeyTestResult = 'success';
                $this->notify([
                    'type' => 'success',
                    'message' => '✅ API key is working! AI assistant is ready.'
                ]);
            } else {
                $this->apiKeyTestResult = 'error';
                $this->notify([
                    'type' => 'danger',
                    'message' => '❌ API key test failed: ' . $response->body()
                ]);
            }

        } catch (\Exception $e) {
            $this->apiKeyTestResult = 'error';
            $this->notify([
                'type' => 'danger',
                'message' => '❌ API key test failed: ' . $e->getMessage()
            ]);
        } finally {
            $this->testingApiKey = false;
        }
    }

    protected function getDefaultSystemPrompt()
    {
        return "You are a helpful e-commerce assistant for a WhatsApp store. Your goals:

1. Help customers find and learn about products
2. Process orders efficiently and accurately  
3. Provide excellent customer service
4. Keep responses friendly, concise, and helpful

When showing products:
- Include name, price, description, and stock status
- For single products, end with [BUTTONS:product_id] to show Buy Now/Add to Cart buttons
- Use emojis to make messages engaging

For orders:
- Collect: product, quantity, payment method, delivery address
- Confirm details before processing
- End with [ORDER:product_id:quantity] when ready to create order

Always be polite, professional, and focus on helping the customer make the best choice for their needs.";
    }

    public function render()
    {
        return view('livewire.tenant.ecommerce.settings', [
            'availablePaymentMethods' => $this->availablePaymentMethods,
            'availableCurrencies' => $this->availableCurrencies,
            'availableAiModels' => $this->availableAiModels,
        ]);
    }
}
