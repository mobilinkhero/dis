<?php

namespace App\Models\Tenant;

use App\Models\BaseModel;
use App\Traits\BelongsToTenant;
use App\Traits\TracksFeatureUsage;

/**
 * Product model for e-commerce functionality
 * 
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $description
 * @property string|null $sku
 * @property decimal $price
 * @property int $stock_quantity
 * @property string $status
 * @property array|null $images
 * @property int|null $category_id
 * @property array|null $variants
 * @property array|null $metadata
 * @property \Carbon\Carbon $created_at
 * @property \Carbon\Carbon $updated_at
 */
class Product extends BaseModel
{
    use BelongsToTenant, TracksFeatureUsage;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'sku',
        'price',
        'stock_quantity',
        'status',
        'images',
        'category_id',
        'variants',
        'metadata'
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'stock_quantity' => 'integer',
        'images' => 'array',
        'variants' => 'array',
        'metadata' => 'array',
        'tenant_id' => 'integer',
        'category_id' => 'integer'
    ];

    const STATUS_ACTIVE = 'active';
    const STATUS_INACTIVE = 'inactive';
    const STATUS_OUT_OF_STOCK = 'out_of_stock';

    public function getFeatureSlug(): ?string
    {
        return 'products';
    }

    /**
     * Check if product is in stock
     */
    public function isInStock(): bool
    {
        return $this->stock_quantity > 0 && $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Get product's main image
     */
    public function getMainImageAttribute(): ?string
    {
        return $this->images[0] ?? null;
    }

    /**
     * Scope for active products
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for products in stock
     */
    public function scopeInStock($query)
    {
        return $query->where('stock_quantity', '>', 0)
                     ->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Decrease stock quantity
     */
    public function decreaseStock(int $quantity): bool
    {
        if ($this->stock_quantity >= $quantity) {
            $this->decrement('stock_quantity', $quantity);
            
            // Update status if out of stock
            if ($this->stock_quantity <= 0) {
                $this->update(['status' => self::STATUS_OUT_OF_STOCK]);
            }
            
            return true;
        }
        
        return false;
    }

    /**
     * Increase stock quantity
     */
    public function increaseStock(int $quantity): void
    {
        $this->increment('stock_quantity', $quantity);
        
        // Update status back to active if was out of stock
        if ($this->status === self::STATUS_OUT_OF_STOCK && $this->stock_quantity > 0) {
            $this->update(['status' => self::STATUS_ACTIVE]);
        }
    }

    /**
     * Get formatted price
     */
    public function getFormattedPriceAttribute(): string
    {
        return '$' . number_format($this->price, 2);
    }
}
