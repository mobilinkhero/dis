<?php

namespace App\Models\Tenant;

use App\Models\BaseModel;
use App\Traits\BelongsToTenant;
use App\Traits\TracksFeatureUsage;

/**
 * E-commerce Configuration Model
 * Stores Google Sheets configuration and e-commerce settings for each tenant
 */
class EcommerceConfiguration extends BaseModel
{
    use BelongsToTenant, TracksFeatureUsage;

    protected $fillable = [
        'tenant_id',
        'is_configured',
        'google_sheets_url',
        'products_sheet_id',
        'orders_sheet_id',
        'customers_sheet_id',
        'payment_methods',
        'currency',
        'tax_rate',
        'shipping_settings',
        'order_confirmation_message',
        'payment_confirmation_message',
        'abandoned_cart_settings',
        'upselling_settings',
        'ai_recommendations_enabled',
        'sync_status',
        'last_sync_at',
        'configuration_completed_at',
    ];

    protected $casts = [
        'tenant_id' => 'int',
        'is_configured' => 'bool',
        'payment_methods' => 'json',
        'shipping_settings' => 'json',
        'abandoned_cart_settings' => 'json',
        'upselling_settings' => 'json',
        'ai_recommendations_enabled' => 'bool',
        'tax_rate' => 'decimal:2',
        'last_sync_at' => 'datetime',
        'configuration_completed_at' => 'datetime',
    ];

    /**
     * Get the feature slug for tracking usage
     */
    public function getFeatureSlug(): ?string
    {
        return 'ecommerce';
    }

    /**
     * Check if e-commerce is fully configured
     */
    public function isFullyConfigured(): bool
    {
        return $this->is_configured && 
               $this->google_sheets_url && 
               $this->products_sheet_id && 
               $this->orders_sheet_id;
    }

    /**
     * Get payment methods as array
     */
    public function getPaymentMethodsAttribute($value)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    /**
     * Get default abandoned cart settings
     */
    public function getDefaultAbandonedCartSettings(): array
    {
        return [
            'enabled' => true,
            'delay_hours' => [1, 6, 24],
            'discount_percentage' => [0, 5, 10],
            'messages' => [
                'Forgot something? Complete your order now!',
                'Still interested? Here\'s 5% off your cart!',
                'Last chance! 10% off expires soon!'
            ]
        ];
    }

    /**
     * Get default upselling settings
     */
    public function getDefaultUpsellingSettings(): array
    {
        return [
            'enabled' => true,
            'cross_sell_enabled' => true,
            'minimum_order_value' => 0,
            'upsell_percentage' => 20,
            'max_recommendations' => 3
        ];
    }
}
