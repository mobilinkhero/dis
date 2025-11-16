<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\EcommerceConfiguration;
use App\Services\GoogleSheetsService;
use App\Services\EcommerceLogger;
use Livewire\Component;

class EcommerceSettings extends Component
{
    public $config;
    public $generatedScript = '';
    public $showScriptModal = false;
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
        ]
    ];

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
    ];

    public function mount()
    {
        if (!checkPermission('tenant.ecommerce.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $this->loadSettings();
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
        $this->validate();

        try {
            if (!$this->config) {
                $this->notify(['type' => 'danger', 'message' => 'Please complete e-commerce setup first']);
                return redirect()->to(tenant_route('tenant.ecommerce.setup'));
            }

            $this->config->update([
                'currency' => $this->settings['currency'],
                'tax_rate' => (float) $this->settings['tax_rate'],
                'payment_methods' => $this->settings['payment_methods'],
                'order_confirmation_message' => $this->settings['order_confirmation_message'],
                'payment_confirmation_message' => $this->settings['payment_confirmation_message'],
                'ai_recommendations_enabled' => $this->settings['ai_recommendations_enabled'],
                'abandoned_cart_settings' => $this->settings['abandoned_cart_settings'],
                'upselling_settings' => $this->settings['upselling_settings'],
                'shipping_settings' => $this->settings['shipping_settings'],
            ]);

            $this->notify(['type' => 'success', 'message' => 'E-commerce settings updated successfully']);
        } catch (\Exception $e) {
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
            ]
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

            EcommerceLogger::info('Sheet sync initiated from settings page', [
                'tenant_id' => tenant_id(),
                'user_id' => auth()->id()
            ]);

            $sheetsService = new GoogleSheetsService();
            $result = $sheetsService->checkAndCreateSheets($this->config);
            
            if ($result['success']) {
                EcommerceLogger::info('Sheet sync completed successfully', [
                    'tenant_id' => tenant_id(),
                    'created_sheets' => $result['required_sheets'] ?? []
                ]);
                
                // Read the generated Apps Script and show it to the user
                if (isset($result['apps_script_file']) && file_exists($result['apps_script_file'])) {
                    $this->generatedScript = file_get_contents($result['apps_script_file']);
                    $this->showScriptModal = true;
                }
                
                $this->notify(['type' => 'success', 'message' => 'Apps Script generated successfully! Please follow the instructions in the popup.']);
            } else {
                EcommerceLogger::error('Sheet sync failed', [
                    'tenant_id' => tenant_id(),
                    'error' => $result['message']
                ]);
                $this->notify(['type' => 'danger', 'message' => $result['message']]);
            }
        } catch (\Exception $e) {
            EcommerceLogger::error('Sheet sync exception', [
                'tenant_id' => tenant_id(),
                'exception' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            $this->notify(['type' => 'danger', 'message' => 'Sheet sync failed: ' . $e->getMessage()]);
        }
    }

    public function closeScriptModal()
    {
        $this->showScriptModal = false;
        $this->generatedScript = '';
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

    public function render()
    {
        return view('livewire.tenant.ecommerce.settings', [
            'availablePaymentMethods' => $this->availablePaymentMethods,
            'availableCurrencies' => $this->availableCurrencies,
        ]);
    }
}
