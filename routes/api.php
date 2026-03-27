<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\CartController;
use App\Http\Controllers\Api\V1\CategoryController;
use App\Http\Controllers\Api\V1\OrderController;
use App\Http\Controllers\Api\V1\ProductController;
use App\Http\Controllers\Api\V1\TagController;
use App\Models\Order;
use App\Models\Product;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    // ── Public Routes ────────────────────────────────────────────────────────
    Route::prefix('auth')->group(function (): void {
        Route::post('register', [AuthController::class, 'register']);
        Route::post('login', [AuthController::class, 'login']);
    });

    Route::get('products', [ProductController::class, 'index']);
    Route::get('products/{id}', [ProductController::class, 'show'])->where('id', '[0-9]+');

    Route::get('categories', [CategoryController::class, 'index']);
    Route::get('categories/{category}', [CategoryController::class, 'show']);
    Route::get('categories/{category}/products', [CategoryController::class, 'products']);
    Route::get('tags', [TagController::class, 'index']);

    // ── Authenticated Routes ─────────────────────────────────────────────────
    Route::middleware('auth:sanctum')->group(function (): void {
        // Auth
        Route::post('auth/logout', [AuthController::class, 'logout']);
        Route::get('auth/me', [AuthController::class, 'me']);

        // Cart
        Route::get('cart', [CartController::class, 'index']);
        Route::post('cart/items', [CartController::class, 'addItem']);
        Route::put('cart/items/{itemId}', [CartController::class, 'updateItem']);
        Route::delete('cart/items/{itemId}', [CartController::class, 'removeItem']);
        Route::delete('cart', [CartController::class, 'clear']);

        // Orders
        Route::get('orders', [OrderController::class, 'index'])->middleware('can:viewAny,'.Order::class);
        Route::get('orders/{order}', [OrderController::class, 'show'])->middleware('can:view,order');
        Route::post('orders', [OrderController::class, 'store'])->middleware('can:create,'.Order::class);
        Route::put('orders/{order}/cancel', [OrderController::class, 'cancel'])->middleware('can:cancel,order');
        Route::put('orders/{order}/status', [OrderController::class, 'updateStatus'])->middleware('can:update,order');

        // Products (policy-backed admin mutations)
        Route::post('products', [ProductController::class, 'store'])->middleware('can:create,'.Product::class);
        Route::put('products/{product}', [ProductController::class, 'update'])->middleware('can:update,product');
        Route::delete('products/{product}', [ProductController::class, 'destroy'])->middleware('can:delete,product');

        Route::get('products/low-stock', [ProductController::class, 'lowStock'])
            ->middleware('can:viewLowStock,'.Product::class);

        // ── Admin Routes ─────────────────────────────────────────────────────
        Route::middleware('role:admin')->group(function (): void {
            // Categories (admin CRUD)
            Route::post('categories', [CategoryController::class, 'store']);
            Route::put('categories/{category}', [CategoryController::class, 'update']);
            Route::delete('categories/{category}', [CategoryController::class, 'destroy']);
            Route::post('tags', [TagController::class, 'store']);
            Route::put('tags/{tag}', [TagController::class, 'update']);
            Route::delete('tags/{tag}', [TagController::class, 'destroy']);
        });
    });
});
