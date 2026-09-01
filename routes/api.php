<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Models\Product;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\OrderController;
use App\Http\Middleware\SecretAdminMiddleware;

// --- ROUTE PUBLIC ORDERS (Pelanggan) ---
Route::post('/validate-qr', [OrderController::class, 'validateTableQr']);
Route::post('/orders', [OrderController::class, 'createOrder']);
Route::get('/orders/batch', [OrderController::class, 'getMultipleOrders']);
Route::get('/orders/{id}', [OrderController::class, 'show']);
Route::get('/orders/{id}/status', [OrderController::class, 'getOrderStatus']);
Route::patch('/orders/{id}/payment', [OrderController::class, 'selectPaymentMethod']); // Disamakan jadi /payment
Route::patch('/orders/{id}/cancel', [OrderController::class, 'cancelOrder']);

// --- ROUTE PRODUCTS ---
Route::get('/products', [ProductController::class, 'index']);

// --- PROTECTED ADMIN / KITCHEN ROUTES ---
Route::middleware(SecretAdminMiddleware::class)->group(function () {
    // Admin Products
    Route::post('/products', [ProductController::class, 'store']);
    Route::put('/products/{id}', [ProductController::class, 'update']);
    Route::delete('/products/{id}', [ProductController::class, 'destroy']);
    
    // Kitchen & Order Management
    Route::get('/kitchen/orders', [OrderController::class, 'getKitchenOrders']);
    Route::patch('/orders/{id}/update-status', [OrderController::class, 'updateOrderStatus']);
});

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');