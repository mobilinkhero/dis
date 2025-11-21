<?php

namespace App\Services;

use App\Models\Tenant\Product;
use App\Models\Tenant\EcommerceOrder;
use App\Models\Tenant\CustomerProfile;
use App\Models\Tenant\CustomerInteraction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * REAL-TIME INTELLIGENCE SERVICE
 * Live business intelligence and market insights
 * 
 * Features:
 * - Real-time sales analytics
 * - Live inventory monitoring
 * - Market trend analysis
 * - Competitive intelligence
 * - Dynamic pricing recommendations
 * - Live customer behavior tracking
 * - Performance KPI monitoring
 * - Alert system for business events
 */
class RealTimeIntelligenceService
{
    protected $tenantId;

    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * GET CURRENT BUSINESS INSIGHTS
     * Real-time snapshot of business performance
     */
    public function getCurrentInsights($contact = null, string $message = ''): array
    {
        return [
            'timestamp' => now()->toISOString(),
            'business_metrics' => $this->getLiveBusinessMetrics(),
            'trending_products' => $this->getTrendingProducts(),
            'inventory_alerts' => $this->getInventoryAlerts(),
            'customer_activity' => $this->getLiveCustomerActivity(),
            'sales_performance' => $this->getLiveSalesPerformance(),
            'market_trends' => $this->getMarketTrends(),
            'competitive_insights' => $this->getCompetitiveInsights(),
            'relevant_promotions' => $this->getRelevantPromotions($contact),
            'performance_alerts' => $this->getPerformanceAlerts()
        ];
    }

    /**
     * LIVE BUSINESS METRICS
     * Real-time KPIs and performance indicators
     */
    protected function getLiveBusinessMetrics(): array
    {
        return Cache::remember("live_metrics_{$this->tenantId}", 300, function() {
            $today = Carbon::today();
            $yesterday = Carbon::yesterday();
            $thisWeek = Carbon::now()->startOfWeek();
            $lastWeek = Carbon::now()->subWeek()->startOfWeek();
            $thisMonth = Carbon::now()->startOfMonth();

            // Today's metrics
            $todayOrders = EcommerceOrder::where('tenant_id', $this->tenantId)
                ->whereDate('created_at', $today)
                ->count();
            
            $todayRevenue = EcommerceOrder::where('tenant_id', $this->tenantId)
                ->whereDate('created_at', $today)
                ->sum('total_amount');

            // Yesterday's metrics for comparison
            $yesterdayOrders = EcommerceOrder::where('tenant_id', $this->tenantId)
                ->whereDate('created_at', $yesterday)
                ->count();
            
            $yesterdayRevenue = EcommerceOrder::where('tenant_id', $this->tenantId)
                ->whereDate('created_at', $yesterday)
                ->sum('total_amount');

            // Week metrics
            $thisWeekOrders = EcommerceOrder::where('tenant_id', $this->tenantId)
                ->where('created_at', '>=', $thisWeek)
                ->count();
            
            $lastWeekOrders = EcommerceOrder::where('tenant_id', $this->tenantId)
                ->whereBetween('created_at', [$lastWeek, $thisWeek])
                ->count();

            // Live customer activity
            $activeCustomersLast24h = CustomerInteraction::where('tenant_id', $this->tenantId)
                ->where('created_at', '>', now()->subDay())
                ->distinct('contact_id')
                ->count();

            return [
                'today' => [
                    'orders' => $todayOrders,
                    'revenue' => $todayRevenue,
                    'avg_order_value' => $todayOrders > 0 ? $todayRevenue / $todayOrders : 0,
                    'vs_yesterday' => [
                        'orders' => $this->calculateGrowthRate($todayOrders, $yesterdayOrders),
                        'revenue' => $this->calculateGrowthRate($todayRevenue, $yesterdayRevenue)
                    ]
                ],
                'this_week' => [
                    'orders' => $thisWeekOrders,
                    'vs_last_week' => $this->calculateGrowthRate($thisWeekOrders, $lastWeekOrders)
                ],
                'live_activity' => [
                    'active_customers_24h' => $activeCustomersLast24h,
                    'current_hour_interactions' => $this->getCurrentHourInteractions(),
                    'conversion_rate_today' => $this->calculateTodayConversionRate()
                ]
            ];
        });
    }

    /**
     * TRENDING PRODUCTS
     * Real-time product performance and trends
     */
    protected function getTrendingProducts(): array
    {
        return Cache::remember("trending_products_{$this->tenantId}", 600, function() {
            // Products with highest order velocity in last 24 hours
            $trending = DB::table('ecommerce_orders')
                ->join('products', function($join) {
                    $join->on(DB::raw("JSON_EXTRACT(ecommerce_orders.order_items, '$[0].product_id')"), '=', 'products.id');
                })
                ->where('ecommerce_orders.tenant_id', $this->tenantId)
                ->where('ecommerce_orders.created_at', '>', now()->subDay())
                ->select(
                    'products.id',
                    'products.name',
                    'products.price',
                    'products.category',
                    DB::raw('COUNT(*) as order_count'),
                    DB::raw('SUM(JSON_EXTRACT(ecommerce_orders.order_items, "$[0].quantity")) as total_quantity')
                )
                ->groupBy('products.id', 'products.name', 'products.price', 'products.category')
                ->orderByDesc('order_count')
                ->limit(10)
                ->get();

            return $trending->map(function($item) {
                return [
                    'product_id' => $item->id,
                    'name' => $item->name,
                    'price' => $item->price,
                    'category' => $item->category,
                    'trend_score' => $item->order_count * 10 + $item->total_quantity,
                    'velocity' => $item->order_count . ' orders in 24h',
                    'status' => $this->getTrendStatus($item->order_count)
                ];
            })->toArray();
        });
    }

    /**
     * INVENTORY ALERTS
     * Real-time inventory monitoring and alerts
     */
    protected function getInventoryAlerts(): array
    {
        return Cache::remember("inventory_alerts_{$this->tenantId}", 300, function() {
            $alerts = [];

            // Low stock alerts
            $lowStockProducts = Product::where('tenant_id', $this->tenantId)
                ->where('status', 'active')
                ->where('stock_quantity', '<=', 10)
                ->where('stock_quantity', '>', 0)
                ->get();

            foreach ($lowStockProducts as $product) {
                $alerts[] = [
                    'type' => 'low_stock',
                    'severity' => $product->stock_quantity <= 5 ? 'critical' : 'warning',
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'current_stock' => $product->stock_quantity,
                    'message' => "{$product->name} has only {$product->stock_quantity} units left"
                ];
            }

            // Out of stock alerts
            $outOfStockCount = Product::where('tenant_id', $this->tenantId)
                ->where('status', 'active')
                ->where('stock_quantity', 0)
                ->count();

            if ($outOfStockCount > 0) {
                $alerts[] = [
                    'type' => 'out_of_stock',
                    'severity' => 'critical',
                    'count' => $outOfStockCount,
                    'message' => "{$outOfStockCount} products are out of stock"
                ];
            }

            // Fast-moving products (potential stockout risk)
            $fastMoving = $this->identifyFastMovingProducts();
            foreach ($fastMoving as $product) {
                $alerts[] = [
                    'type' => 'fast_moving',
                    'severity' => 'info',
                    'product_id' => $product['id'],
                    'product_name' => $product['name'],
                    'velocity' => $product['velocity'],
                    'message' => "{$product['name']} is selling fast - consider restocking"
                ];
            }

            return $alerts;
        });
    }

    /**
     * LIVE CUSTOMER ACTIVITY
     * Real-time customer behavior and engagement
     */
    protected function getLiveCustomerActivity(): array
    {
        return Cache::remember("live_customer_activity_{$this->tenantId}", 180, function() {
            $last24h = now()->subDay();
            $lastHour = now()->subHour();

            // Customer engagement metrics
            $engagementMetrics = [
                'total_interactions_24h' => CustomerInteraction::where('tenant_id', $this->tenantId)
                    ->where('created_at', '>', $last24h)
                    ->count(),
                
                'unique_customers_24h' => CustomerInteraction::where('tenant_id', $this->tenantId)
                    ->where('created_at', '>', $last24h)
                    ->distinct('contact_id')
                    ->count(),
                
                'interactions_last_hour' => CustomerInteraction::where('tenant_id', $this->tenantId)
                    ->where('created_at', '>', $lastHour)
                    ->count(),
                
                'avg_session_length' => $this->calculateAverageSessionLength(),
                
                'top_interaction_types' => $this->getTopInteractionTypes()
            ];

            // Customer sentiment distribution
            $sentimentDistribution = $this->getSentimentDistribution();

            // Most active customers
            $activeCustomers = $this->getMostActiveCustomers();

            return [
                'engagement_metrics' => $engagementMetrics,
                'sentiment_distribution' => $sentimentDistribution,
                'active_customers' => $activeCustomers,
                'peak_hours' => $this->identifyPeakHours()
            ];
        });
    }

    /**
     * LIVE SALES PERFORMANCE
     * Real-time sales analytics and performance
     */
    protected function getLiveSalesPerformance(): array
    {
        return Cache::remember("live_sales_performance_{$this->tenantId}", 300, function() {
            $today = Carbon::today();
            $thisWeek = Carbon::now()->startOfWeek();
            $thisMonth = Carbon::now()->startOfMonth();

            return [
                'current_performance' => [
                    'today_sales' => $this->getTodaySalesData(),
                    'week_progress' => $this->getWeekProgress(),
                    'month_progress' => $this->getMonthProgress()
                ],
                'conversion_metrics' => [
                    'overall_conversion_rate' => $this->calculateOverallConversionRate(),
                    'channel_performance' => $this->getChannelPerformance(),
                    'funnel_analysis' => $this->getFunnelAnalysis()
                ],
                'revenue_streams' => [
                    'by_category' => $this->getRevenueByCategory(),
                    'by_customer_tier' => $this->getRevenueByCustomerTier(),
                    'by_hour' => $this->getHourlyRevenue()
                ]
            ];
        });
    }

    /**
     * MARKET TRENDS ANALYSIS
     * Current market conditions and trends
     */
    protected function getMarketTrends(): array
    {
        return [
            'seasonal_trends' => $this->getSeasonalTrends(),
            'category_trends' => $this->getCategoryTrends(),
            'price_trends' => $this->getPriceTrends(),
            'demand_forecast' => $this->getDemandForecast()
        ];
    }

    /**
     * COMPETITIVE INSIGHTS
     * Market positioning and competitive analysis
     */
    protected function getCompetitiveInsights(): array
    {
        // This would integrate with market research APIs
        // For now, return simulated competitive intelligence
        return [
            'market_position' => 'strong',
            'price_competitiveness' => 85, // Score out of 100
            'unique_value_props' => ['AI-powered assistance', 'Personalized recommendations'],
            'opportunity_areas' => ['Expand product range', 'Improve delivery speed'],
            'market_share_estimate' => '12%'
        ];
    }

    /**
     * PERFORMANCE ALERTS
     * Real-time alerts for business performance
     */
    protected function getPerformanceAlerts(): array
    {
        $alerts = [];

        // Revenue alerts
        $todayRevenue = EcommerceOrder::where('tenant_id', $this->tenantId)
            ->whereDate('created_at', today())
            ->sum('total_amount');
        
        $avgDailyRevenue = $this->getAverageDailyRevenue();
        
        if ($todayRevenue < $avgDailyRevenue * 0.7) {
            $alerts[] = [
                'type' => 'revenue_below_average',
                'severity' => 'warning',
                'message' => 'Today\'s revenue is 30% below average',
                'current' => $todayRevenue,
                'average' => $avgDailyRevenue
            ];
        }

        // Conversion rate alerts
        $currentConversionRate = $this->calculateTodayConversionRate();
        $avgConversionRate = $this->getAverageConversionRate();
        
        if ($currentConversionRate < $avgConversionRate * 0.8) {
            $alerts[] = [
                'type' => 'low_conversion_rate',
                'severity' => 'warning',
                'message' => 'Conversion rate is below normal',
                'current' => $currentConversionRate,
                'average' => $avgConversionRate
            ];
        }

        return $alerts;
    }

    /**
     * Helper methods for calculations and data retrieval
     */
    protected function calculateGrowthRate($current, $previous): float
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return round((($current - $previous) / $previous) * 100, 2);
    }

    protected function getTrendStatus(int $orderCount): string
    {
        if ($orderCount >= 10) return 'hot';
        if ($orderCount >= 5) return 'trending';
        if ($orderCount >= 2) return 'rising';
        return 'stable';
    }

    protected function getCurrentHourInteractions(): int
    {
        return CustomerInteraction::where('tenant_id', $this->tenantId)
            ->where('created_at', '>', now()->startOfHour())
            ->count();
    }

    protected function calculateTodayConversionRate(): float
    {
        $todayInteractions = CustomerInteraction::where('tenant_id', $this->tenantId)
            ->whereDate('created_at', today())
            ->distinct('contact_id')
            ->count();
        
        $todayOrders = EcommerceOrder::where('tenant_id', $this->tenantId)
            ->whereDate('created_at', today())
            ->count();

        return $todayInteractions > 0 ? round(($todayOrders / $todayInteractions) * 100, 2) : 0;
    }

    // Additional helper methods would be implemented here...
}
