<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\EcommerceConfiguration;
use App\Rules\PurifiedInput;
use Illuminate\Support\Facades\Http;
use Livewire\Component;

class AiSettings extends Component
{
    public $ai_enabled = false;
    public $ai_provider = 'openai';
    public $ai_api_key = '';
    public $ai_model = 'gpt-3.5-turbo';
    public $ai_temperature = 0.7;
    public $ai_max_tokens = 1000;
    public $ai_system_prompt = '';
    public $ai_product_recommendations = true;
    public $ai_order_processing = true;
    public $ai_customer_support = true;
    public $ai_inventory_alerts = false;
    public $ai_response_timeout = 30;
    public $ai_fallback_to_manual = true;
    public $ai_fallback_message = '';

    public $availableModels = [];
    public $testing = false;
    public $testResult = '';

    protected $rules = [
        'ai_enabled' => 'boolean',
        'ai_provider' => 'required|in:openai,claude,gemini',
        'ai_api_key' => 'required_if:ai_enabled,true|string',
        'ai_model' => 'required_if:ai_enabled,true|string',
        'ai_temperature' => 'numeric|min:0|max:1',
        'ai_max_tokens' => 'integer|min:1|max:4000',
        'ai_system_prompt' => 'nullable|string|max:2000',
        'ai_product_recommendations' => 'boolean',
        'ai_order_processing' => 'boolean',
        'ai_customer_support' => 'boolean',
        'ai_inventory_alerts' => 'boolean',
        'ai_response_timeout' => 'integer|min:5|max:120',
        'ai_fallback_to_manual' => 'boolean',
        'ai_fallback_message' => 'nullable|string|max:500',
    ];

    public function mount()
    {
        if (!checkPermission('tenant.ecommerce.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $this->loadConfiguration();
        $this->loadAvailableModels();
    }

    protected function loadConfiguration()
    {
        $config = EcommerceConfiguration::where('tenant_id', auth_tenant_id())->first();
        
        if ($config) {
            $this->ai_enabled = $config->ai_enabled ?? false;
            $this->ai_provider = $config->ai_provider ?? 'openai';
            $this->ai_api_key = $config->ai_api_key ?? '';
            $this->ai_model = $config->ai_model ?? 'gpt-3.5-turbo';
            $this->ai_temperature = $config->ai_temperature ?? 0.7;
            $this->ai_max_tokens = $config->ai_max_tokens ?? 1000;
            $this->ai_system_prompt = $config->ai_system_prompt ?? $this->getDefaultSystemPrompt();
            $this->ai_product_recommendations = $config->ai_product_recommendations ?? true;
            $this->ai_order_processing = $config->ai_order_processing ?? true;
            $this->ai_customer_support = $config->ai_customer_support ?? true;
            $this->ai_inventory_alerts = $config->ai_inventory_alerts ?? false;
            $this->ai_response_timeout = $config->ai_response_timeout ?? 30;
            $this->ai_fallback_to_manual = $config->ai_fallback_to_manual ?? true;
            $this->ai_fallback_message = $config->ai_fallback_message ?? 'I apologize, but I\'m temporarily unavailable. A human agent will assist you shortly.';
        } else {
            $this->ai_system_prompt = $this->getDefaultSystemPrompt();
            $this->ai_fallback_message = 'I apologize, but I\'m temporarily unavailable. A human agent will assist you shortly.';
        }
    }

    protected function loadAvailableModels()
    {
        $this->availableModels = [
            'openai' => [
                ['id' => 'gpt-3.5-turbo', 'name' => 'GPT-3.5 Turbo (Fast & Cost-effective)', 'description' => 'Good for most e-commerce tasks'],
                ['id' => 'gpt-4', 'name' => 'GPT-4 (Most Capable)', 'description' => 'Best performance, higher cost'],
                ['id' => 'gpt-4-turbo', 'name' => 'GPT-4 Turbo (Balanced)', 'description' => 'Latest model with good speed/cost ratio'],
            ],
            'claude' => [
                ['id' => 'claude-3-sonnet', 'name' => 'Claude 3 Sonnet', 'description' => 'Fast and capable'],
                ['id' => 'claude-3-opus', 'name' => 'Claude 3 Opus', 'description' => 'Most intelligent'],
            ],
            'gemini' => [
                ['id' => 'gemini-pro', 'name' => 'Gemini Pro', 'description' => 'Google\'s advanced model'],
            ]
        ];
    }

    public function save()
    {
        if (!checkPermission('tenant.ecommerce.edit')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')]);
            return;
        }

        $this->validate();

        try {
            $config = EcommerceConfiguration::firstOrCreate(
                ['tenant_id' => auth_tenant_id()],
                ['is_configured' => false]
            );

            $config->update([
                'ai_enabled' => $this->ai_enabled,
                'ai_provider' => $this->ai_provider,
                'ai_api_key' => $this->ai_api_key,
                'ai_model' => $this->ai_model,
                'ai_temperature' => $this->ai_temperature,
                'ai_max_tokens' => $this->ai_max_tokens,
                'ai_system_prompt' => $this->ai_system_prompt,
                'ai_product_recommendations' => $this->ai_product_recommendations,
                'ai_order_processing' => $this->ai_order_processing,
                'ai_customer_support' => $this->ai_customer_support,
                'ai_inventory_alerts' => $this->ai_inventory_alerts,
                'ai_response_timeout' => $this->ai_response_timeout,
                'ai_fallback_to_manual' => $this->ai_fallback_to_manual,
                'ai_fallback_message' => $this->ai_fallback_message,
            ]);

            $this->notify([
                'type' => 'success',
                'message' => 'AI settings saved successfully!'
            ]);

        } catch (\Exception $e) {
            $this->notify([
                'type' => 'danger',
                'message' => 'Failed to save AI settings: ' . $e->getMessage()
            ]);
        }
    }

    public function testApiKey()
    {
        if (!$this->ai_api_key) {
            $this->notify(['type' => 'danger', 'message' => 'Please enter an API key first.']);
            return;
        }

        $this->testing = true;
        $this->testResult = '';

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $this->ai_api_key,
                'Content-Type' => 'application/json',
            ])
            ->timeout(10)
            ->post('https://api.openai.com/v1/chat/completions', [
                'model' => $this->ai_model,
                'messages' => [
                    ['role' => 'user', 'content' => 'Hello, this is a test message.']
                ],
                'max_tokens' => 50,
            ]);

            if ($response->successful()) {
                $this->testResult = 'success';
                $this->notify([
                    'type' => 'success',
                    'message' => '✅ API key is working! AI assistant is ready.'
                ]);
            } else {
                $this->testResult = 'error';
                $this->notify([
                    'type' => 'danger',
                    'message' => '❌ API key test failed: ' . $response->body()
                ]);
            }

        } catch (\Exception $e) {
            $this->testResult = 'error';
            $this->notify([
                'type' => 'danger',
                'message' => '❌ API key test failed: ' . $e->getMessage()
            ]);
        } finally {
            $this->testing = false;
        }
    }

    public function resetToDefaults()
    {
        $this->ai_system_prompt = $this->getDefaultSystemPrompt();
        $this->ai_temperature = 0.7;
        $this->ai_max_tokens = 1000;
        $this->ai_response_timeout = 30;
        $this->ai_fallback_message = 'I apologize, but I\'m temporarily unavailable. A human agent will assist you shortly.';
        
        $this->notify([
            'type' => 'success',
            'message' => 'Settings reset to defaults.'
        ]);
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
        return view('livewire.tenant.ecommerce.ai-settings');
    }
}
