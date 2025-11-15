<?php

namespace App\Models\Tenant;

use App\Models\BaseModel;
use App\Traits\BelongsToTenant;
use App\Traits\TracksFeatureUsage;

/**
 * Order model for e-commerce functionality
 * 
 * @property int $id
 * @property int $tenant_id
 * @property int $contact_id
 * @property string $order_number
 * @property decimal $total_amount
 * @property string $status
 * @property array $items
 * @property string|null $shipping_address
 * @property string|null $notes
 * @property string $source
 * @property \Carbon\Carbon|null $ordered_at
 * @property \Carbon\Carbon|null $status_updated_at
 * @property array|null $payment_info
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Order extends BaseModel
{
    use BelongsToTenant, TracksFeatureUsage;

    protected $fillable = [
        'tenant_id',
        'contact_id',
        'order_number',
        'total_amount',
        'status',
        'items',
        'shipping_address',
        'notes',
        'source',
        'ordered_at',
        'status_updated_at',
        'payment_info'
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'items' => 'array',
        'payment_info' => 'array',
        'ordered_at' => 'datetime',
        'status_updated_at' => 'datetime',
        'tenant_id' => 'integer',
        'contact_id' => 'integer'
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_CONFIRMED = 'confirmed';
    const STATUS_PROCESSING = 'processing';
    const STATUS_SHIPPED = 'shipped';
    const STATUS_DELIVERED = 'delivered';
    const STATUS_CANCELLED = 'cancelled';

    const SOURCE_WHATSAPP = 'whatsapp';
    const SOURCE_WEBSITE = 'website';
    const SOURCE_MANUAL = 'manual';

    public function getFeatureSlug(): ?string
    {
        return 'orders';
    }

    /**
     * Relationship to contact
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Get order items as collection
     */
    public function getItemsCollectionAttribute()
    {
        return collect($this->items ?? []);
    }

    /**
     * Get total items count
     */
    public function getTotalItemsAttribute(): int
    {
        return $this->getItemsCollectionAttribute()->sum('quantity');
    }

    /**
     * Get formatted total amount
     */
    public function getFormattedTotalAttribute(): string
    {
        return '$' . number_format($this->total_amount, 2);
    }

    /**
     * Get status badge color
     */
    public function getStatusBadgeColorAttribute(): string
    {
        return match($this->status) {
            self::STATUS_PENDING => 'yellow',
            self::STATUS_CONFIRMED => 'blue',
            self::STATUS_PROCESSING => 'purple',
            self::STATUS_SHIPPED => 'indigo',
            self::STATUS_DELIVERED => 'green',
            self::STATUS_CANCELLED => 'red',
            default => 'gray'
        };
    }

    /**
     * Check if order can be modified
     */
    public function canBeModified(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_CONFIRMED]);
    }

    /**
     * Check if order is completed
     */
    public function isCompleted(): bool
    {
        return in_array($this->status, [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope for pending orders
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for active orders (not cancelled or delivered)
     */
    public function scopeActive($query)
    {
        return $query->whereNotIn('status', [self::STATUS_CANCELLED, self::STATUS_DELIVERED]);
    }

    /**
     * Scope for completed orders
     */
    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [self::STATUS_DELIVERED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope for orders by source
     */
    public function scopeBySource($query, string $source)
    {
        return $query->where('source', $source);
    }

    /**
     * Update order status with timestamp
     */
    public function updateStatus(string $status): void
    {
        $this->update([
            'status' => $status,
            'status_updated_at' => now()
        ]);

        // Log status change
        activity()
            ->performedOn($this)
            ->withProperties([
                'old_status' => $this->getOriginal('status'),
                'new_status' => $status
            ])
            ->log('order_status_updated');
    }

    /**
     * Calculate order statistics
     */
    public static function getStatistics(int $tenantId, int $days = 30): array
    {
        $startDate = now()->subDays($days);
        
        return [
            'total_orders' => self::where('tenant_id', $tenantId)
                ->where('created_at', '>=', $startDate)
                ->count(),
            
            'total_revenue' => self::where('tenant_id', $tenantId)
                ->where('created_at', '>=', $startDate)
                ->where('status', '!=', self::STATUS_CANCELLED)
                ->sum('total_amount'),
            
            'pending_orders' => self::where('tenant_id', $tenantId)
                ->where('status', self::STATUS_PENDING)
                ->count(),
                
            'completed_orders' => self::where('tenant_id', $tenantId)
                ->where('created_at', '>=', $startDate)
                ->where('status', self::STATUS_DELIVERED)
                ->count(),
        ];
    }
}
