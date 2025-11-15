<?php

namespace App\Models\Tenant;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\BelongsToTenant;
use App\Traits\TracksFeatureUsage;
use Carbon\Carbon;

/**
 * Order Model for E-commerce Bot
 * 
 * @property int $id
 * @property int|null $tenant_id
 * @property string $order_number
 * @property int|null $contact_id
 * @property string $customer_name
 * @property string $customer_phone
 * @property string|null $customer_email
 * @property string|null $delivery_address
 * @property decimal $subtotal
 * @property decimal $tax_amount
 * @property decimal $shipping_amount
 * @property decimal $discount_amount
 * @property decimal $total_amount
 * @property string $status
 * @property string $payment_status
 * @property string|null $payment_method
 * @property string|null $payment_reference
 * @property array|null $metadata
 * @property int $sheets_row_index
 * @property Carbon|null $order_date
 * @property Carbon|null $shipped_at
 * @property Carbon|null $delivered_at
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Order extends BaseModel
{
    use BelongsToTenant, TracksFeatureUsage;

    protected $table = 'orders';

    protected $casts = [
        'tenant_id' => 'int',
        'contact_id' => 'int',
        'subtotal' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'shipping_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'metadata' => 'array',
        'sheets_row_index' => 'int',
        'order_date' => 'datetime',
        'shipped_at' => 'datetime',
        'delivered_at' => 'datetime',
        'last_synced_at' => 'datetime',
    ];

    protected $fillable = [
        'tenant_id',
        'order_number',
        'contact_id',
        'customer_name',
        'customer_phone',
        'customer_email',
        'delivery_address',
        'subtotal',
        'tax_amount',
        'shipping_amount',
        'discount_amount',
        'total_amount',
        'status',
        'payment_status',
        'payment_method',
        'payment_reference',
        'metadata',
        'sheets_row_index',
        'order_date',
        'shipped_at',
        'delivered_at',
        'last_synced_at',
    ];

    // Order Status Constants
    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';
    const STATUS_REFUNDED = 'refunded';

    // Payment Status Constants
    const PAYMENT_PENDING = 'pending';
    const PAYMENT_PAID = 'paid';
    const PAYMENT_FAILED = 'failed';
    const PAYMENT_REFUNDED = 'refunded';
    const PAYMENT_PARTIAL = 'partial';

    /**
     * Generate unique order number
     */
    public static function generateOrderNumber(): string
    {
        return 'ORD-' . strtoupper(uniqid());
    }

    /**
     * Get order statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_PROCESSING => 'Processing',
            self::STATUS_SHIPPED => 'Shipped',
            self::STATUS_DELIVERED => 'Delivered',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_REFUNDED => 'Refunded'
        ];
    }

    /**
     * Get payment statuses
     */
    public static function getPaymentStatuses(): array
    {
        return [
            self::PAYMENT_PENDING => 'Pending',
            self::PAYMENT_PAID => 'Paid',
            self::PAYMENT_FAILED => 'Failed',
            self::PAYMENT_REFUNDED => 'Refunded',
            self::PAYMENT_PARTIAL => 'Partial'
        ];
    }

    /**
     * Boot model events
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($order) {
            if (empty($order->order_number)) {
                $order->order_number = self::generateOrderNumber();
            }
            if (empty($order->order_date)) {
                $order->order_date = now();
            }
        });
    }

    /**
     * Relationship with tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship with contact
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Relationship with order items
     */
    public function items()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAttribute(): string
    {
        return '₹' . number_format($this->total_amount, 2);
    }

    /**
     * Check if order can be cancelled
     */
    public function canBeCancelled(): bool
    {
        return in_array($this->status, [
            self::STATUS_PENDING,
            self::STATUS_CONFIRMED,
            self::STATUS_PROCESSING
        ]);
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_DELIVERED;
    }

    /**
     * Calculate totals from items
     */
    public function calculateTotals(): void
    {
        $this->subtotal = $this->items->sum(function ($item) {
            return $item->price * $item->quantity;
        });

        $this->total_amount = $this->subtotal + $this->tax_amount + $this->shipping_amount - $this->discount_amount;
    }

    /**
     * Update order status
     */
    public function updateStatus(string $status, array $metadata = []): bool
    {
        $this->status = $status;
        
        if ($status === self::STATUS_SHIPPED && !$this->shipped_at) {
            $this->shipped_at = now();
        }
        
        if ($status === self::STATUS_DELIVERED && !$this->delivered_at) {
            $this->delivered_at = now();
        }

        if (!empty($metadata)) {
            $this->metadata = array_merge($this->metadata ?? [], $metadata);
        }

        return $this->save();
    }

    /**
     * Get feature slug for feature usage tracking
     */
    public function getFeatureSlug(): ?string
    {
        return 'orders';
    }

    /**
     * Get WhatsApp order confirmation message
     */
    public function getConfirmationMessage(): string
    {
        $message = "🎉 *Order Confirmed!*\n\n";
        $message .= "📋 Order Number: `{$this->order_number}`\n";
        $message .= "📅 Date: {$this->order_date->format('d M Y, H:i')}\n";
        $message .= "👤 Customer: {$this->customer_name}\n";
        $message .= "📱 Phone: {$this->customer_phone}\n\n";
        
        $message .= "*Items:*\n";
        foreach ($this->items as $item) {
            $message .= "• {$item->product_name} x{$item->quantity} - ₹{$item->total_amount}\n";
        }
        
        $message .= "\n💰 Total: *{$this->formatted_total}*\n";
        
        if ($this->delivery_address) {
            $message .= "📍 Delivery Address: {$this->delivery_address}\n";
        }
        
        $message .= "\n📦 Status: " . ucfirst($this->status);
        
        return $message;
    }

    /**
     * Get WhatsApp tracking message
     */
    public function getTrackingMessage(): string
    {
        $message = "📦 *Order Tracking*\n\n";
        $message .= "📋 Order: `{$this->order_number}`\n";
        $message .= "📊 Status: *" . ucfirst($this->status) . "*\n\n";
        
        $statusSteps = [
            self::STATUS_PENDING => '⏳ Order Placed',
            self::STATUS_CONFIRMED => '✅ Order Confirmed',
            self::STATUS_PROCESSING => '🔄 Processing',
            self::STATUS_SHIPPED => '🚚 Shipped',
            self::STATUS_DELIVERED => '🏠 Delivered'
        ];
        
        foreach ($statusSteps as $step => $label) {
            $icon = $this->status === $step ? '🔸' : ($this->hasPassedStatus($step) ? '✅' : '⚪');
            $message .= "{$icon} {$label}\n";
        }
        
        if ($this->shipped_at) {
            $message .= "\n🚚 Shipped on: {$this->shipped_at->format('d M Y, H:i')}\n";
        }
        
        if ($this->delivered_at) {
            $message .= "🏠 Delivered on: {$this->delivered_at->format('d M Y, H:i')}\n";
        }
        
        return $message;
    }

    /**
     * Check if order has passed a certain status
     */
    private function hasPassedStatus(string $status): bool
    {
        $statusOrder = [
            self::STATUS_PENDING => 1,
            self::STATUS_CONFIRMED => 2,
            self::STATUS_PROCESSING => 3,
            self::STATUS_SHIPPED => 4,
            self::STATUS_DELIVERED => 5
        ];
        
        return ($statusOrder[$this->status] ?? 0) > ($statusOrder[$status] ?? 0);
    }
}
