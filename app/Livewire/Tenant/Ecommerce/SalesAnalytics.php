<?php

namespace App\Livewire\Tenant\Ecommerce;

use App\Models\Tenant\Order;
use App\Models\Tenant\Product;
use App\Models\Tenant\EcommerceBot;
use Livewire\Component;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalesAnalytics extends Component
{
    public $dateRange = '30_days';
    public $selectedMetric = 'revenue';
    public $compareWith = 'previous_period';
    
    // Analytics data
    public $analyticsData = [];
    public $chartData = [];
    public $topProducts = [];
    public $customerInsights = [];

    public function mount()
    {
        if (!checkPermission('tenant.ecommerce.analytics')) {
            $this->notify(['type' => 'danger', 'message' => t('access_denied_note')], true);
            return redirect()->to(tenant_route('tenant.dashboard'));
        }

        $this->loadAnalytics();
    }

    public function updatedDateRange()
    {
        $this->loadAnalytics();
    }

    public function updatedSelectedMetric()
    {
        $this->loadAnalytics();
    }

    public function loadAnalytics()
    {
        $dateRange = $this->getDateRange();
        
        $this->analyticsData = $this->calculateKPIs($dateRange);
        $this->chartData = $this->getChartData($dateRange);
        $this->topProducts = $this->getTopProducts($dateRange);
        $this->customerInsights = $this->getCustomerInsights($dateRange);
    }

    private function getDateRange(): array
    {
        $endDate = now();
        
        switch ($this->dateRange) {
            case '7_days':
                $startDate = now()->subDays(7);
                break;
            case '30_days':
                $startDate = now()->subDays(30);
                break;
            case '90_days':
                $startDate = now()->subDays(90);
                break;
            case '12_months':
                $startDate = now()->subMonths(12);
                break;
            default:
                $startDate = now()->subDays(30);
        }

        return [$startDate, $endDate];
    }

    private function calculateKPIs($dateRange): array
    {
        [$startDate, $endDate] = $dateRange;
        
        $orders = Order::where('tenant_id', tenant_id())
            ->whereBetween('created_at', [$startDate, $endDate]);

        $totalRevenue = $orders->sum('total_amount');
        $totalOrders = $orders->count();
        $avgOrderValue = $totalOrders > 0 ? $totalRevenue / $totalOrders : 0;
        
        $completedOrders = $orders->where('status', 'delivered')->count();
        $conversionRate = $totalOrders > 0 ? ($completedOrders / $totalOrders) * 100 : 0;

        // Previous period comparison
        $periodLength = $endDate->diffInDays($startDate);
        $prevStartDate = $startDate->copy()->subDays($periodLength);
        $prevEndDate = $startDate;
        
        $prevOrders = Order::where('tenant_id', tenant_id())
            ->whereBetween('created_at', [$prevStartDate, $prevEndDate]);
            
        $prevRevenue = $prevOrders->sum('total_amount');
        $revenueGrowth = $prevRevenue > 0 ? (($totalRevenue - $prevRevenue) / $prevRevenue) * 100 : 0;

        return [
            'total_revenue' => $totalRevenue,
            'total_orders' => $totalOrders,
            'avg_order_value' => $avgOrderValue,
            'conversion_rate' => $conversionRate,
            'revenue_growth' => $revenueGrowth,
            'period_label' => $this->getPeriodLabel(),
        ];
    }

    private function getChartData($dateRange): array
    {
        [$startDate, $endDate] = $dateRange;
        
        $groupBy = $this->getGroupByFormat();
        
        $data = Order::where('tenant_id', tenant_id())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                DB::raw($groupBy['select']),
                DB::raw('SUM(total_amount) as revenue'),
                DB::raw('COUNT(*) as orders')
            )
            ->groupBy(DB::raw($groupBy['group']))
            ->orderBy(DB::raw($groupBy['group']))
            ->get();

        return $data->map(function ($item) {
            return [
                'date' => $item->date,
                'revenue' => (float) $item->revenue,
                'orders' => (int) $item->orders,
            ];
        })->toArray();
    }

    private function getGroupByFormat(): array
    {
        switch ($this->dateRange) {
            case '7_days':
            case '30_days':
                return [
                    'select' => 'DATE(created_at) as date',
                    'group' => 'DATE(created_at)'
                ];
            case '90_days':
                return [
                    'select' => 'DATE_FORMAT(created_at, "%Y-%m-%d") as date',
                    'group' => 'DATE(created_at)'
                ];
            case '12_months':
                return [
                    'select' => 'DATE_FORMAT(created_at, "%Y-%m") as date',
                    'group' => 'YEAR(created_at), MONTH(created_at)'
                ];
            default:
                return [
                    'select' => 'DATE(created_at) as date',
                    'group' => 'DATE(created_at)'
                ];
        }
    }

    private function getTopProducts($dateRange): array
    {
        [$startDate, $endDate] = $dateRange;
        
        return DB::table('order_items')
            ->join('orders', 'order_items.order_id', '=', 'orders.id')
            ->where('orders.tenant_id', tenant_id())
            ->whereBetween('orders.created_at', [$startDate, $endDate])
            ->select(
                'order_items.product_name',
                DB::raw('SUM(order_items.quantity) as total_quantity'),
                DB::raw('SUM(order_items.total_amount) as total_revenue')
            )
            ->groupBy('order_items.product_name')
            ->orderBy('total_revenue', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }

    private function getCustomerInsights($dateRange): array
    {
        [$startDate, $endDate] = $dateRange;
        
        $customerData = Order::where('tenant_id', tenant_id())
            ->whereBetween('created_at', [$startDate, $endDate])
            ->select(
                'customer_phone',
                'customer_name',
                DB::raw('COUNT(*) as order_count'),
                DB::raw('SUM(total_amount) as total_spent'),
                DB::raw('AVG(total_amount) as avg_order_value')
            )
            ->groupBy('customer_phone', 'customer_name')
            ->orderBy('total_spent', 'desc')
            ->limit(10)
            ->get();

        return [
            'top_customers' => $customerData->toArray(),
            'repeat_customers' => $customerData->where('order_count', '>', 1)->count(),
            'new_customers' => $customerData->where('order_count', '=', 1)->count(),
        ];
    }

    private function getPeriodLabel(): string
    {
        switch ($this->dateRange) {
            case '7_days':
                return 'Last 7 Days';
            case '30_days':
                return 'Last 30 Days';
            case '90_days':
                return 'Last 90 Days';
            case '12_months':
                return 'Last 12 Months';
            default:
                return 'Last 30 Days';
        }
    }

    public function exportAnalytics()
    {
        $filename = 'sales_analytics_' . now()->format('Y-m-d_H-i-s') . '.csv';
        
        $callback = function() {
            $file = fopen('php://output', 'w');
            
            // KPIs Summary
            fputcsv($file, ['Sales Analytics Report']);
            fputcsv($file, ['Period', $this->analyticsData['period_label']]);
            fputcsv($file, ['Total Revenue', number_format($this->analyticsData['total_revenue'], 2)]);
            fputcsv($file, ['Total Orders', $this->analyticsData['total_orders']]);
            fputcsv($file, ['Average Order Value', number_format($this->analyticsData['avg_order_value'], 2)]);
            fputcsv($file, ['Conversion Rate', number_format($this->analyticsData['conversion_rate'], 2) . '%']);
            fputcsv($file, []);
            
            // Top Products
            fputcsv($file, ['Top Products']);
            fputcsv($file, ['Product Name', 'Quantity Sold', 'Revenue']);
            foreach ($this->topProducts as $product) {
                fputcsv($file, [
                    $product->product_name,
                    $product->total_quantity,
                    number_format($product->total_revenue, 2)
                ]);
            }
            
            fclose($file);
        };

        return response()->streamDownload($callback, $filename, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"$filename\"",
        ]);
    }

    public function render()
    {
        return view('livewire.tenant.ecommerce.sales-analytics');
    }
}
