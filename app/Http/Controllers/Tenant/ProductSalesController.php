<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Tenant\Contact;
use App\Models\Tenant\Product;
use App\Models\Tenant\Order;
use App\Services\FeatureService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ProductSalesController extends Controller
{
    protected FeatureService $featureService;

    public function __construct(FeatureService $featureService)
    {
        $this->featureService = $featureService;
    }

    /**
     * Display the product sales dashboard
     */
    public function index()
    {
        // Check permissions
        if (!checkPermission('tenant.product_sales.view')) {
            abort(403, t('access_denied'));
        }

        // Check feature access
        if (!$this->featureService->hasFeature('product_sales')) {
            return redirect()->route('tenant.dashboard')
                ->with('error', t('feature_not_available_in_current_plan'));
        }

        return view('tenant.product-sales.index');
    }

    /**
     * Get products for catalog
     */
    public function getProducts(Request $request): JsonResponse
    {
        try {
            $products = Product::where('tenant_id', tenant_id())
                ->where('status', 'active')
                ->when($request->search, function ($query, $search) {
                    $query->where(function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%")
                          ->orWhere('description', 'like', "%{$search}%")
                          ->orWhere('sku', 'like', "%{$search}%");
                    });
                })
                ->when($request->category_id, function ($query, $categoryId) {
                    $query->where('category_id', $categoryId);
                })
                ->orderBy($request->sort_by ?? 'name', $request->sort_order ?? 'asc')
                ->paginate($request->per_page ?? 12);

            return response()->json([
                'success' => true,
                'data' => $products
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => t('failed_to_fetch_products')
            ], 500);
        }
    }

    /**
     * Create or update order from WhatsApp
     */
    public function createOrder(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'contact_phone' => 'required|string',
                'products' => 'required|array',
                'products.*.id' => 'required|exists:products,id',
                'products.*.quantity' => 'required|integer|min:1',
                'shipping_address' => 'nullable|string',
                'notes' => 'nullable|string'
            ]);

            // Find or create contact
            $contact = Contact::where('tenant_id', tenant_id())
                ->where('phone', $request->contact_phone)
                ->first();

            if (!$contact) {
                $contact = Contact::create([
                    'tenant_id' => tenant_id(),
                    'phone' => $request->contact_phone,
                    'name' => $request->contact_name ?? 'WhatsApp Customer',
                    'source_id' => getSourceId('WhatsApp Sales'),
                ]);
            }

            // Calculate order total
            $totalAmount = 0;
            $orderItems = [];

            foreach ($request->products as $productData) {
                $product = Product::find($productData['id']);
                $quantity = $productData['quantity'];
                $subtotal = $product->price * $quantity;
                
                $totalAmount += $subtotal;
                $orderItems[] = [
                    'product_id' => $product->id,
                    'product_name' => $product->name,
                    'quantity' => $quantity,
                    'unit_price' => $product->price,
                    'subtotal' => $subtotal
                ];
            }

            // Create order
            $order = Order::create([
                'tenant_id' => tenant_id(),
                'contact_id' => $contact->id,
                'order_number' => $this->generateOrderNumber(),
                'total_amount' => $totalAmount,
                'status' => 'pending',
                'items' => json_encode($orderItems),
                'shipping_address' => $request->shipping_address,
                'notes' => $request->notes,
                'source' => 'whatsapp',
                'ordered_at' => now()
            ]);

            return response()->json([
                'success' => true,
                'message' => t('order_created_successfully'),
                'data' => [
                    'order_id' => $order->id,
                    'order_number' => $order->order_number,
                    'total_amount' => $totalAmount
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => t('failed_to_create_order'),
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update order status
     */
    public function updateOrderStatus(Request $request, $orderId): JsonResponse
    {
        try {
            $request->validate([
                'status' => 'required|in:pending,confirmed,processing,shipped,delivered,cancelled'
            ]);

            $order = Order::where('tenant_id', tenant_id())
                ->findOrFail($orderId);

            $order->update([
                'status' => $request->status,
                'status_updated_at' => now()
            ]);

            // Send WhatsApp notification to customer about status update
            if ($order->contact && $request->send_notification) {
                $this->sendOrderStatusNotification($order, $request->status);
            }

            return response()->json([
                'success' => true,
                'message' => t('order_status_updated_successfully')
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => t('failed_to_update_order_status')
            ], 500);
        }
    }

    /**
     * Get sales analytics data
     */
    public function getAnalytics(Request $request): JsonResponse
    {
        try {
            $period = $request->period ?? '30'; // days
            $startDate = now()->subDays($period);

            $analytics = [
                'total_orders' => Order::where('tenant_id', tenant_id())
                    ->where('created_at', '>=', $startDate)
                    ->count(),
                
                'total_revenue' => Order::where('tenant_id', tenant_id())
                    ->where('created_at', '>=', $startDate)
                    ->where('status', '!=', 'cancelled')
                    ->sum('total_amount'),
                
                'conversion_rate' => $this->calculateConversionRate($startDate),
                
                'top_products' => $this->getTopProducts($startDate),
                
                'daily_sales' => $this->getDailySalesData($startDate),
                
                'order_statuses' => Order::where('tenant_id', tenant_id())
                    ->where('created_at', '>=', $startDate)
                    ->selectRaw('status, COUNT(*) as count')
                    ->groupBy('status')
                    ->pluck('count', 'status')
                    ->toArray()
            ];

            return response()->json([
                'success' => true,
                'data' => $analytics
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => t('failed_to_fetch_analytics')
            ], 500);
        }
    }

    /**
     * Generate unique order number
     */
    private function generateOrderNumber(): string
    {
        $prefix = 'ORD';
        $timestamp = now()->format('ymd');
        $sequence = Order::where('tenant_id', tenant_id())
            ->whereDate('created_at', today())
            ->count() + 1;
        
        return $prefix . '-' . $timestamp . '-' . str_pad($sequence, 4, '0', STR_PAD_LEFT);
    }

    /**
     * Send order status notification via WhatsApp
     */
    private function sendOrderStatusNotification(Order $order, string $status): void
    {
        // Implementation would integrate with your existing WhatsApp service
        // This is a placeholder for the WhatsApp notification logic
    }

    /**
     * Calculate conversion rate
     */
    private function calculateConversionRate($startDate): float
    {
        $totalContacts = Contact::where('tenant_id', tenant_id())
            ->where('created_at', '>=', $startDate)
            ->count();
        
        $orderContacts = Order::where('tenant_id', tenant_id())
            ->where('created_at', '>=', $startDate)
            ->distinct('contact_id')
            ->count();
        
        return $totalContacts > 0 ? round(($orderContacts / $totalContacts) * 100, 2) : 0;
    }

    /**
     * Get top selling products
     */
    private function getTopProducts($startDate): array
    {
        return Order::where('tenant_id', tenant_id())
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

    /**
     * Get daily sales data for charts
     */
    private function getDailySalesData($startDate): array
    {
        return Order::where('tenant_id', tenant_id())
            ->where('created_at', '>=', $startDate)
            ->where('status', '!=', 'cancelled')
            ->selectRaw('DATE(created_at) as date, COUNT(*) as orders, SUM(total_amount) as revenue')
            ->groupBy('date')
            ->orderBy('date')
            ->get()
            ->map(function ($item) {
                return [
                    'date' => $item->date,
                    'orders' => $item->orders,
                    'revenue' => $item->revenue
                ];
            })
            ->toArray();
    }
}
