<?php

use App\Http\Controllers\Tenant\ProductSalesController;
use Illuminate\Support\Facades\Route;

// Existing routes...

// Product Sales Routes
Route::prefix('product-sales')->name('product-sales.')->group(function () {
    Route::get('/', [ProductSalesController::class, 'index'])->name('index');
    Route::get('/products', [ProductSalesController::class, 'getProducts'])->name('products');
    Route::get('/recommendations', [ProductSalesController::class, 'getRecommendations'])->name('recommendations');
    Route::post('/checkout', [ProductSalesController::class, 'processCheckout'])->name('checkout');
    Route::post('/send-catalog', [ProductSalesController::class, 'sendCatalogViaWhatsApp'])->name('send-catalog');
});

// API Routes for AJAX calls
Route::prefix('api/products')->name('api.products.')->group(function () {
    Route::get('/search', [ProductSalesController::class, 'getProducts'])->name('search');
    Route::get('/recommendations/{type}', [ProductSalesController::class, 'getRecommendations'])->name('recommendations');
    Route::post('/ai-recommendations', [ProductSalesController::class, 'getRecommendations'])->name('ai-recommendations');
});
