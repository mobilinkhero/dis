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
        Schema::create('ecommerce_bots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->nullable()->constrained()->onDelete('cascade');
            $table->boolean('is_enabled')->default(false);
            $table->text('google_sheets_product_url')->nullable();
            $table->text('google_sheets_order_url')->nullable();
            $table->string('sheets_product_id')->nullable();
            $table->string('sheets_order_id')->nullable();
            $table->json('sync_settings')->nullable();
            $table->json('upselling_rules')->nullable();
            $table->json('reminder_settings')->nullable();
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamps();

            $table->index(['tenant_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ecommerce_bots');
    }
};
