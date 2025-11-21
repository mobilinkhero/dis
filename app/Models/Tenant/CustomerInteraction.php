<?php

namespace App\Models\Tenant;

use App\Models\BaseModel;

class CustomerInteraction extends BaseModel
{
    protected $fillable = [
        'tenant_id',
        'contact_id',
        'interaction_type',
        'interaction_data',
        'sentiment_analysis',
        'ai_insights',
        'session_id'
    ];

    protected $casts = [
        'interaction_data' => 'array',
        'sentiment_analysis' => 'array',
        'ai_insights' => 'array'
    ];

    /**
     * Get the contact that owns the interaction
     */
    public function contact()
    {
        return $this->belongsTo(Contact::class);
    }

    /**
     * Scope for specific interaction types
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('interaction_type', $type);
    }

    /**
     * Scope for recent interactions
     */
    public function scopeRecent($query, int $hours = 24)
    {
        return $query->where('created_at', '>', now()->subHours($hours));
    }
}
