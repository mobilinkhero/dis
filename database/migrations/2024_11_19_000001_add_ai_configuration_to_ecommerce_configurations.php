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
            // AI Configuration
            $table->boolean('ai_powered_mode')->default(false)->after('ai_recommendations_enabled');
            $table->text('openai_api_key')->nullable()->after('ai_powered_mode');
            $table->string('openai_model', 50)->default('gpt-3.5-turbo')->after('openai_api_key');
            $table->decimal('ai_temperature', 2, 1)->default(0.7)->after('openai_model');
            $table->integer('ai_max_tokens')->default(500)->after('ai_temperature');
            
            // AI Behavior Settings
            $table->text('ai_system_prompt')->nullable()->after('ai_max_tokens');
            $table->text('ai_product_context')->nullable()->after('ai_system_prompt');
            $table->json('ai_response_templates')->nullable()->after('ai_product_context');
            
            // Direct Sheets Integration
            $table->boolean('direct_sheets_integration')->default(false)->after('ai_response_templates');
            $table->boolean('bypass_local_database')->default(false)->after('direct_sheets_integration');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ecommerce_configurations', function (Blueprint $table) {
            $table->dropColumn([
                'ai_powered_mode',
                'openai_api_key', 
                'openai_model',
                'ai_temperature',
                'ai_max_tokens',
                'ai_system_prompt',
                'ai_product_context',
                'ai_response_templates',
                'direct_sheets_integration',
                'bypass_local_database'
            ]);
        });
    }
};
