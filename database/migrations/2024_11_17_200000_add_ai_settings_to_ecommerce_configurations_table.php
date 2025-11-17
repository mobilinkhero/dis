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
            // AI Integration Settings
            $table->boolean('ai_enabled')->default(false)->after('ai_recommendations_enabled');
            $table->string('ai_provider')->default('openai')->after('ai_enabled'); // openai, claude, gemini, etc.
            $table->text('ai_api_key')->nullable()->after('ai_provider');
            $table->string('ai_model')->default('gpt-4')->after('ai_api_key'); // gpt-3.5-turbo, gpt-4, etc.
            $table->decimal('ai_temperature', 3, 2)->default(0.7)->after('ai_model'); // 0.0 to 1.0
            $table->integer('ai_max_tokens')->default(1000)->after('ai_temperature');
            
            // AI Behavior Settings
            $table->text('ai_system_prompt')->nullable()->after('ai_max_tokens');
            $table->text('ai_product_context')->nullable()->after('ai_system_prompt');
            $table->json('ai_conversation_memory')->nullable()->after('ai_product_context'); // Store recent conversations
            
            // AI Features
            $table->boolean('ai_product_recommendations')->default(true)->after('ai_conversation_memory');
            $table->boolean('ai_order_processing')->default(true)->after('ai_product_recommendations');
            $table->boolean('ai_customer_support')->default(true)->after('ai_order_processing');
            $table->boolean('ai_inventory_alerts')->default(false)->after('ai_customer_support');
            
            // AI Performance Settings
            $table->integer('ai_response_timeout')->default(30)->after('ai_inventory_alerts'); // seconds
            $table->boolean('ai_fallback_to_manual')->default(true)->after('ai_response_timeout');
            $table->text('ai_fallback_message')->nullable()->after('ai_fallback_to_manual');
            
            // AI Analytics
            $table->integer('ai_requests_count')->default(0)->after('ai_fallback_message');
            $table->decimal('ai_success_rate', 5, 2)->default(0)->after('ai_requests_count'); // percentage
            $table->timestamp('ai_last_used_at')->nullable()->after('ai_success_rate');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'ai_enabled',
                'ai_provider', 
                'ai_api_key',
                'ai_model',
                'ai_temperature',
                'ai_max_tokens',
                'ai_system_prompt',
                'ai_product_context',
                'ai_conversation_memory',
                'ai_product_recommendations',
                'ai_order_processing',
                'ai_customer_support',
                'ai_inventory_alerts',
                'ai_response_timeout',
                'ai_fallback_to_manual',
                'ai_fallback_message',
                'ai_requests_count',
                'ai_success_rate',
                'ai_last_used_at'
            ]);
        });
    }
};
