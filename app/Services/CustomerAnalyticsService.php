<?php

namespace App\Services;

use App\Models\Tenant\Order;
use App\Models\Tenant\CustomerProfile;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CustomerAnalyticsService
{
    protected $tenantId;

    public function __construct($tenantId = null)
    {
        $this->tenantId = $tenantId ?? tenant_id();
    }

    /**
     * Get customer purchase history analytics
     */
    public function getCustomerPurchaseHistory($contactId): array
    {
        $orders = Order::where('tenant_id', $this->tenantId)
            ->where('contact_id', $contactId)
            ->orderBy('created_at', 'desc')
            ->get();

        return [
            'total_orders' => $orders->count(),
            'total_spent' => $orders->sum('total_amount'),
            'average_order_value' => $orders->avg('total_amount') ?? 0,
            'last_order_date' => $orders->first()?->created_at,
            'orders' => $orders->take(10)->toArray(),
        ];
    }

    /**
     * Calculate customer lifetime value
     */
    public function calculateLifetimeValue($contactId): float
    {
        return (float) Order::where('tenant_id', $this->tenantId)
            ->where('contact_id', $contactId)
            ->whereIn('order_status', [Order::STATUS_CONFIRMED, Order::STATUS_DELIVERED])
            ->sum('total_amount');
    }

    /**
     * Get customer engagement metrics
     */
    public function getEngagementMetrics($contactId): array
    {
        $profile = CustomerProfile::where('tenant_id', $this->tenantId)
            ->where('contact_id', $contactId)
            ->first();

        if (!$profile) {
            return [
                'engagement_score' => 0,
                'last_interaction' => null,
                'interaction_frequency' => 'unknown',
            ];
        }

        return [
            'engagement_score' => $profile->engagement_score ?? 0,
            'last_interaction' => $profile->last_interaction_at,
            'interaction_frequency' => $this->calculateInteractionFrequency($contactId),
            'preferred_channels' => $profile->communication_preferences ?? [],
        ];
    }

    /**
     * Calculate interaction frequency
     */
    protected function calculateInteractionFrequency($contactId): string
    {
        $orders = Order::where('tenant_id', $this->tenantId)
            ->where('contact_id', $contactId)
            ->where('created_at', '>=', Carbon::now()->subMonths(3))
            ->count();

        if ($orders >= 5) return 'high';
        if ($orders >= 2) return 'medium';
        if ($orders >= 1) return 'low';
        return 'inactive';
    }

    /**
     * Get customer segment
     */
    public function getCustomerSegment($contactId): string
    {
        $ltv = $this->calculateLifetimeValue($contactId);
        $orderCount = Order::where('tenant_id', $this->tenantId)
            ->where('contact_id', $contactId)
            ->count();

        if ($ltv > 1000 && $orderCount > 5) return 'vip';
        if ($ltv > 500 && $orderCount > 3) return 'loyal';
        if ($orderCount > 1) return 'returning';
        if ($orderCount === 1) return 'new';
        return 'prospect';
    }

    /**
     * Get product preferences based on order history
     */
    public function getProductPreferences($contactId): array
    {
        $orders = Order::where('tenant_id', $this->tenantId)
            ->where('contact_id', $contactId)
            ->get();

        $categories = [];
        $products = [];

        foreach ($orders as $order) {
            $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
            
            if (is_array($items)) {
                foreach ($items as $item) {
                    $productName = $item['name'] ?? $item['product_name'] ?? 'Unknown';
                    $category = $item['category'] ?? 'Uncategorized';
                    
                    $products[$productName] = ($products[$productName] ?? 0) + 1;
                    $categories[$category] = ($categories[$category] ?? 0) + 1;
                }
            }
        }

        arsort($products);
        arsort($categories);

        return [
            'favorite_products' => array_keys(array_slice($products, 0, 5)),
            'favorite_categories' => array_keys(array_slice($categories, 0, 3)),
            'purchase_patterns' => $this->analyzePurchasePatterns($orders),
        ];
    }

    /**
     * Analyze purchase patterns
     */
    protected function analyzePurchasePatterns($orders): array
    {
        if ($orders->isEmpty()) {
            return [
                'average_items_per_order' => 0,
                'preferred_time' => 'unknown',
                'price_sensitivity' => 'unknown',
            ];
        }

        $totalItems = 0;
        $hourCounts = array_fill(0, 24, 0);

        foreach ($orders as $order) {
            $items = is_string($order->items) ? json_decode($order->items, true) : $order->items;
            $totalItems += is_array($items) ? count($items) : 0;
            
            if ($order->created_at) {
                $hour = $order->created_at->hour;
                $hourCounts[$hour]++;
            }
        }

        $peakHour = array_keys($hourCounts, max($hourCounts))[0];
        $preferredTime = $this->getTimeOfDay($peakHour);

        $avgOrderValue = $orders->avg('total_amount');
        $priceSensitivity = $avgOrderValue < 50 ? 'high' : ($avgOrderValue < 200 ? 'medium' : 'low');

        return [
            'average_items_per_order' => $orders->count() > 0 ? round($totalItems / $orders->count(), 1) : 0,
            'preferred_time' => $preferredTime,
            'price_sensitivity' => $priceSensitivity,
        ];
    }

    /**
     * Get time of day from hour
     */
    protected function getTimeOfDay($hour): string
    {
        if ($hour >= 5 && $hour < 12) return 'morning';
        if ($hour >= 12 && $hour < 17) return 'afternoon';
        if ($hour >= 17 && $hour < 21) return 'evening';
        return 'night';
    }

    /**
     * Get churn risk score
     */
    public function getChurnRisk($contactId): array
    {
        $lastOrder = Order::where('tenant_id', $this->tenantId)
            ->where('contact_id', $contactId)
            ->latest()
            ->first();

        if (!$lastOrder) {
            return [
                'risk_level' => 'unknown',
                'score' => 0,
                'reason' => 'No order history',
            ];
        }

        $daysSinceLastOrder = Carbon::now()->diffInDays($lastOrder->created_at);
        
        $riskScore = 0;
        $reasons = [];

        // Time-based risk
        if ($daysSinceLastOrder > 90) {
            $riskScore += 40;
            $reasons[] = 'Inactive for 90+ days';
        } elseif ($daysSinceLastOrder > 60) {
            $riskScore += 25;
            $reasons[] = 'Inactive for 60+ days';
        } elseif ($daysSinceLastOrder > 30) {
            $riskScore += 10;
            $reasons[] = 'Inactive for 30+ days';
        }

        // Order frequency decline
        $recentOrders = Order::where('tenant_id', $this->tenantId)
            ->where('contact_id', $contactId)
            ->where('created_at', '>=', Carbon::now()->subMonths(6))
            ->count();

        if ($recentOrders === 0) {
            $riskScore += 30;
            $reasons[] = 'No recent orders';
        }

        // Engagement decline
        $profile = CustomerProfile::where('tenant_id', $this->tenantId)
            ->where('contact_id', $contactId)
            ->first();

        if ($profile && $profile->engagement_score < 30) {
            $riskScore += 20;
            $reasons[] = 'Low engagement score';
        }

        $riskLevel = 'low';
        if ($riskScore >= 70) $riskLevel = 'critical';
        elseif ($riskScore >= 50) $riskLevel = 'high';
        elseif ($riskScore >= 30) $riskLevel = 'medium';

        return [
            'risk_level' => $riskLevel,
            'score' => min($riskScore, 100),
            'reasons' => $reasons,
            'days_since_last_order' => $daysSinceLastOrder,
        ];
    }

    /**
     * Get comprehensive customer analytics
     */
    public function getComprehensiveAnalytics($contactId): array
    {
        return [
            'purchase_history' => $this->getCustomerPurchaseHistory($contactId),
            'lifetime_value' => $this->calculateLifetimeValue($contactId),
            'engagement' => $this->getEngagementMetrics($contactId),
            'segment' => $this->getCustomerSegment($contactId),
            'preferences' => $this->getProductPreferences($contactId),
            'churn_risk' => $this->getChurnRisk($contactId),
        ];
    }
}
