<?php

namespace App\Services;

use App\Traits\Ai;
use App\Models\Tenant\Contact;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class AIProductRecommendationService
{
    use Ai;

    protected $cacheTimeout = 3600; // 1 hour

    /**
     * Get AI-powered product recommendations for a customer
     *
     * @param array $customerData Customer information and purchase history
     * @param array $products Available products to recommend from
     * @param array $options Recommendation options (limit, type, etc.)
     * @return array Recommended products with reasons
     */
    public function getRecommendations(array $customerData, array $products, array $options = []): array
    {
        try {
            $limit = $options['limit'] ?? 4;
            $type = $options['type'] ?? 'general'; // general, upsell, cross-sell, repurchase
            
            // Create cache key based on customer and request
            $cacheKey = "ai_recommendations_{$customerData['id']}_{$type}_" . md5(serialize($options));
            
            // Check cache first
            if ($cached = Cache::get($cacheKey)) {
                return $cached;
            }

            // Prepare AI prompt based on recommendation type
            $prompt = $this->buildRecommendationPrompt($customerData, $products, $type);
            
            // Get AI recommendation
            $aiResponse = $this->getAIRecommendation($prompt);
            
            if (!$aiResponse['status']) {
                // Fallback to rule-based recommendations
                return $this->getFallbackRecommendations($customerData, $products, $limit);
            }

            // Parse AI response and match with actual products
            $recommendations = $this->parseAIRecommendations($aiResponse['message'], $products, $limit);
            
            // Cache the results
            Cache::put($cacheKey, $recommendations, $this->cacheTimeout);
            
            return $recommendations;

        } catch (\Exception $e) {
            Log::error('AI Product Recommendation Error', [
                'error' => $e->getMessage(),
                'customer_id' => $customerData['id'] ?? null
            ]);

            // Return fallback recommendations on error
            return $this->getFallbackRecommendations($customerData, $products, $options['limit'] ?? 4);
        }
    }

    /**
     * Get personalized product recommendations based on customer behavior
     */
    public function getPersonalizedRecommendations(int $customerId, array $options = []): array
    {
        try {
            $contact = Contact::find($customerId);
            if (!$contact) {
                return [];
            }

            // Get customer data and purchase history
            $customerData = $this->buildCustomerProfile($contact);
            
            // Get available products (you might want to get this from your product service)
            $products = $this->getAvailableProducts();
            
            return $this->getRecommendations($customerData, $products, $options);

        } catch (\Exception $e) {
            Log::error('Personalized Recommendations Error', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId
            ]);
            return [];
        }
    }

    /**
     * Get upsell recommendations for current cart items
     */
    public function getUpsellRecommendations(array $cartItems, int $customerId = null): array
    {
        try {
            $customerData = $customerId ? $this->buildCustomerProfile(Contact::find($customerId)) : [];
            $customerData['current_cart'] = $cartItems;
            
            $products = $this->getAvailableProducts();
            
            return $this->getRecommendations($customerData, $products, [
                'type' => 'upsell',
                'limit' => 3
            ]);

        } catch (\Exception $e) {
            Log::error('Upsell Recommendations Error', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId
            ]);
            return [];
        }
    }

    /**
     * Get cross-sell recommendations based on purchased items
     */
    public function getCrossSellRecommendations(array $purchasedItems, int $customerId = null): array
    {
        try {
            $customerData = $customerId ? $this->buildCustomerProfile(Contact::find($customerId)) : [];
            $customerData['recent_purchases'] = $purchasedItems;
            
            $products = $this->getAvailableProducts();
            
            return $this->getRecommendations($customerData, $products, [
                'type' => 'cross-sell',
                'limit' => 4
            ]);

        } catch (\Exception $e) {
            Log::error('Cross-sell Recommendations Error', [
                'error' => $e->getMessage(),
                'customer_id' => $customerId
            ]);
            return [];
        }
    }

    /**
     * Build comprehensive customer profile for AI analysis
     */
    protected function buildCustomerProfile(Contact $contact): array
    {
        return [
            'id' => $contact->id,
            'name' => $contact->name,
            'email' => $contact->email,
            'phone' => $contact->phone,
            'age' => $contact->age ?? null,
            'gender' => $contact->gender ?? null,
            'location' => $contact->address ?? null,
            'registration_date' => $contact->created_at->format('Y-m-d'),
            'last_interaction' => $contact->updated_at->format('Y-m-d H:i:s'),
            
            // Purchase history (you might want to implement this based on your order system)
            'purchase_history' => $this->getPurchaseHistory($contact->id),
            'favorite_categories' => $this->getFavoriteCategories($contact->id),
            'average_order_value' => $this->getAverageOrderValue($contact->id),
            'purchase_frequency' => $this->getPurchaseFrequency($contact->id),
            'preferred_brands' => $this->getPreferredBrands($contact->id),
            'seasonal_preferences' => $this->getSeasonalPreferences($contact->id),
            'price_sensitivity' => $this->getPriceSensitivity($contact->id),
            'interaction_channels' => ['whatsapp'], // Since this is WhatsApp focused
        ];
    }

    /**
     * Build AI prompt for product recommendations
     */
    protected function buildRecommendationPrompt(array $customerData, array $products, string $type): string
    {
        $basePrompt = "You are an AI shopping assistant that provides personalized product recommendations. ";
        
        switch ($type) {
            case 'upsell':
                $basePrompt .= "Focus on recommending higher-value or premium alternatives to items in the customer's current cart.";
                break;
            case 'cross-sell':
                $basePrompt .= "Focus on recommending complementary products that go well with the customer's recent purchases.";
                break;
            case 'repurchase':
                $basePrompt .= "Focus on recommending items the customer might need to replenish or replace.";
                break;
            default:
                $basePrompt .= "Provide general product recommendations based on the customer's profile and preferences.";
        }

        $prompt = $basePrompt . "\n\n";
        
        $prompt .= "Customer Profile:\n";
        $prompt .= "- Name: {$customerData['name']}\n";
        $prompt .= "- Purchase History: " . json_encode($customerData['purchase_history'] ?? []) . "\n";
        $prompt .= "- Favorite Categories: " . implode(', ', $customerData['favorite_categories'] ?? []) . "\n";
        $prompt .= "- Average Order Value: $" . ($customerData['average_order_value'] ?? 0) . "\n";
        $prompt .= "- Price Sensitivity: " . ($customerData['price_sensitivity'] ?? 'medium') . "\n";
        
        if (isset($customerData['current_cart'])) {
            $prompt .= "- Current Cart: " . json_encode($customerData['current_cart']) . "\n";
        }
        
        if (isset($customerData['recent_purchases'])) {
            $prompt .= "- Recent Purchases: " . json_encode($customerData['recent_purchases']) . "\n";
        }

        $prompt .= "\nAvailable Products (JSON format):\n";
        $prompt .= json_encode(array_slice($products, 0, 50)); // Limit to avoid token limits
        
        $prompt .= "\n\nPlease recommend 3-5 products in JSON format with this structure:";
        $prompt .= "\n{";
        $prompt .= "\n  \"recommendations\": [";
        $prompt .= "\n    {";
        $prompt .= "\n      \"product_id\": \"123\",";
        $prompt .= "\n      \"confidence_score\": 0.85,";
        $prompt .= "\n      \"reason\": \"Brief explanation why this product fits the customer\"";
        $prompt .= "\n    }";
        $prompt .= "\n  ]";
        $prompt .= "\n}";
        
        return $prompt;
    }

    /**
     * Get AI recommendation using existing AI service
     */
    protected function getAIRecommendation(string $prompt): array
    {
        return $this->aiResponse([
            'input_msg' => $prompt,
            'menu' => 'Custom Prompt',
            'submenu' => 'Analyze customer data and recommend products based on their profile, preferences, and behavior patterns. Focus on personalization and relevance.'
        ]);
    }

    /**
     * Parse AI response and match with actual products
     */
    protected function parseAIRecommendations(string $aiResponse, array $products, int $limit): array
    {
        try {
            // Clean the response and try to extract JSON
            $cleanResponse = $this->cleanAIResponse($aiResponse);
            $parsed = json_decode($cleanResponse, true);
            
            if (!isset($parsed['recommendations']) || !is_array($parsed['recommendations'])) {
                throw new \Exception('Invalid AI response format');
            }

            $recommendations = [];
            $productMap = collect($products)->keyBy('id');
            
            foreach ($parsed['recommendations'] as $rec) {
                if (!isset($rec['product_id']) || !$productMap->has($rec['product_id'])) {
                    continue;
                }
                
                $product = $productMap->get($rec['product_id']);
                $product['recommendation_score'] = $rec['confidence_score'] ?? 0.5;
                $product['recommendation_reason'] = $rec['reason'] ?? 'AI recommended based on your preferences';
                
                $recommendations[] = $product;
                
                if (count($recommendations) >= $limit) {
                    break;
                }
            }
            
            return $recommendations;
            
        } catch (\Exception $e) {
            Log::error('AI Response Parsing Error', ['error' => $e->getMessage(), 'response' => $aiResponse]);
            
            // Return top products as fallback
            return array_slice($products, 0, $limit);
        }
    }

    /**
     * Clean AI response to extract valid JSON
     */
    protected function cleanAIResponse(string $response): string
    {
        // Remove markdown code blocks
        $response = preg_replace('/```json\s*/', '', $response);
        $response = preg_replace('/```\s*$/', '', $response);
        
        // Find JSON-like structure
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            return $matches[0];
        }
        
        return $response;
    }

    /**
     * Fallback recommendations using simple rules
     */
    protected function getFallbackRecommendations(array $customerData, array $products, int $limit): array
    {
        // Simple rule-based fallback
        $recommendations = collect($products)
            ->filter(function ($product) {
                return $product['inStock'] ?? true;
            })
            ->sortByDesc(function ($product) {
                return ($product['rating'] ?? 0) * ($product['reviews'] ?? 1);
            })
            ->take($limit)
            ->values()
            ->toArray();
            
        // Add fallback reason
        foreach ($recommendations as &$product) {
            $product['recommendation_reason'] = 'Popular choice based on customer ratings';
            $product['recommendation_score'] = 0.6;
        }
        
        return $recommendations;
    }

    /**
     * Get available products (implement based on your product system)
     */
    protected function getAvailableProducts(): array
    {
        // This should integrate with your actual product system
        // For now, return sample products
        return [
            [
                'id' => 1,
                'name' => 'Premium Wireless Headphones',
                'price' => 199.99,
                'category' => 'Electronics',
                'rating' => 4.8,
                'reviews' => 128,
                'inStock' => true,
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500'
            ],
            // Add more products...
        ];
    }

    // Helper methods for customer analysis
    protected function getPurchaseHistory(int $contactId): array
    {
        // Implement based on your order system
        return [];
    }

    protected function getFavoriteCategories(int $contactId): array
    {
        // Implement based on purchase history
        return ['Electronics', 'Books'];
    }

    protected function getAverageOrderValue(int $contactId): float
    {
        // Implement based on order history
        return 85.50;
    }

    protected function getPurchaseFrequency(int $contactId): string
    {
        // Implement based on purchase patterns
        return 'monthly'; // weekly, monthly, quarterly, occasional
    }

    protected function getPreferredBrands(int $contactId): array
    {
        // Implement based on purchase history
        return ['Apple', 'Samsung', 'Nike'];
    }

    protected function getSeasonalPreferences(int $contactId): array
    {
        // Implement based on seasonal purchase patterns
        return ['summer' => 'outdoor', 'winter' => 'electronics'];
    }

    protected function getPriceSensitivity(int $contactId): string
    {
        // Analyze customer's price behavior
        return 'medium'; // low, medium, high
    }

    /**
     * Clear recommendation cache for a customer
     */
    public function clearCustomerCache(int $customerId): bool
    {
        $pattern = "ai_recommendations_{$customerId}_*";
        
        // This would need a more sophisticated cache clearing mechanism
        // For now, just return true
        return true;
    }

    /**
     * Get recommendation performance analytics
     */
    public function getRecommendationAnalytics(array $options = []): array
    {
        // Implement analytics for recommendation effectiveness
        return [
            'total_recommendations' => 0,
            'click_through_rate' => 0.0,
            'conversion_rate' => 0.0,
            'revenue_generated' => 0.0
        ];
    }
}
