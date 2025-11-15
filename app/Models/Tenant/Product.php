<?php

namespace App\Models\Tenant;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\BelongsToTenant;
use App\Traits\TracksFeatureUsage;
use Carbon\Carbon;

/**
 * Product Model for E-commerce Bot
 * 
 * @property int $id
 * @property int|null $tenant_id
 * @property string $name
 * @property string|null $description
 * @property string|null $sku
 * @property decimal $price
 * @property decimal|null $compare_price
 * @property int $stock_quantity
 * @property string|null $image_url
 * @property string|null $category
 * @property string $status
 * @property array|null $variants
 * @property array|null $metadata
 * @property int $sheets_row_index
 * @property Carbon|null $last_synced_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class Product extends BaseModel
{
    use BelongsToTenant, TracksFeatureUsage;

    protected $table = 'products';

    protected $casts = [
        'tenant_id' => 'int',
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'stock_quantity' => 'int',
        'variants' => 'array',
        'metadata' => 'array',
        'sheets_row_index' => 'int',
        'last_synced_at' => 'datetime',
    ];

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'sku',
        'price',
        'compare_price',
        'stock_quantity',
        'image_url',
        'category',
        'status',
        'variants',
        'metadata',
        'sheets_row_index',
        'last_synced_at',
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_DRAFT = 'draft';
    const STATUS_ARCHIVED = 'archived';
    const STATUS_OUT_OF_STOCK = 'out_of_stock';

    /**
     * Get available statuses
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ARCHIVED => 'Archived',
            self::STATUS_OUT_OF_STOCK => 'Out of Stock'
        ];
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for in-stock products
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0);
    }

    /**
     * Scope for available products (active and in stock)
     */
    public function scopeAvailable($query)
    {
        return $query->active()->inStock();
    }

    /**
     * Check if product is available for sale
     */
    public function isAvailable(): bool
    {
        return $this->status === self::STATUS_ACTIVE && $this->stock_quantity > 0;
    }

    /**
     * Check if product is low in stock
     */
    public function isLowStock($threshold = 5): bool
    {
        return $this->stock_quantity <= $threshold && $this->stock_quantity > 0;
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return number_format($this->price, 2);
    }

    /**
     * Get discount percentage if compare price exists
     */
    public function getDiscountPercentageAttribute(): ?int
    {
        if ($this->compare_price && $this->compare_price > $this->price) {
            return round((($this->compare_price - $this->price) / $this->compare_price) * 100);
        }
        return null;
    }

    /**
     * Relationship with tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Relationship with order items
     */
    public function orderItems()
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get total sold quantity
     */
    public function getTotalSoldAttribute(): int
    {
        return $this->orderItems()->sum('quantity');
    }

    /**
     * Update stock quantity
     */
    public function updateStock(int $quantity, bool $increase = false): bool
    {
        if ($increase) {
            $this->stock_quantity += $quantity;
        } else {
            $this->stock_quantity = max(0, $this->stock_quantity - $quantity);
        }
        
        // Update status if out of stock
        if ($this->stock_quantity === 0 && $this->status === self::STATUS_ACTIVE) {
            $this->status = self::STATUS_OUT_OF_STOCK;
        } elseif ($this->stock_quantity > 0 && $this->status === self::STATUS_OUT_OF_STOCK) {
            $this->status = self::STATUS_ACTIVE;
        }
        
        return $this->save();
    }

    /**
     * Get feature slug for feature usage tracking
     */
    public function getFeatureSlug(): ?string
    {
        return 'products';
    }

    /**
     * Get WhatsApp message format for this product
     */
    public function toWhatsAppMessage(): array
    {
        $message = "*{$this->name}*\n\n";
        
        if ($this->description) {
            $message .= "{$this->description}\n\n";
        }
        
        $message .= "💰 Price: ₹{$this->formatted_price}";
        
        if ($this->compare_price && $this->discount_percentage) {
            $message .= " ~~₹{$this->compare_price}~~ ({$this->discount_percentage}% OFF)";
        }
        
        $message .= "\n📦 Stock: {$this->stock_quantity} units";
        
        if ($this->category) {
            $message .= "\n🏷️ Category: {$this->category}";
        }

        $buttons = [
            ['id' => "add_to_cart_{$this->id}", 'title' => '🛒 Add to Cart'],
            ['id' => "product_details_{$this->id}", 'title' => 'ℹ️ More Info'],
        ];

        return [
            'type' => 'interactive',
            'interactive' => [
                'type' => 'button',
                'body' => ['text' => $message],
                'action' => ['buttons' => $buttons]
            ]
        ];
    }
}
