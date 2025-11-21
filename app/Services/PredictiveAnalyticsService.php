<?php

namespace App\Services;

use App\Models\Tenant\CustomerProfile;
use App\Models\Tenant\EcommerceOrder;
use App\Models\Tenant\Product;
use App\Models\Tenant\CustomerInteraction;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

/**
 * PREDICTIVE ANALYTICS SERVICE
 * Advanced AI-powered predictive analytics for e-commerce
 * 
 * Features:
 * - Customer Lifetime Value prediction
 * - Churn risk analysis
 * - Purchase behavior forecasting
 * - Inventory demand prediction
 * - Price optimization
 * - Market trend analysis
 * - Seasonal demand forecasting
 * - Customer segment prediction
 */
class PredictiveAnalyticsService
{
    protected $tenantId;

    public function __construct($tenantId)
    {
        $this->tenantId = $tenantId;
    }

    /**
     * COMPREHENSIVE CUSTOMER ANALYSIS
     * Analyzes customer for all predictive insights
     */
    public function analyzeCustomer($contact, string $message = '', array $context = []): array
    {
        $cacheKey = "predictive_analysis_{$this->tenantId}_{$contact->id}_" . md5($message);
        
        return Cache::remember($cacheKey, 1800, function() use ($contact, $message, $context) {
            $profile = CustomerProfile::where('tenant_id', $this->tenantId)
                ->where('contact_id', $contact->id)
                ->first();

            if (!$profile) {
                return $this->getNewCustomerPredictions($contact, $message, $context);
            }

            return [
                'customer_id' => $contact->id,
                'analysis_timestamp' => now()->toISOString(),
                'lifetime_value_prediction' => $this->calculateLifetimeValue($profile),
                'churn_risk_analysis' => $this->calculateChurnRisk($profile),
                'next_purchase_prediction' => $this->predictNextPurchase($profile),
                'purchase_intent_score' => $this->calculatePurchaseIntentScore($profile, $message),
                'price_sensitivity_analysis' => $this->analyzePriceSensitivityAdvanced($profile),
                'predicted_categories' => $this->predictPreferredCategories($profile),
                'seasonal_predictions' => $this->predictSeasonalBehavior($profile),
                'upsell_opportunities' => $this->identifyUpsellOpportunities($profile),
                'cross_sell_potential' => $this->identifyCrossSellPotential($profile),
                'optimal_contact_time' => $this->predictOptimalContactTime($profile),
                'preferred_communication_style' => $this->predictCommunicationPreferences($profile),
                'budget_prediction' => $this->predictCustomerBudget($profile),
                'loyalty_score' => $this->calculateLoyaltyScore($profile),
                'influence_score' => $this->calculateInfluenceScore($profile)
            ];
        });
    }

    /**
     * CUSTOMER LIFETIME VALUE PREDICTION
     * Advanced CLV calculation using multiple factors
     */
    public function calculateLifetimeValue(CustomerProfile $profile): array
    {
        // Get historical data
        $orders = EcommerceOrder::where('tenant_id', $this->tenantId)
            ->where('contact_id', $profile->contact_id)
            ->orderBy('created_at')
            ->get();

        if ($orders->isEmpty()) {
            return $this->predictNewCustomerLTV($profile);
        }

        // Calculate key metrics
        $totalSpent = $orders->sum('total_amount');
        $orderCount = $orders->count();
        $avgOrderValue = $totalSpent / $orderCount;
        
        // Calculate purchase frequency (orders per month)
        $firstOrder = $orders->first()->created_at;
        $lastOrder = $orders->last()->created_at;
        $monthsActive = max(1, Carbon::parse($firstOrder)->diffInMonths($lastOrder));
        $purchaseFrequency = $orderCount / $monthsActive;

        // Predict future behavior
        $predictedMonthlySpend = $avgOrderValue * $purchaseFrequency;
        $churnRisk = $this->calculateChurnRisk($profile);
        $retentionProbability = 1 - ($churnRisk['probability'] / 100);
        
        // Calculate CLV with different timeframes
        $clv6Months = $predictedMonthlySpend * 6 * $retentionProbability;
        $clv1Year = $predictedMonthlySpend * 12 * $retentionProbability;
        $clv3Years = $predictedMonthlySpend * 36 * pow($retentionProbability, 3);

        // Apply tier multipliers
        $tierMultiplier = match($profile->tier) {
            'vip' => 1.5,
            'premium' => 1.3,
            'regular' => 1.1,
            default => 1.0
        };

        return [
            'current_value' => $totalSpent,
            'predicted_6_months' => round($clv6Months * $tierMultiplier, 2),
            'predicted_1_year' => round($clv1Year * $tierMultiplier, 2),
            'predicted_3_years' => round($clv3Years * $tierMultiplier, 2),
            'monthly_value' => round($predictedMonthlySpend, 2),
            'confidence' => $this->calculatePredictionConfidence($orders),
            'factors' => [
                'avg_order_value' => $avgOrderValue,
                'purchase_frequency' => $purchaseFrequency,
                'retention_probability' => $retentionProbability,
                'tier_multiplier' => $tierMultiplier
            ]
        ];
    }

    /**
     * CHURN RISK ANALYSIS
     * Predicts likelihood of customer churn
     */
    public function calculateChurnRisk(CustomerProfile $profile): array
    {
        $riskScore = 0;
        $factors = [];

        // Factor 1: Time since last interaction
        $daysSinceLastInteraction = $profile->last_interaction_at ? 
            Carbon::parse($profile->last_interaction_at)->diffInDays(now()) : 999;
        
        if ($daysSinceLastInteraction > 90) {
            $riskScore += 40;
            $factors[] = 'Long time since last interaction';
        } elseif ($daysSinceLastInteraction > 30) {
            $riskScore += 20;
            $factors[] = 'Moderate time since last interaction';
        }

        // Factor 2: Purchase frequency decline
        $recentOrders = EcommerceOrder::where('tenant_id', $this->tenantId)
            ->where('contact_id', $profile->contact_id)
            ->where('created_at', '>', now()->subDays(90))
            ->count();
        
        $historicalAvg = $profile->total_orders > 0 ? 
            ($profile->total_orders / max(1, Carbon::parse($profile->created_at)->diffInMonths(now()))) * 3 : 0;

        if ($recentOrders < $historicalAvg * 0.5) {
            $riskScore += 30;
            $factors[] = 'Declining purchase frequency';
        }

        // Factor 3: Behavioral score decline
        if ($profile->behavioral_score < 40) {
            $riskScore += 25;
            $factors[] = 'Low behavioral engagement';
        }

        // Factor 4: Support interactions
        $supportInteractions = CustomerInteraction::where('tenant_id', $this->tenantId)
            ->where('contact_id', $profile->contact_id)
            ->where('interaction_type', 'support')
            ->where('created_at', '>', now()->subDays(30))
            ->count();

        if ($supportInteractions > 2) {
            $riskScore += 15;
            $factors[] = 'Recent support issues';
        }

        // Factor 5: Tier regression
        if ($profile->tier === 'standard' && $profile->total_orders > 5) {
            $riskScore += 10;
            $factors[] = 'No tier progression despite activity';
        }

        // Determine risk level
        $riskLevel = 'low';
        if ($riskScore >= 70) {
            $riskLevel = 'critical';
        } elseif ($riskScore >= 50) {
            $riskLevel = 'high';
        } elseif ($riskScore >= 30) {
            $riskLevel = 'medium';
        }

        return [
            'probability' => min(95, $riskScore),
            'level' => $riskLevel,
            'factors' => $factors,
            'recommended_actions' => $this->getChurnPreventionActions($riskLevel, $factors),
            'urgency' => $riskLevel === 'critical' ? 'immediate' : ($riskLevel === 'high' ? 'high' : 'normal')
        ];
    }

    /**
     * NEXT PURCHASE PREDICTION
     * Predicts when customer will make their next purchase
     */
    public function predictNextPurchase(CustomerProfile $profile): array
    {
        $orders = EcommerceOrder::where('tenant_id', $this->tenantId)
            ->where('contact_id', $profile->contact_id)
            ->orderBy('created_at')
            ->get();

        if ($orders->count() < 2) {
            return $this->predictFirstRepeatPurchase($profile);
        }

        // Calculate average days between purchases
        $intervals = [];
        for ($i = 1; $i < $orders->count(); $i++) {
            $intervals[] = Carbon::parse($orders[$i-1]->created_at)
                ->diffInDays($orders[$i]->created_at);
        }

        $avgInterval = array_sum($intervals) / count($intervals);
        $lastOrderDate = Carbon::parse($orders->last()->created_at);
        $daysSinceLastOrder = $lastOrderDate->diffInDays(now());

        // Adjust for seasonal patterns
        $seasonalMultiplier = $this->getSeasonalMultiplier($profile->seasonal_patterns ?? []);
        $adjustedInterval = $avgInterval * $seasonalMultiplier;

        // Calculate prediction
        $expectedNextPurchase = $lastOrderDate->addDays($adjustedInterval);
        $isOverdue = $daysSinceLastOrder > $adjustedInterval;

        // Confidence calculation
        $confidence = $this->calculateIntervalConfidence($intervals, $avgInterval);

        return [
            'predicted_date' => $expectedNextPurchase->toDateString(),
            'days_from_now' => max(0, now()->diffInDays($expectedNextPurchase, false)),
            'is_overdue' => $isOverdue,
            'days_overdue' => $isOverdue ? $daysSinceLastOrder - $adjustedInterval : 0,
            'confidence' => $confidence,
            'timeframe' => $this->categorizeTimeframe($expectedNextPurchase),
            'factors' => [
                'average_interval_days' => round($avgInterval, 1),
                'seasonal_adjustment' => $seasonalMultiplier,
                'last_order_date' => $lastOrderDate->toDateString()
            ]
        ];
    }

    /**
     * PURCHASE INTENT SCORE
     * Analyzes current message and context for purchase intent
     */
    protected function calculatePurchaseIntentScore(CustomerProfile $profile, string $message): int
    {
        $score = 0;

        // Analyze message for purchase keywords
        $purchaseKeywords = [
            'buy' => 20, 'purchase' => 20, 'order' => 18, 'get' => 10,
            'want' => 15, 'need' => 12, 'looking for' => 15, 'interested' => 12,
            'price' => 10, 'cost' => 8, 'how much' => 8, 'affordable' => 6,
            'compare' => 5, 'versus' => 5, 'best' => 8, 'recommend' => 6
        ];

        $messageLower = strtolower($message);
        foreach ($purchaseKeywords as $keyword => $points) {
            if (strpos($messageLower, $keyword) !== false) {
                $score += $points;
            }
        }

        // Adjust based on customer behavior
        if ($profile->behavioral_score > 70) {
            $score += 10; // High engagement customers more likely to purchase
        }

        // Recent activity bonus
        $recentInteractions = CustomerInteraction::where('tenant_id', $this->tenantId)
            ->where('contact_id', $profile->contact_id)
            ->where('created_at', '>', now()->subHours(24))
            ->count();

        if ($recentInteractions > 2) {
            $score += 15; // High recent activity indicates intent
        }

        // Seasonal adjustment
        $currentSeason = $this->getCurrentSeason();
        if (in_array($currentSeason, $profile->seasonal_patterns ?? [])) {
            $score += 10;
        }

        return min(100, $score);
    }

    /**
     * PREDICT PREFERRED CATEGORIES
     * Predicts which product categories customer will be interested in
     */
    protected function predictPreferredCategories(CustomerProfile $profile): array
    {
        $predictions = [];

        // Base predictions on purchase history
        $favoriteCategories = $profile->favorite_categories ?? [];
        
        // Add related categories based on ML-like associations
        $categoryAssociations = [
            'clothing' => ['accessories', 'shoes', 'jewelry'],
            'electronics' => ['accessories', 'software', 'gaming'],
            'home' => ['garden', 'kitchen', 'decor'],
            'books' => ['stationery', 'electronics', 'education'],
            'sports' => ['clothing', 'health', 'outdoor']
        ];

        foreach ($favoriteCategories as $category) {
            $predictions[] = [
                'category' => $category,
                'confidence' => 85,
                'reason' => 'Historical preference'
            ];

            // Add associated categories
            if (isset($categoryAssociations[$category])) {
                foreach ($categoryAssociations[$category] as $associated) {
                    $predictions[] = [
                        'category' => $associated,
                        'confidence' => 60,
                        'reason' => "Associated with {$category}"
                    ];
                }
            }
        }

        // Remove duplicates and sort by confidence
        $predictions = collect($predictions)
            ->unique('category')
            ->sortByDesc('confidence')
            ->take(5)
            ->values()
            ->toArray();

        return $predictions;
    }

    /**
     * Helper methods for various calculations...
     */
    protected function getChurnPreventionActions(string $riskLevel, array $factors): array
    {
        $actions = [];

        switch ($riskLevel) {
            case 'critical':
                $actions[] = 'Immediate personal outreach';
                $actions[] = 'Exclusive VIP discount offer';
                $actions[] = 'Priority customer service';
                $actions[] = 'Personalized product recommendations';
                break;
                
            case 'high':
                $actions[] = 'Targeted re-engagement campaign';
                $actions[] = 'Special discount offer';
                $actions[] = 'Check-in message';
                break;
                
            case 'medium':
                $actions[] = 'Gentle re-engagement';
                $actions[] = 'Newsletter with personalized content';
                $actions[] = 'Loyalty program benefits';
                break;
        }

        return $actions;
    }

    protected function categorizeTimeframe($predictedDate): string
    {
        $daysFromNow = now()->diffInDays($predictedDate, false);
        
        if ($daysFromNow <= 7) return 'this_week';
        if ($daysFromNow <= 30) return 'this_month';
        if ($daysFromNow <= 90) return 'next_3_months';
        return 'beyond_3_months';
    }

    protected function getCurrentSeason(): string
    {
        $month = now()->month;
        if (in_array($month, [12, 1, 2])) return 'winter';
        if (in_array($month, [3, 4, 5])) return 'spring';
        if (in_array($month, [6, 7, 8])) return 'summer';
        return 'autumn';
    }

    // Additional helper methods would be implemented here...
}
