<?php

namespace App\Models\Tenant;

use App\Models\BaseModel;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;

/**
 * Order Item Model for E-commerce Bot
 * 
 * @property int $id
 * @property int|null $tenant_id
 * @property int $order_id
 * @property int|null $product_id
 * @property string $product_name
 * @property string|null $product_sku
 * @property decimal $price
 * @property int $quantity
 * @property decimal $total_amount
 * @property array|null $product_metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class OrderItem extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'order_items';

    protected $casts = [
        'tenant_id' => 'int',
        'order_id' => 'int',
        'product_id' => 'int',
        'price' => 'decimal:2',
        'quantity' => 'int',
        'total_amount' => 'decimal:2',
        'product_metadata' => 'array',
    ];

    protected $fillable = [
        'tenant_id',
        'order_id',
        'product_id',
        'product_name',
        'product_sku',
        'price',
        'quantity',
        'total_amount',
        'product_metadata',
    ];

    /**
     * Boot model events
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($orderItem) {
            $orderItem->total_amount = $orderItem->price * $orderItem->quantity;
        });
    }

    /**
     * Relationship with order
     */
    public function order()
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Relationship with product
     */
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '₹' . number_format($this->price, 2);
    }

    /**
     * Get formatted total
     */
    public function getFormattedTotalAttribute(): string
    {
        return '₹' . number_format($this->total_amount, 2);
    }
}
