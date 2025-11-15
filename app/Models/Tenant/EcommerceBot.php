<?php

namespace App\Models\Tenant;

use App\Models\BaseModel;
use App\Models\Tenant;
use App\Traits\BelongsToTenant;
use Carbon\Carbon;

/**
 * E-commerce Bot Configuration Model
 * 
 * @property int $id
 * @property int|null $tenant_id
 * @property bool $is_enabled
 * @property string|null $google_sheets_product_url
 * @property string|null $google_sheets_order_url
 * @property string|null $sheets_product_id
 * @property string|null $sheets_order_id
 * @property array|null $sync_settings
 * @property array|null $upselling_rules
 * @property array|null $reminder_settings
 * @property Carbon|null $last_sync_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class EcommerceBot extends BaseModel
{
    use BelongsToTenant;

    protected $table = 'ecommerce_bots';

    protected $casts = [
        'tenant_id' => 'int',
        'is_enabled' => 'boolean',
        'sync_settings' => 'array',
        'upselling_rules' => 'array',
        'reminder_settings' => 'array',
        'last_sync_at' => 'datetime',
    ];

    protected $fillable = [
        'tenant_id',
        'is_enabled',
        'google_sheets_product_url',
        'google_sheets_order_url',
        'sheets_product_id',
        'sheets_order_id',
        'sync_settings',
        'upselling_rules',
        'reminder_settings',
        'last_sync_at',
    ];

    /**
     * Get default sync settings
     */
    public function getDefaultSyncSettings(): array
    {
        return [
            'auto_sync_enabled' => true,
            'sync_interval_minutes' => 30,
            'product_columns' => [
                'name' => 'Product Name',
                'price' => 'Price',
                'description' => 'Description',
                'image_url' => 'Image URL',
                'stock_quantity' => 'Stock',
                'category' => 'Category',
                'sku' => 'SKU',
                'status' => 'Status'
            ],
            'order_columns' => [
                'customer_name' => 'Customer Name',
                'customer_phone' => 'Phone',
                'product_name' => 'Product',
                'quantity' => 'Quantity',
                'total_amount' => 'Total',
                'status' => 'Status',
                'order_date' => 'Order Date',
                'delivery_address' => 'Address'
            ]
        ];
    }

    /**
     * Get default upselling rules
     */
    public function getDefaultUpsellingRules(): array
    {
        return [
            'enabled' => true,
            'cross_sell_products' => [],
            'minimum_order_value' => 100,
            'discount_percentage' => 10,
            'bundle_offers' => [],
            'seasonal_promotions' => []
        ];
    }

    /**
     * Get default reminder settings
     */
    public function getDefaultReminderSettings(): array
    {
        return [
            'cart_abandonment' => [
                'enabled' => true,
                'reminder_intervals' => [1, 24, 72], // hours
                'discount_progression' => [5, 10, 15] // percentages
            ],
            'reorder_reminders' => [
                'enabled' => true,
                'days_after_delivery' => 30
            ],
            'stock_alerts' => [
                'enabled' => true,
                'notify_customers' => true
            ]
        ];
    }

    /**
     * Relationship with tenant
     */
    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    /**
     * Get products for this bot
     */
    public function products()
    {
        return $this->hasMany(Product::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Get orders for this bot
     */
    public function orders()
    {
        return $this->hasMany(Order::class, 'tenant_id', 'tenant_id');
    }

    /**
     * Check if Google Sheets are configured
     */
    public function hasSheetsConfigured(): bool
    {
        return !empty($this->google_sheets_product_url) || !empty($this->google_sheets_order_url);
    }

    /**
     * Check if bot is ready for use
     */
    public function isReady(): bool
    {
        return $this->is_enabled && $this->hasSheetsConfigured();
    }
}
