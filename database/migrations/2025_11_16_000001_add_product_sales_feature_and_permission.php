<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Carbon\Carbon;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = Carbon::now();

        // Add product_sales feature
        DB::table('features')->updateOrInsert(
            ['slug' => 'product_sales'],
            [
                'name' => 'Product Sales',
                'slug' => 'product_sales',
                'description' => 'Enable product sales and e-commerce features',
                'type' => 'limit',
                'display_order' => 95,
                'default' => false,
                'created_at' => $now,
                'updated_at' => $now,
            ]
        );

        // Add product_sales permissions
        $permissions = [
            'tenant.product_sales.view',
            'tenant.product_sales.create',
            'tenant.product_sales.edit',
            'tenant.product_sales.delete',
        ];

        foreach ($permissions as $permission) {
            Permission::updateOrCreate([
                'name' => $permission,
                'guard_name' => 'web',
                'scope' => 'tenant',
            ]);
        }

        // Add product_sales feature to existing plans (optional - set to -1 for unlimited)
        $plans = DB::table('plans')->get();
        $feature = DB::table('features')->where('slug', 'product_sales')->first();

        if ($feature) {
            foreach ($plans as $plan) {
                // Set different limits based on plan type
                $limit = match($plan->slug) {
                    'free' => 0,          // Free plan: disabled
                    'basic' => 1,         // Basic plan: enabled
                    'premium' => -1,      // Premium plan: unlimited
                    'enterprise' => -1,   // Enterprise plan: unlimited
                    default => 1,         // Default: enabled
                };

                DB::table('plan_features')->updateOrInsert(
                    [
                        'plan_id' => $plan->id,
                        'feature_id' => $feature->id,
                    ],
                    [
                        'plan_id' => $plan->id,
                        'feature_id' => $feature->id,
                        'limit_value' => $limit,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ]
                );
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove permissions
        Permission::where('name', 'LIKE', 'tenant.product_sales.%')->delete();

        // Remove feature (this will also remove plan_features due to foreign key)
        DB::table('features')->where('slug', 'product_sales')->delete();
    }
};
