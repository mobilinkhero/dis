<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\EcommerceConfiguration;
use App\Models\Tenant\Product;
use App\Models\Tenant\Order;
use App\Services\GoogleSheetsService;
use App\Services\EcommerceLogger;
use Livewire\Component;

class EcommerceDashboard extends Component
{
    public $config;
    public $isConfigured = false;
    public $stats = [];

    public function mount()
    {
        // Check permissions
        if (!checkPermission('tenant.ecommerce.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $this->loadConfiguration();
        $this->loadStats();
    }

    public function loadConfiguration()
    {
        $this->config = EcommerceConfiguration::where('tenant_id', tenant_id())->first();
        $this->isConfigured = $this->config && $this->config->isFullyConfigured();
    }

    public function loadStats()
    {
        $tenantId = tenant_id();
        
        $this->stats = [
            'total_products' => Product::where('tenant_id', $tenantId)->count(),
            'active_products' => Product::where('tenant_id', $tenantId)->where('status', 'active')->count(),
            'low_stock_products' => Product::where('tenant_id', $tenantId)
                ->whereRaw('stock_quantity <= low_stock_threshold')->count(),
            
            'total_orders' => Order::where('tenant_id', $tenantId)->count(),
            'pending_orders' => Order::where('tenant_id', $tenantId)->where('status', 'pending')->count(),
            'completed_orders' => Order::where('tenant_id', $tenantId)->where('status', 'delivered')->count(),
            
            'total_revenue' => Order::where('tenant_id', $tenantId)
                ->where('payment_status', 'paid')->sum('total_amount'),
            'monthly_revenue' => Order::where('tenant_id', $tenantId)
                ->where('payment_status', 'paid')
                ->whereBetween('created_at', [now()->startOfMonth(), now()])
                ->sum('total_amount'),
            
            'recent_orders' => Order::where('tenant_id', $tenantId)
                ->with('contact')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get(),
        ];
    }

    public function redirectToSetup()
    {
        return redirect()->to(tenant_route('tenant.ecommerce.setup'));
    }

    public function syncNow()
    {
        if (!$this->isConfigured) {
            EcommerceLogger::warning('Sync attempted without configuration', [
                'tenant_id' => tenant_id(),
                'config_exists' => !is_null($this->config)
            ]);
            $this->notify(['type' => 'danger', 'message' => 'E-commerce not configured yet']);
            return;
        }

        try {
            EcommerceLogger::info('Manual sync started from dashboard', [
                'tenant_id' => tenant_id(),
                'user_id' => auth()->id()
            ]);

            $sheetsService = new GoogleSheetsService();
            $result = $sheetsService->syncProducts($this->config);
            
            if ($result['success']) {
                EcommerceLogger::sheetsSync('manual_products', 'success', $result);
                $this->notify(['type' => 'success', 'message' => $result['message']]);
                $this->loadStats(); // Refresh stats
            } else {
                EcommerceLogger::sheetsSync('manual_products', 'failed', $result);
                $this->notify(['type' => 'danger', 'message' => $result['message']]);
            }
        } catch (\Exception $e) {
            EcommerceLogger::error('Manual sync failed', [
                'tenant_id' => tenant_id(),
                'exception' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);
            $this->notify(['type' => 'danger', 'message' => 'Sync failed: ' . $e->getMessage()]);
        }
    }

    public function render()
    {
        return view('livewire.tenant.ecommerce.dashboard', [
            'config' => $this->config,
            'isConfigured' => $this->isConfigured,
            'stats' => $this->stats,
        ]);
    }
}
