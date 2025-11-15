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
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('tenant_id');
            $table->unsignedBigInteger('contact_id');
            $table->string('order_number')->unique();
            $table->decimal('total_amount', 10, 2);
            $table->enum('status', [
                'pending', 
                'confirmed', 
                'processing', 
                'shipped', 
                'delivered', 
                'cancelled'
            ])->default('pending');
            $table->json('items'); // Array of order items
            $table->text('shipping_address')->nullable();
            $table->text('notes')->nullable();
            $table->enum('source', ['whatsapp', 'website', 'manual'])->default('whatsapp');
            $table->timestamp('ordered_at')->nullable();
            $table->timestamp('status_updated_at')->nullable();
            $table->json('payment_info')->nullable();
            $table->timestamps();

            $table->index(['tenant_id', 'status']);
            $table->index(['tenant_id', 'contact_id']);
            $table->index(['tenant_id', 'order_number']);
            $table->index(['tenant_id', 'created_at']);
            $table->index(['tenant_id', 'source']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
