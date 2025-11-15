<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\EcommerceBot;
use App\Models\Tenant\Product;
use App\Models\Tenant\Order;
use App\Traits\Ai;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class UpsellCampaigns extends Component
{
    use Ai;

    public $ecommerceBot;
    public $upsellingRules = [];
    public $isEnabled = false;
    
    // Campaign creation
    public $campaignName = '';
    public $targetSegment = 'all';
    public $offerType = 'discount';
    public $discountPercentage = 10;
    public $minimumOrderValue = 100;
    public $selectedProducts = [];
    public $aiSuggestions = [];

    public function mount()
    {
        if (!checkPermission('tenant.ecommerce.upselling')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $this->ecommerceBot = EcommerceBot::where('tenant_id', tenant_id())->first();
        
        if ($this->ecommerceBot) {
            $this->upsellingRules = $this->ecommerceBot->upselling_rules ?? $this->getDefaultUpsellingRules();
            $this->isEnabled = $this->upsellingRules['enabled'] ?? false;
        } else {
            $this->upsellingRules = $this->getDefaultUpsellingRules();
        }
    }

    public function generateAiSuggestions()
    {
        if (!$this->ecommerceBot) {
            $this->notify(['type' => 'danger', 'message' => 'E-commerce bot not configured']);
            return;
        }

        try {
            // Get recent orders and products for AI analysis
            $recentOrders = Order::where('tenant_id', tenant_id())
                ->with('items.product')
                ->latest()
                ->limit(50)
                ->get();

            $topProducts = Product::where('tenant_id', tenant_id())
                ->where('status', 'active')
                ->orderBy('stock_quantity', 'desc')
                ->limit(10)
                ->get();

            // Create AI prompt for upselling suggestions
            $orderData = $recentOrders->map(function($order) {
                return [
                    'items' => $order->items->pluck('product_name')->toArray(),
                    'total' => $order->total_amount
                ];
            })->toArray();

            $prompt = "Based on this e-commerce data, suggest 3 intelligent upselling strategies:\n\n";
            $prompt .= "Recent Orders: " . json_encode(array_slice($orderData, 0, 10)) . "\n";
            $prompt .= "Top Products: " . $topProducts->pluck('name')->implode(', ') . "\n\n";
            $prompt .= "Provide specific, actionable upselling suggestions with product combinations and discount strategies.";

            $aiResponse = $this->aiResponse([
                'input_msg' => $prompt,
                'menu' => 'Custom Prompt',
                'submenu' => 'You are an e-commerce upselling expert. Analyze the data and provide specific, actionable upselling strategies.'
            ]);

            if ($aiResponse['status']) {
                $this->aiSuggestions = [
                    'generated_at' => now()->format('Y-m-d H:i:s'),
                    'suggestions' => $aiResponse['message']
                ];
                $this->notify(['type' => 'success', 'message' => 'AI suggestions generated successfully!']);
            } else {
                $this->notify(['type' => 'danger', 'message' => 'Failed to generate AI suggestions']);
            }

        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Error generating suggestions: ' . $e->getMessage()]);
        }
    }

    public function saveUpsellingRules()
    {
        try {
            if (!$this->ecommerceBot) {
                $this->ecommerceBot = EcommerceBot::create([
                    'tenant_id' => tenant_id(),
                    'is_enabled' => false,
                    'upselling_rules' => $this->upsellingRules,
                ]);
            } else {
                $this->ecommerceBot->update([
                    'upselling_rules' => $this->upsellingRules
                ]);
            }

            $this->notify(['type' => 'success', 'message' => 'Upselling rules saved successfully!']);
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Failed to save rules: ' . $e->getMessage()]);
        }
    }

    public function toggleUpselling()
    {
        $this->upsellingRules['enabled'] = !$this->upsellingRules['enabled'];
        $this->isEnabled = $this->upsellingRules['enabled'];
        $this->saveUpsellingRules();
    }

    public function updateRule($key, $value)
    {
        data_set($this->upsellingRules, $key, $value);
    }

    private function getDefaultUpsellingRules(): array
    {
        return [
            'enabled' => false,
            'cross_sell_products' => [],
            'minimum_order_value' => 100,
            'discount_percentage' => 10,
            'bundle_offers' => [],
            'seasonal_promotions' => [],
            'abandoned_cart_recovery' => [
                'enabled' => true,
                'reminder_intervals' => [1, 24, 72], // hours
                'discount_progression' => [5, 10, 15] // percentages
            ]
        ];
    }

    public function render()
    {
        $products = Product::where('tenant_id', tenant_id())
            ->where('status', 'active')
            ->orderBy('name')
            ->get();

        $recentOrders = Order::where('tenant_id', tenant_id())
            ->with('items')
            ->latest()
            ->limit(10)
            ->get();

        $stats = [
            'total_orders' => Order::where('tenant_id', tenant_id())->count(),
            'avg_order_value' => Order::where('tenant_id', tenant_id())->avg('total_amount') ?? 0,
            'conversion_rate' => 0, // You can calculate this based on your metrics
        ];

        return view('livewire.tenant.ecommerce.upsell-campaigns', [
            'products' => $products,
            'recentOrders' => $recentOrders,
            'stats' => $stats,
        ]);
    }
}
