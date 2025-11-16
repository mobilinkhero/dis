<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\EcommerceBot;
use App\Models\Tenant\Product;
use App\Models\Tenant\Order;
use App\Services\GoogleSheetsEcommerceService;
use Livewire\Component;
use Illuminate\Support\Facades\DB;

class EcommerceDashboard extends Component
{
    public ?EcommerceBot $ecommerceBot = null;
    public bool $isConfigured = false;
    public array $stats = [];
    public bool $showDisconnectConfirm = false;

    public function mount()
    {
        // Check permissions
        if (!checkPermission('tenant.ecommerce.setup')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $this->loadEcommerceData();
    }

    public function loadEcommerceData()
    {
        $this->ecommerceBot = EcommerceBot::where('tenant_id', tenant_id())->first();
        
        if ($this->ecommerceBot && $this->ecommerceBot->is_enabled && $this->ecommerceBot->google_sheets_product_url) {
            $this->isConfigured = true;
            $this->loadStats();
        } else {
            $this->isConfigured = false;
        }
    }

    public function loadStats()
    {
        if (!$this->ecommerceBot) return;

        $this->stats = [
            'products_total' => Product::where('tenant_id', tenant_id())->count(),
            'products_active' => Product::where('tenant_id', tenant_id())->where('status', 'active')->count(),
            'products_low_stock' => Product::where('tenant_id', tenant_id())->where('stock_quantity', '<=', 5)->count(),
            'orders_total' => Order::where('tenant_id', tenant_id())->count(),
            'orders_pending' => Order::where('tenant_id', tenant_id())->where('status', 'pending')->count(),
            'orders_today' => Order::where('tenant_id', tenant_id())->whereDate('created_at', today())->count(),
            'revenue_total' => Order::where('tenant_id', tenant_id())->where('payment_status', 'paid')->sum('total_amount'),
            'revenue_today' => Order::where('tenant_id', tenant_id())
                ->where('payment_status', 'paid')
                ->whereDate('created_at', today())
                ->sum('total_amount'),
        ];
    }

    public function syncProducts()
    {
        if (!$this->ecommerceBot || !$this->ecommerceBot->isReady()) {
            $this->notify(['type' => 'danger', 'message' => 'E-commerce bot is not configured properly']);
            return;
        }

        try {
            $sheetsService = new GoogleSheetsEcommerceService($this->ecommerceBot);
            $result = $sheetsService->syncProductsFromSheets();

            if ($result['success']) {
                $this->notify(['type' => 'success', 'message' => $result['message']]);
                $this->loadStats(); // Refresh stats after sync
            } else {
                $this->notify(['type' => 'danger', 'message' => $result['message']]);
            }
        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    public function showDisconnectDialog()
    {
        $this->showDisconnectConfirm = true;
    }

    public function cancelDisconnect()
    {
        $this->showDisconnectConfirm = false;
    }

    public function disconnectEcommerce()
    {
        if (!$this->ecommerceBot) {
            $this->notify(['type' => 'danger', 'message' => 'No e-commerce bot to disconnect']);
            return;
        }

        try {
            DB::transaction(function () {
                // Disable the bot but keep the data for potential re-activation
                $this->ecommerceBot->update([
                    'is_enabled' => false,
                    'google_sheets_product_url' => null,
                    'google_sheets_order_url' => null,
                    'sheets_product_id' => null,
                    'sheets_order_id' => null,
                    'last_sync_at' => null,
                ]);
            });

            $this->showDisconnectConfirm = false;
            $this->loadEcommerceData(); // Refresh the view
            $this->notify(['type' => 'success', 'message' => 'E-commerce bot disconnected successfully. Products and orders are preserved.']);

        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => 'Failed to disconnect: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.tenant.ecommerce.ecommerce-dashboard');
    }
}
