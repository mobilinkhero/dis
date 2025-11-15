<?php

namespace App\Livewire\Tenant\ProductSales;

use App\Models\Tenant\Contact;
use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Services\FeatureService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Component;
use Livewire\WithPagination;

class ProductSalesDashboard extends Component
{
    use WithPagination;

    public $selectedPeriod = '30';
    public $searchTerm = '';
    public $selectedStatus = '';
    public $showOrderModal = false;
    public $selectedOrder = null;

    // Analytics data
    public $totalOrders = 0;
    public $totalRevenue = 0;
    public $conversionRate = 0;
    public $averageOrderValue = 0;
    public $topProducts = [];
    public $salesChart = [];
    public $orderStatuses = [];

    protected $featureService;

    protected $listeners = [
        'refreshDashboard' => 'refreshData',
        'orderUpdated' => 'refreshData'
    ];

    public function boot(FeatureService $featureService)
    {
        $this->featureService = $featureService;
    }

    public function mount()
    {
        // Check permissions
        if (!checkPermission('tenant.product_sales.view')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        // Check feature access
        if (!$this->featureService->hasFeature('product_sales')) {
            $this->notify(['type' => 'warning', 'message' => t('feature_not_available_in_current_plan')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $this->refreshData();
    }

    public function updatedSelectedPeriod()
    {
        $this->refreshData();
    }

    public function updatedSearchTerm()
    {
        $this->resetPage();
    }

    public function updatedSelectedStatus()
    {
        $this->resetPage();
    }

    public function refreshData()
    {
        $this->calculateAnalytics();
        $this->loadTopProducts();
        $this->loadSalesChart();
        $this->loadOrderStatuses();
    }

    public function calculateAnalytics()
    {
        $startDate = Carbon::now()->subDays($this->selectedPeriod);
        
        // Total orders
        $this->totalOrders = Order::where('tenant_id', tenant_id())
            ->where('created_at', '>=', $startDate)
            ->count();

        // Total revenue (excluding cancelled orders)
        $this->totalRevenue = Order::where('tenant_id', tenant_id())
            ->where('created_at', '>=', $startDate)
            ->where('status', '!=', 'cancelled')
            ->sum('total_amount');

        // Average order value
        $this->averageOrderValue = $this->totalOrders > 0 
            ? round($this->totalRevenue / $this->totalOrders, 2) 
            : 0;

        // Conversion rate
        $totalContacts = Contact::where('tenant_id', tenant_id())
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $orderContacts = Order::where('tenant_id', tenant_id())
            ->where('created_at', '>=', $startDate)
            ->distinct('contact_id')
            ->count();
        
        $this->conversionRate = $totalContacts > 0 
            ? round(($orderContacts / $totalContacts) * 100, 2) 
            : 0;
    }

    public function loadTopProducts()
    {
        $startDate = Carbon::now()->subDays($this->selectedPeriod);
        
        $this->topProducts = Order::where('tenant_id', tenant_id())
            ->where('created_at', '>=', $startDate)
            ->where('status', '!=', 'cancelled')
            ->get()
            ->flatMap(function ($order) {
                return collect(json_decode($order->items, true));
            })
            ->groupBy('product_id')
            ->map(function ($items) {
                return [
                    'product_name' => $items->first()['product_name'],
                    'total_quantity' => $items->sum('quantity'),
                    'total_revenue' => $items->sum('subtotal')
                ];
            })
            ->sortByDesc('total_revenue')
            ->take(5)
            ->values()
            ->toArray();
    }

    public function loadSalesChart()
    {
        $startDate = Carbon::now()->subDays($this->selectedPeriod);
        
        $this->salesChart = Order::where('tenant_id', tenant_id())
            ->where('created_at', '>=', $startDate)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => Carbon::parse($item->date)->format('M d'),
                    'orders' => $item->orders,
                    'revenue' => round($item->revenue, 2)
                ];
            })
            ->toArray();
    }

    public function loadOrderStatuses()
    {
        $startDate = Carbon::now()->subDays($this->selectedPeriod);
        
        $this->orderStatuses = Order::where('tenant_id', tenant_id())
            ->where('created_at', '>=', $startDate)
            ->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();
    }

    public function viewOrder($orderId)
    {
        $this->selectedOrder = Order::where('tenant_id', tenant_id())
            ->with('contact')
            ->find($orderId);
        
        if ($this->selectedOrder) {
            $this->showOrderModal = true;
        }
    }

    public function closeOrderModal()
    {
        $this->showOrderModal = false;
        $this->selectedOrder = null;
    }

    public function updateOrderStatus($orderId, $status)
    {
        try {
            $order = Order::where('tenant_id', tenant_id())->findOrFail($orderId);
            
            $order->update([
                'status' => $status,
                'status_updated_at' => now()
            ]);

            $this->notify(['type' => 'success', 'message' => t('order_status_updated_successfully')]);
            $this->refreshData();
            $this->emit('orderUpdated');

        } catch (\Exception $e) {
            $this->notify(['type' => 'danger', 'message' => t('failed_to_update_order_status')]);
        }
    }

    public function exportOrders()
    {
        // Implementation for exporting orders to CSV/Excel
        $this->notify(['type' => 'info', 'message' => t('export_feature_coming_soon')]);
    }

    public function render()
    {
        $orders = Order::where('tenant_id', tenant_id())
            ->with('contact')
            ->when($this->searchTerm, function ($query) {
                $query->where(function ($q) {
                    $q->where('order_number', 'like', '%' . $this->searchTerm . '%')
                      ->orWhereHas('contact', function ($contactQuery) {
                          $contactQuery->where('name', 'like', '%' . $this->searchTerm . '%')
                                      ->orWhere('phone', 'like', '%' . $this->searchTerm . '%');
                      });
                });
            })
            ->when($this->selectedStatus, function ($query) {
                $query->where('status', $this->selectedStatus);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return view('livewire.tenant.product-sales.product-sales-dashboard', [
            'orders' => $orders,
            'recentOrders' => Order::where('tenant_id', tenant_id())
                ->with('contact')
                ->orderBy('created_at', 'desc')
                ->take(5)
                ->get()
        ]);
    }

    private function notify($notification, $redirect = false)
    {
        session()->flash('notification', $notification);
        if ($redirect) {
            return redirect()->to(tenant_route('tenant.dashboard'));
        }
    }
}
