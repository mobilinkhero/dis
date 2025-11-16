<?php

namespace App\Services;

use App\Models\Tenant\Product;
use App\Models\Tenant\Order;
use App\Models\Tenant\Contact;
use App\Models\Tenant\EcommerceConfiguration;
use App\Services\EcommerceLogger;
use App\Traits\Ai;
use App\Traits\WhatsApp;
use Carbon\Carbon;

/**
 * E-commerce Order Processing Service
 * Handles WhatsApp-based product catalog, orders, and AI-powered interactions
 */
class EcommerceOrderService
{
    use Ai, WhatsApp;

    protected $tenantId;
    protected $config;
    protected $currentContact;
    protected $currentOrder;

    public function __construct($tenantId = null)
    {
        $this->tenantId = $tenantId ?? tenant_id();
        $this->config = EcommerceConfiguration::where('tenant_id', $this->tenantId)->first();
    }

    /**
     * Process incoming WhatsApp message for e-commerce
     */
    public function processMessage(string $message, Contact $contact): array
    {
        try {
            EcommerceLogger::botInteraction(
                $contact->phone ?? 'unknown', 
                $message, 
                'Processing started',
                ['method' => 'processMessage']
            );

            // Check if e-commerce is configured
            if (!$this->config) {
                EcommerceLogger::warning('E-commerce configuration not found', [
                    'tenant_id' => $this->tenantId
                ]);
                
                return [
                    'handled' => false,
                    'response' => null
                ];
            }

            if (!$this->config->isFullyConfigured()) {
                EcommerceLogger::warning('E-commerce not fully configured', [
                    'tenant_id' => $this->tenantId,
                    'is_configured' => $this->config->is_configured,
                    'has_sheets_url' => !empty($this->config->google_sheets_url),
                    'sheets_url' => $this->config->google_sheets_url
                ]);
                
                return [
                    'handled' => false,
                    'response' => null
                ];
            }

            EcommerceLogger::info('E-commerce configuration verified', [
                'tenant_id' => $this->tenantId,
                'message' => $message
            ]);

            $this->currentContact = $contact;
            
            // Use AI to detect intent and extract product information
            $intent = $this->detectMessageIntent($message);
            
            switch ($intent['type']) {
                case 'browse_products':
                    return $this->handleBrowseProducts($intent);
                
                case 'product_inquiry':
                    return $this->handleProductInquiry($intent);
                
                case 'add_to_cart':
                    return $this->handleAddToCart($intent);
                
                case 'view_cart':
                    return $this->handleViewCart();
                
                case 'checkout':
                    return $this->handleCheckout($intent);
                
                case 'order_status':
                    return $this->handleOrderStatus($intent);
                
                case 'help':
                    return $this->handleHelp();
                
                default:
                    return $this->handleUnknownMessage($message);
            }
        } catch (\Exception $e) {
            EcommerceLogger::error('Error processing message', [
                'tenant_id' => $this->tenantId,
                'contact_phone' => $contact->phone ?? 'unknown',
                'message' => $message,
                'exception' => $e->getMessage(),
                'stack_trace' => $e->getTraceAsString()
            ]);

            return [
                'handled' => false,
                'response' => 'An error occurred while processing your message. Please try again later.'
            ];
        }
    }

    /**
     * Use AI to detect customer intent from message
     */
    protected function detectMessageIntent(string $message): array
    {
        try {
            $prompt = "Analyze this WhatsApp message for e-commerce intent. Extract key information and classify the intent.

Message: \"{$message}\"

Available intents:
- browse_products: Customer wants to see products/catalog
- product_inquiry: Asking about specific product details
- add_to_cart: Wants to add item to cart/order
- view_cart: Check current cart/order
- checkout: Ready to place order
- order_status: Check existing order status
- help: Needs assistance

Return JSON format:
{
    \"type\": \"intent_name\",
    \"confidence\": 0.95,
    \"extracted_data\": {
        \"product_name\": \"extracted product name if any\",
        \"quantity\": \"extracted quantity if any\",
        \"category\": \"extracted category if any\",
        \"order_number\": \"extracted order number if any\"
    }
}";

            $aiResponse = $this->aiResponse([
                'input_msg' => $message,
                'menu' => 'Custom Prompt',
                'submenu' => $prompt
            ]);

            if ($aiResponse['status']) {
                $intent = json_decode($aiResponse['message'], true);
                if ($intent && isset($intent['type'])) {
                    return $intent;
                }
            }
        } catch (\Exception $e) {
            \Log::error('AI intent detection failed: ' . $e->getMessage());
        }

        // Fallback to keyword-based detection
        return $this->fallbackIntentDetection($message);
    }

    /**
     * Fallback intent detection using keywords
     */
    protected function fallbackIntentDetection(string $message): array
    {
        $message = strtolower($message);
        
        $patterns = [
            'browse_products' => ['shop', 'catalog', 'products', 'show me', 'what do you have', 'browse', 'menu', 'store', 'buy'],
            'add_to_cart' => ['add', 'purchase', 'i want', 'get me', 'take'],
            'view_cart' => ['cart', 'my order', 'what did i order', 'check order', 'my cart'],
            'checkout' => ['checkout', 'confirm order', 'place order', 'proceed', 'pay', 'complete'],
            'order_status' => ['status', 'where is my order', 'tracking', 'delivered', 'order status'],
            'help' => ['help', 'how to', 'assist', 'support', 'info']
        ];

        foreach ($patterns as $intent => $keywords) {
            foreach ($keywords as $keyword) {
                if (strpos($message, $keyword) !== false) {
                    return [
                        'type' => $intent,
                        'confidence' => 0.7,
                        'extracted_data' => []
                    ];
                }
            }
        }

        return [
            'type' => 'unknown',
            'confidence' => 0.0,
            'extracted_data' => []
        ];
    }

    /**
     * Handle browse products request
     */
    protected function handleBrowseProducts(array $intent): array
    {
        $products = Product::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->where('stock_quantity', '>', 0)
            ->limit(10)
            ->get();

        if ($products->isEmpty()) {
            return [
                'handled' => true,
                'response' => "Sorry, we don't have any products available right now. Please check back later! 😊"
            ];
        }

        $response = "🛍️ *Welcome to our store!* Here are our available products:\n\n";
        
        foreach ($products as $product) {
            $response .= "📦 *{$product->name}*\n";
            $response .= "💰 Price: {$product->formatted_price}";
            
            if ($product->is_on_sale) {
                $response .= " ~~\${$product->price}~~ (Sale!)";
            }
            
            $response .= "\n📋 {$product->description}\n";
            $response .= "📊 Stock: {$product->stock_quantity} units\n";
            
            if ($product->category) {
                $response .= "🏷️ Category: {$product->category}\n";
            }
            
            $response .= "\n";
        }

        $response .= "To order, just tell me what you want! For example:\n";
        $response .= "\"I want 2 units of {$products->first()->name}\"\n\n";
        $response .= "Need help? Just type 'help' 🤝";

        return [
            'handled' => true,
            'response' => $response
        ];
    }

    /**
     * Handle specific product inquiry
     */
    protected function handleProductInquiry(array $intent): array
    {
        $productName = $intent['extracted_data']['product_name'] ?? '';
        $category = $intent['extracted_data']['category'] ?? '';

        $query = Product::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->where('stock_quantity', '>', 0);

        if ($productName) {
            $query->where('name', 'like', "%{$productName}%");
        } elseif ($category) {
            $query->where('category', 'like', "%{$category}%");
        }

        $products = $query->limit(5)->get();

        if ($products->isEmpty()) {
            return [
                'handled' => true,
                'response' => "Sorry, I couldn't find any products matching your request. 😕\n\nWould you like to see our full catalog? Just type 'catalog' or 'products'."
            ];
        }

        $response = "🔍 *Here's what I found:*\n\n";
        
        foreach ($products as $product) {
            $response .= "📦 *{$product->name}*\n";
            $response .= "💰 {$product->formatted_price}";
            
            if ($product->is_on_sale) {
                $response .= " ~~\${$product->price}~~ 🏷️";
            }
            
            $response .= "\n📋 {$product->description}\n";
            $response .= "📊 Available: {$product->stock_quantity} units\n\n";
        }

        // AI-powered recommendations
        $recommendations = $this->getAIRecommendations($products->first());
        if (!empty($recommendations)) {
            $response .= "💡 *You might also like:*\n";
            foreach ($recommendations as $rec) {
                $response .= "• {$rec->name} - {$rec->formatted_price}\n";
            }
            $response .= "\n";
        }

        $response .= "To order, just tell me the quantity you want! 🛒";

        return [
            'handled' => true,
            'response' => $response
        ];
    }

    /**
     * Handle add to cart request
     */
    protected function handleAddToCart(array $intent): array
    {
        $productName = $intent['extracted_data']['product_name'] ?? '';
        $quantity = (int) ($intent['extracted_data']['quantity'] ?? 1);

        if (!$productName) {
            return [
                'handled' => true,
                'response' => "I'd be happy to help you order! Could you please specify which product you'd like? 😊\n\nType 'catalog' to see all available products."
            ];
        }

        // Find the product
        $product = Product::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->where('name', 'like', "%{$productName}%")
            ->first();

        if (!$product) {
            return [
                'handled' => true,
                'response' => "Sorry, I couldn't find a product called \"{$productName}\". 😕\n\nWould you like to see our available products? Type 'catalog'."
            ];
        }

        if ($product->stock_quantity < $quantity) {
            return [
                'handled' => true,
                'response' => "Sorry, we only have {$product->stock_quantity} units of {$product->name} in stock. 📦\n\nWould you like to order {$product->stock_quantity} units instead?"
            ];
        }

        // Get or create current order for this contact
        $order = $this->getCurrentOrder();
        
        // Add item to order
        $existingItems = $order->items ?? [];
        $itemExists = false;

        foreach ($existingItems as $key => $item) {
            if ($item['product_id'] == $product->id) {
                $existingItems[$key]['quantity'] += $quantity;
                $existingItems[$key]['total'] = $existingItems[$key]['quantity'] * $product->effective_price;
                $itemExists = true;
                break;
            }
        }

        if (!$itemExists) {
            $existingItems[] = [
                'product_id' => $product->id,
                'product_name' => $product->name,
                'sku' => $product->sku,
                'quantity' => $quantity,
                'price' => $product->effective_price,
                'total' => $quantity * $product->effective_price,
            ];
        }

        $order->items = $existingItems;
        $order->calculateTotals();
        $order->save();

        $response = "✅ *Added to your cart!*\n\n";
        $response .= "📦 {$quantity}x {$product->name}\n";
        $response .= "💰 {$product->formatted_price} each\n";
        $response .= "🧮 Subtotal: \$" . number_format($quantity * $product->effective_price, 2) . "\n\n";
        
        $response .= "Current cart total: *\$" . number_format($order->total_amount, 2) . "*\n\n";
        $response .= "Want to add more items or ready to checkout?\n";
        $response .= "• Type 'cart' to view your full cart\n";
        $response .= "• Type 'checkout' to place your order\n";
        $response .= "• Or tell me what else you'd like to add! 🛒";

        return [
            'handled' => true,
            'response' => $response
        ];
    }

    /**
     * Handle view cart request
     */
    protected function handleViewCart(): array
    {
        $order = $this->getCurrentOrder();
        
        if (empty($order->items)) {
            return [
                'handled' => true,
                'response' => "🛒 Your cart is empty!\n\nWould you like to browse our products? Type 'catalog' to see what's available. 😊"
            ];
        }

        $response = "🛒 *Your Cart:*\n\n";
        
        foreach ($order->items as $item) {
            $response .= "📦 {$item['quantity']}x {$item['product_name']}\n";
            $response .= "💰 \${$item['price']} each = \${$item['total']}\n\n";
        }

        $response .= "💳 *Order Summary:*\n";
        $response .= "Subtotal: \$" . number_format($order->subtotal, 2) . "\n";
        $response .= "Tax: \$" . number_format($order->tax_amount, 2) . "\n";
        $response .= "**Total: \$" . number_format($order->total_amount, 2) . "**\n\n";
        
        $response .= "Ready to checkout? Type 'checkout' to place your order! 🚀";

        return [
            'handled' => true,
            'response' => $response
        ];
    }

    /**
     * Handle checkout request
     */
    protected function handleCheckout(array $intent): array
    {
        $order = $this->getCurrentOrder();
        
        if (empty($order->items)) {
            return [
                'handled' => true,
                'response' => "🛒 Your cart is empty! Add some products first.\n\nType 'catalog' to browse our products. 😊"
            ];
        }

        // Generate order number and finalize order
        $order->update([
            'order_number' => Order::generateOrderNumber(),
            'status' => Order::STATUS_CONFIRMED,
            'customer_name' => $this->currentContact->name ?? 'WhatsApp Customer',
            'customer_phone' => $this->currentContact->phone ?? '',
            'customer_email' => $this->currentContact->email ?? '',
        ]);

        // Reduce stock for all items
        foreach ($order->items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $product->reduceStock($item['quantity']);
            }
        }

        // Sync to Google Sheets
        $sheetsService = new GoogleSheetsService($this->tenantId);
        $sheetsService->syncOrderToSheets($order);

        $response = "🎉 *Order Confirmed!*\n\n";
        $response .= "📋 Order #: *{$order->order_number}*\n";
        $response .= "💳 Total: *\$" . number_format($order->total_amount, 2) . "*\n\n";
        
        $response .= "📦 *Items Ordered:*\n";
        foreach ($order->items as $item) {
            $response .= "• {$item['quantity']}x {$item['product_name']}\n";
        }
        
        $response .= "\n🚚 We'll process your order shortly!\n";
        $response .= "💬 You'll receive updates on this WhatsApp number.\n\n";
        
        // Payment instructions based on configured methods
        $paymentMethods = $this->config->payment_methods ?? [];
        if (!empty($paymentMethods)) {
            $response .= "💰 *Payment Options:*\n";
            foreach ($paymentMethods as $method) {
                $response .= "• " . ucfirst(str_replace('_', ' ', $method)) . "\n";
            }
            $response .= "\nOur team will contact you with payment details.\n\n";
        }
        
        $response .= "Thank you for your order! 🙏\n";
        $response .= "Questions? Just message us anytime!";

        // Clear the current order session
        $this->clearCurrentOrder();

        return [
            'handled' => true,
            'response' => $response
        ];
    }

    /**
     * Handle order status inquiry
     */
    protected function handleOrderStatus(array $intent): array
    {
        $orderNumber = $intent['extracted_data']['order_number'] ?? '';
        
        if (!$orderNumber) {
            // Show recent orders for this contact
            $recentOrders = Order::where('tenant_id', $this->tenantId)
                ->where('customer_phone', $this->currentContact->phone)
                ->orderBy('created_at', 'desc')
                ->limit(3)
                ->get();

            if ($recentOrders->isEmpty()) {
                return [
                    'handled' => true,
                    'response' => "I don't see any recent orders for your number. 😕\n\nIf you have an order number, please share it and I'll check the status for you!"
                ];
            }

            $response = "📋 *Your Recent Orders:*\n\n";
            foreach ($recentOrders as $order) {
                $response .= "🔸 Order #{$order->order_number}\n";
                $response .= "📅 " . $order->created_at->format('M j, Y') . "\n";
                $response .= "🎯 Status: " . ucfirst($order->status) . "\n";
                $response .= "💰 Total: \$" . number_format($order->total_amount, 2) . "\n\n";
            }
            
            $response .= "For specific order details, share your order number! 📞";

            return [
                'handled' => true,
                'response' => $response
            ];
        }

        // Find specific order
        $order = Order::where('tenant_id', $this->tenantId)
            ->where('order_number', 'like', "%{$orderNumber}%")
            ->first();

        if (!$order) {
            return [
                'handled' => true,
                'response' => "I couldn't find order #{$orderNumber}. 😕\n\nPlease check the order number and try again, or contact our support team for assistance!"
            ];
        }

        $response = "📋 *Order Status Update*\n\n";
        $response .= "🔢 Order #: {$order->order_number}\n";
        $response .= "📅 Date: " . $order->created_at->format('M j, Y g:i A') . "\n";
        $response .= "🎯 Status: *" . ucfirst($order->status) . "*\n";
        $response .= "💳 Payment: " . ucfirst($order->payment_status) . "\n";
        $response .= "💰 Total: \$" . number_format($order->total_amount, 2) . "\n\n";

        $response .= "📦 *Items:*\n";
        foreach ($order->order_items as $item) {
            $response .= "• {$item['quantity']}x {$item['product_name']}\n";
        }

        if ($order->tracking_number) {
            $response .= "\n🚚 Tracking: *{$order->tracking_number}*\n";
        }

        if ($order->notes) {
            $response .= "\n📝 Notes: {$order->notes}\n";
        }

        $response .= "\nNeed help? Just ask! 🤝";

        return [
            'handled' => true,
            'response' => $response
        ];
    }

    /**
     * Handle help request
     */
    protected function handleHelp(): array
    {
        $response = "🤝 *How can I help you?*\n\n";
        $response .= "Here's what you can do:\n\n";
        $response .= "🛍️ *Shopping:*\n";
        $response .= "• Type 'catalog' or 'products' to browse\n";
        $response .= "• Say 'I want [product name]' to order\n";
        $response .= "• Type 'cart' to view your current cart\n";
        $response .= "• Type 'checkout' when ready to order\n\n";
        
        $response .= "📋 *Order Management:*\n";
        $response .= "• Type 'my orders' to see recent orders\n";
        $response .= "• Share your order number for status updates\n\n";
        
        $response .= "💬 *Examples:*\n";
        $response .= "• \"Show me phones\" - Browse phone category\n";
        $response .= "• \"I want 2 iPhone cases\" - Add to cart\n";
        $response .= "• \"Order #12345 status\" - Check order\n\n";
        
        $response .= "Just message naturally - I understand! 😊\n";
        $response .= "Our AI will help process your request.";

        return [
            'handled' => true,
            'response' => $response
        ];
    }

    /**
     * Handle unknown messages with AI
     */
    protected function handleUnknownMessage(string $message): array
    {
        // Use AI to provide helpful response
        try {
            $prompt = "You are a helpful e-commerce assistant for a WhatsApp store. A customer sent this message: \"{$message}\"

Provide a helpful, friendly response that:
1. Acknowledges their message
2. Offers relevant assistance
3. Guides them to browse products, get help, or clarify their request
4. Keeps it conversational and brief

Don't make up specific products or prices. Focus on being helpful and guiding them to the right action.";

            $aiResponse = $this->aiResponse([
                'input_msg' => $message,
                'menu' => 'Custom Prompt',
                'submenu' => $prompt
            ]);

            if ($aiResponse['status']) {
                return [
                    'handled' => true,
                    'response' => $aiResponse['message'] . "\n\n💡 Type 'help' for more options!"
                ];
            }
        } catch (\Exception $e) {
            \Log::error('AI response generation failed: ' . $e->getMessage());
        }

        // Fallback response
        return [
            'handled' => true,
            'response' => "I'm here to help! 😊\n\nYou can:\n• Type 'catalog' to see our products\n• Type 'help' for more options\n• Or just tell me what you're looking for!\n\nWhat would you like to do?"
        ];
    }

    /**
     * Get or create current order for contact
     */
    protected function getCurrentOrder(): Order
    {
        if ($this->currentOrder) {
            return $this->currentOrder;
        }

        // Find existing pending order or create new one
        $this->currentOrder = Order::firstOrCreate(
            [
                'tenant_id' => $this->tenantId,
                'contact_id' => $this->currentContact->id,
                'status' => Order::STATUS_PENDING,
            ],
            [
                'order_number' => null, // Will be set on checkout
                'customer_name' => $this->currentContact->name ?? '',
                'customer_phone' => $this->currentContact->phone ?? '',
                'customer_email' => $this->currentContact->email ?? '',
                'items' => [],
                'subtotal' => 0,
                'tax_amount' => 0,
                'total_amount' => 0,
                'currency' => $this->config->currency ?? 'USD',
                'payment_method' => '',
                'payment_status' => Order::PAYMENT_PENDING,
                'source' => 'whatsapp',
            ]
        );

        return $this->currentOrder;
    }

    /**
     * Clear current order session
     */
    protected function clearCurrentOrder(): void
    {
        $this->currentOrder = null;
    }

    /**
     * Get AI-powered product recommendations
     */
    protected function getAIRecommendations(Product $product, int $limit = 3): array
    {
        if (!$this->config || !$this->config->ai_recommendations_enabled) {
            return [];
        }

        // Get related products from same category
        $recommendations = Product::where('tenant_id', $this->tenantId)
            ->where('status', 'active')
            ->where('stock_quantity', '>', 0)
            ->where('id', '!=', $product->id)
            ->where('category', $product->category)
            ->limit($limit)
            ->get();

        // If not enough from same category, get featured products
        if ($recommendations->count() < $limit) {
            $additional = Product::where('tenant_id', $this->tenantId)
                ->where('status', 'active')
                ->where('stock_quantity', '>', 0)
                ->where('featured', true)
                ->where('id', '!=', $product->id)
                ->whereNotIn('id', $recommendations->pluck('id'))
                ->limit($limit - $recommendations->count())
                ->get();
            
            $recommendations = $recommendations->concat($additional);
        }

        return $recommendations->toArray();
    }
}
