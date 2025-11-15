<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Services\AIProductRecommendationService;
use App\Models\Tenant\Contact;
use App\Models\Tenant\WhatsappConnection;
use App\Traits\WhatsApp;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductSalesController extends Controller
{
    use WhatsApp;

    protected $aiRecommendationService;

    public function __construct(AIProductRecommendationService $aiRecommendationService)
    {
        $this->aiRecommendationService = $aiRecommendationService;
    }

    /**
     * Display the product catalog view
     */
    public function index()
    {
        if (!checkPermission('tenant.product_sales.view')) {
            abort(403, 'Access denied');
        }

        $whatsappConnections = WhatsappConnection::where('tenant_id', tenant_id())
            ->where('status', 'connected')
            ->get();

        return view('tenant.product-sales.index', compact('whatsappConnections'));
    }

    /**
     * Get products via AJAX for the catalog
     */
    public function getProducts(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'search' => 'nullable|string|max:255',
            'category' => 'nullable|string|max:100',
            'price_range' => 'nullable|string|max:50',
            'sort_by' => 'nullable|string|in:name,price-low,price-high,rating,newest',
            'page' => 'nullable|integer|min:1',
            'per_page' => 'nullable|integer|min:1|max:50'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            // For now, return sample products. 
            // In production, integrate with your actual product system or Google Sheets
            $products = $this->getSampleProducts();
            
            // Apply filters
            $filtered = $this->applyFilters($products, $request);
            
            // Apply sorting
            $sorted = $this->applySorting($filtered, $request->sort_by ?? 'name');
            
            // Pagination
            $perPage = $request->per_page ?? 12;
            $page = $request->page ?? 1;
            $offset = ($page - 1) * $perPage;
            
            $paginatedProducts = array_slice($sorted, $offset, $perPage);
            
            return response()->json([
                'status' => 'success',
                'data' => [
                    'products' => $paginatedProducts,
                    'total' => count($sorted),
                    'per_page' => $perPage,
                    'current_page' => $page,
                    'total_pages' => ceil(count($sorted) / $perPage)
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to load products: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get AI-powered product recommendations
     */
    public function getRecommendations(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'customer_id' => 'nullable|integer|exists:contacts,id',
            'type' => 'nullable|string|in:general,upsell,cross-sell,repurchase',
            'limit' => 'nullable|integer|min:1|max:10',
            'cart_items' => 'nullable|array',
            'purchased_items' => 'nullable|array'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $type = $request->type ?? 'general';
            $limit = $request->limit ?? 4;
            
            $recommendations = [];
            
            switch ($type) {
                case 'upsell':
                    if ($request->cart_items) {
                        $recommendations = $this->aiRecommendationService->getUpsellRecommendations(
                            $request->cart_items,
                            $request->customer_id
                        );
                    }
                    break;
                    
                case 'cross-sell':
                    if ($request->purchased_items) {
                        $recommendations = $this->aiRecommendationService->getCrossSellRecommendations(
                            $request->purchased_items,
                            $request->customer_id
                        );
                    }
                    break;
                    
                default:
                    if ($request->customer_id) {
                        $recommendations = $this->aiRecommendationService->getPersonalizedRecommendations(
                            $request->customer_id,
                            ['type' => $type, 'limit' => $limit]
                        );
                    } else {
                        // Generic recommendations for anonymous users
                        $products = $this->getSampleProducts();
                        $recommendations = array_slice($products, 0, $limit);
                    }
            }

            return response()->json([
                'status' => 'success',
                'data' => $recommendations
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to get recommendations: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Process checkout and create WhatsApp order
     */
    public function processCheckout(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.id' => 'required|integer',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.price' => 'required|numeric|min:0',
            'customer_info' => 'required|array',
            'customer_info.name' => 'required|string|max:255',
            'customer_info.phone' => 'required|string|max:20',
            'customer_info.email' => 'nullable|email|max:255',
            'customer_info.address' => 'nullable|string|max:500',
            'payment_method' => 'required|string|in:stripe,razorpay,cod,whatsapp_pay',
            'promo_code' => 'nullable|string|max:50',
            'notes' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            DB::beginTransaction();

            // Create or find customer contact
            $contact = $this->findOrCreateContact($request->customer_info);
            
            // Calculate totals
            $orderData = $this->calculateOrderTotals($request->items, $request->promo_code);
            
            // Create order record (implement based on your order system)
            $order = $this->createOrder($contact, $request->all(), $orderData);
            
            // Process payment based on method
            $paymentResult = $this->processPayment($order, $request->payment_method);
            
            if ($paymentResult['status'] === 'success') {
                // Send order confirmation via WhatsApp
                $this->sendOrderConfirmation($contact, $order);
                
                DB::commit();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Order placed successfully!',
                    'data' => [
                        'order_id' => $order['id'],
                        'order_number' => $order['order_number'],
                        'total' => $orderData['total'],
                        'payment_status' => $paymentResult['payment_status'],
                        'payment_url' => $paymentResult['payment_url'] ?? null
                    ]
                ]);
            } else {
                DB::rollback();
                return response()->json([
                    'status' => 'error',
                    'message' => 'Payment processing failed: ' . $paymentResult['message']
                ], 422);
            }

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json([
                'status' => 'error',
                'message' => 'Checkout failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Send product catalog via WhatsApp
     */
    public function sendCatalogViaWhatsApp(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'phone' => 'required|string|max:20',
            'connection_id' => 'required|integer|exists:whatsapp_connections,id',
            'products' => 'nullable|array',
            'message' => 'nullable|string|max:1000'
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()], 422);
        }

        try {
            $connection = WhatsappConnection::where('id', $request->connection_id)
                ->where('tenant_id', tenant_id())
                ->first();

            if (!$connection) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'WhatsApp connection not found'
                ], 404);
            }

            // Get products to send
            $products = $request->products ?? array_slice($this->getSampleProducts(), 0, 5);
            
            // Create product catalog message
            $catalogMessage = $this->buildCatalogMessage($products, $request->message);
            
            // Send via WhatsApp
            $response = $this->setWaTenantId(tenant_id())->sendMessage(
                $request->phone,
                $catalogMessage,
                $connection->phone_number_id
            );

            if ($response && isset($response['messages'])) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Product catalog sent successfully!',
                    'whatsapp_message_id' => $response['messages'][0]['id'] ?? null
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Failed to send WhatsApp message'
                ], 500);
            }

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Failed to send catalog: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get sample products (replace with your actual product system)
     */
    protected function getSampleProducts(): array
    {
        return [
            [
                'id' => 1,
                'name' => 'Premium Wireless Headphones',
                'description' => 'High-quality wireless headphones with active noise cancellation and 30-hour battery life.',
                'price' => 199.99,
                'originalPrice' => 249.99,
                'category' => 'Electronics',
                'image' => 'https://images.unsplash.com/photo-1505740420928-5e560c06d30e?w=500',
                'rating' => 4.8,
                'reviews' => 128,
                'inStock' => true,
                'stockCount' => 15,
                'tags' => ['wireless', 'premium', 'noise-cancelling'],
                'created_at' => '2024-01-15',
                'features' => [
                    'Active Noise Cancellation',
                    '30-hour battery life',
                    'Premium sound quality',
                    'Comfortable design'
                ]
            ],
            [
                'id' => 2,
                'name' => 'Smart Fitness Watch',
                'description' => 'Advanced fitness tracking with heart rate monitoring, GPS, and waterproof design.',
                'price' => 299.99,
                'category' => 'Electronics',
                'image' => 'https://images.unsplash.com/photo-1523275335684-37898b6baf30?w=500',
                'rating' => 4.6,
                'reviews' => 89,
                'inStock' => true,
                'stockCount' => 8,
                'tags' => ['fitness', 'smart', 'waterproof'],
                'created_at' => '2024-01-20',
                'features' => [
                    'Heart rate monitoring',
                    'GPS tracking',
                    'Waterproof design',
                    '7-day battery life'
                ]
            ],
            [
                'id' => 3,
                'name' => 'Organic Cotton T-Shirt',
                'description' => 'Comfortable, sustainable organic cotton t-shirt available in multiple colors.',
                'price' => 29.99,
                'originalPrice' => 39.99,
                'category' => 'Clothing',
                'image' => 'https://images.unsplash.com/photo-1521572163474-6864f9cf17ab?w=500',
                'rating' => 4.4,
                'reviews' => 156,
                'inStock' => true,
                'stockCount' => 25,
                'tags' => ['organic', 'cotton', 'sustainable'],
                'created_at' => '2024-01-10',
                'variants' => ['size', 'color'],
                'features' => [
                    '100% organic cotton',
                    'Sustainable production',
                    'Soft and comfortable',
                    'Machine washable'
                ]
            ],
            // Add more sample products as needed...
        ];
    }

    /**
     * Apply filters to products
     */
    protected function applyFilters(array $products, Request $request): array
    {
        $filtered = $products;

        // Search filter
        if ($request->search) {
            $search = strtolower($request->search);
            $filtered = array_filter($filtered, function ($product) use ($search) {
                return str_contains(strtolower($product['name']), $search) ||
                       str_contains(strtolower($product['description']), $search) ||
                       str_contains(strtolower($product['category']), $search);
            });
        }

        // Category filter
        if ($request->category) {
            $filtered = array_filter($filtered, function ($product) use ($request) {
                return $product['category'] === $request->category;
            });
        }

        // Price range filter
        if ($request->price_range) {
            $filtered = array_filter($filtered, function ($product) use ($request) {
                $price = $product['price'];
                switch ($request->price_range) {
                    case '0-50':
                        return $price >= 0 && $price <= 50;
                    case '50-100':
                        return $price >= 50 && $price <= 100;
                    case '100-200':
                        return $price >= 100 && $price <= 200;
                    case '200+':
                        return $price >= 200;
                    default:
                        return true;
                }
            });
        }

        return array_values($filtered);
    }

    /**
     * Apply sorting to products
     */
    protected function applySorting(array $products, string $sortBy): array
    {
        usort($products, function ($a, $b) use ($sortBy) {
            switch ($sortBy) {
                case 'price-low':
                    return $a['price'] <=> $b['price'];
                case 'price-high':
                    return $b['price'] <=> $a['price'];
                case 'rating':
                    return ($b['rating'] ?? 0) <=> ($a['rating'] ?? 0);
                case 'newest':
                    return strtotime($b['created_at'] ?? '2024-01-01') <=> strtotime($a['created_at'] ?? '2024-01-01');
                default: // name
                    return strcasecmp($a['name'], $b['name']);
            }
        });

        return $products;
    }

    /**
     * Find or create customer contact
     */
    protected function findOrCreateContact(array $customerInfo): Contact
    {
        $contact = Contact::where('phone', $customerInfo['phone'])
            ->where('tenant_id', tenant_id())
            ->first();

        if (!$contact) {
            $contact = Contact::create([
                'tenant_id' => tenant_id(),
                'name' => $customerInfo['name'],
                'phone' => $customerInfo['phone'],
                'email' => $customerInfo['email'] ?? null,
                'address' => $customerInfo['address'] ?? null,
            ]);
        } else {
            // Update contact info if provided
            $contact->update([
                'name' => $customerInfo['name'],
                'email' => $customerInfo['email'] ?? $contact->email,
                'address' => $customerInfo['address'] ?? $contact->address,
            ]);
        }

        return $contact;
    }

    /**
     * Calculate order totals
     */
    protected function calculateOrderTotals(array $items, ?string $promoCode = null): array
    {
        $subtotal = 0;
        foreach ($items as $item) {
            $subtotal += $item['price'] * $item['quantity'];
        }

        $discount = 0;
        if ($promoCode) {
            // Implement promo code logic
            $discount = $this->calculateDiscount($subtotal, $promoCode);
        }

        $tax = ($subtotal - $discount) * 0.08; // 8% tax
        $shipping = $subtotal >= 75 ? 0 : 9.99; // Free shipping over $75
        $total = $subtotal - $discount + $tax + $shipping;

        return [
            'subtotal' => $subtotal,
            'discount' => $discount,
            'tax' => $tax,
            'shipping' => $shipping,
            'total' => $total
        ];
    }

    /**
     * Create order record
     */
    protected function createOrder(Contact $contact, array $requestData, array $orderData): array
    {
        // Implement based on your order system
        return [
            'id' => rand(1000, 9999),
            'order_number' => 'ORD-' . strtoupper(Str::random(8)),
            'contact_id' => $contact->id,
            'items' => $requestData['items'],
            'totals' => $orderData,
            'status' => 'pending',
            'created_at' => now()
        ];
    }

    /**
     * Process payment
     */
    protected function processPayment(array $order, string $paymentMethod): array
    {
        switch ($paymentMethod) {
            case 'stripe':
                return $this->processStripePayment($order);
            case 'razorpay':
                return $this->processRazorpayPayment($order);
            case 'cod':
                return ['status' => 'success', 'payment_status' => 'pending'];
            case 'whatsapp_pay':
                return $this->processWhatsAppPay($order);
            default:
                return ['status' => 'error', 'message' => 'Invalid payment method'];
        }
    }

    /**
     * Process Stripe payment
     */
    protected function processStripePayment(array $order): array
    {
        try {
            // Implement Stripe payment processing
            // This would create a Stripe payment intent or session
            return [
                'status' => 'success',
                'payment_status' => 'pending',
                'payment_url' => 'https://checkout.stripe.com/session_id'
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Process Razorpay payment
     */
    protected function processRazorpayPayment(array $order): array
    {
        try {
            // Implement Razorpay payment processing
            return [
                'status' => 'success',
                'payment_status' => 'pending',
                'payment_url' => 'https://razorpay.com/payment_link'
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Process WhatsApp Pay
     */
    protected function processWhatsAppPay(array $order): array
    {
        try {
            // Implement WhatsApp Pay processing
            return [
                'status' => 'success',
                'payment_status' => 'pending'
            ];
        } catch (\Exception $e) {
            return ['status' => 'error', 'message' => $e->getMessage()];
        }
    }

    /**
     * Send order confirmation via WhatsApp
     */
    protected function sendOrderConfirmation(Contact $contact, array $order): bool
    {
        try {
            $message = "🎉 *Order Confirmation*\n\n";
            $message .= "Thank you {$contact->name}!\n";
            $message .= "Order #: {$order['order_number']}\n";
            $message .= "Total: $" . number_format($order['totals']['total'], 2) . "\n\n";
            $message .= "We'll send you updates on your order status.\n";
            $message .= "Track your order: " . url("/orders/{$order['order_number']}");

            // Get first available WhatsApp connection
            $connection = WhatsappConnection::where('tenant_id', tenant_id())
                ->where('status', 'connected')
                ->first();

            if ($connection) {
                $this->setWaTenantId(tenant_id())->sendMessage(
                    $contact->phone,
                    ['text' => $message, 'type' => 'text'],
                    $connection->phone_number_id
                );
            }

            return true;
        } catch (\Exception $e) {
            // Log error but don't fail the order
            \Log::error('Failed to send order confirmation: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Build catalog message for WhatsApp
     */
    protected function buildCatalogMessage(array $products, ?string $customMessage = null): array
    {
        $message = $customMessage ?? "🛍️ *Check out our amazing products!*\n\n";
        
        foreach ($products as $index => $product) {
            $message .= "*" . ($index + 1) . ". {$product['name']}*\n";
            $message .= "{$product['description']}\n";
            $message .= "💰 Price: $" . number_format($product['price'], 2) . "\n";
            if ($product['rating'] ?? false) {
                $message .= "⭐ Rating: {$product['rating']}/5\n";
            }
            $message .= "\n";
        }
        
        $message .= "Reply with the product number to learn more or place an order! 📱";

        return [
            'text' => $message,
            'type' => 'text'
        ];
    }

    /**
     * Calculate discount from promo code
     */
    protected function calculateDiscount(float $subtotal, string $promoCode): float
    {
        $validCodes = [
            'SAVE10' => 0.10,
            'WELCOME15' => 0.15,
            'SPRING20' => 0.20
        ];

        $code = strtoupper($promoCode);
        if (isset($validCodes[$code])) {
            return $subtotal * $validCodes[$code];
        }

        return 0;
    }
}
