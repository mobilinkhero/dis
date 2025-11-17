<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('ecommerce_configurations', function (Blueprint $table) {
            // Customer details settings
            $table->json('required_customer_fields')->nullable()->after('google_sheets_url'); // ['name', 'address', 'city', 'phone', 'email', 'notes']
            
            // Payment methods settings
            $table->json('enabled_payment_methods')->nullable()->after('required_customer_fields'); // ['cod', 'bank_transfer', 'card', 'online']
            $table->json('payment_method_responses')->nullable()->after('enabled_payment_methods'); // Custom responses for each method
            
            // Default settings
            $table->boolean('collect_customer_details')->default(true)->after('payment_method_responses');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'required_customer_fields',
                'enabled_payment_methods', 
                'payment_method_responses',
                'collect_customer_details'
            ]);
        });
    }
};
