<?php

use App\Http\Controllers\Admin\AdminCategoryController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\AdminOrderController;
use App\Http\Controllers\Admin\AdminProductController;
use App\Http\Controllers\Admin\AdminStockController;
use App\Http\Controllers\AuthPageController;
use App\Http\Controllers\CartPageController;
use App\Http\Controllers\CheckoutPageController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\OrderPageController;
use App\Http\Controllers\ProductPageController;
use App\Http\Controllers\ProfilePageController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// ── Public Routes ─────────────────────────────────────────────────────────

Route::get('/', [HomeController::class, 'index'])->name('home');
Route::get('/products', [ProductPageController::class, 'index'])->name('products.index');
Route::get('/products/{slug}', [ProductPageController::class, 'show'])->name('products.show');

// ── Auth Routes ───────────────────────────────────────────────────────────

Route::middleware('guest')->group(function (): void {
    Route::get('/login', [AuthPageController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthPageController::class, 'login']);
    Route::get('/register', [AuthPageController::class, 'registerForm'])->name('register');
    Route::post('/register', [AuthPageController::class, 'register']);
});

Route::post('/logout', [AuthPageController::class, 'logout'])->middleware('auth')->name('logout');

// ── Authenticated Routes ──────────────────────────────────────────────────

Route::middleware('auth')->group(function (): void {
    // Cart
    Route::get('/cart', [CartPageController::class, 'index'])->name('cart');
    Route::post('/cart/items', [CartPageController::class, 'addItem'])->name('cart.items.add');
    Route::put('/cart/items/{item}', [CartPageController::class, 'updateItem'])->name('cart.items.update');
    Route::delete('/cart/items/{item}', [CartPageController::class, 'removeItem'])->name('cart.items.remove');
    Route::delete('/cart', [CartPageController::class, 'clear'])->name('cart.clear');

    // Customer area
    Route::prefix('customer')->group(function (): void {
        Route::get('/checkout', [CheckoutPageController::class, 'index'])->name('checkout');
        Route::get('/orders', [OrderPageController::class, 'index'])->name('orders.index');
        Route::get('/orders/{order}', [OrderPageController::class, 'show'])->name('orders.show');
        Route::post('/orders', [OrderPageController::class, 'store'])->name('orders.store');
        Route::get('/profile', [ProfilePageController::class, 'index'])->name('profile');
        Route::put('/profile', [ProfilePageController::class, 'update'])->name('profile.update');
        Route::put('/profile/password', [ProfilePageController::class, 'updatePassword'])->name('profile.password');
    });
});

// ── Admin Routes ──────────────────────────────────────────────────────────

Route::middleware(['auth', 'role:admin'])->prefix('admin')->group(function (): void {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('admin.dashboard');

    // Products CRUD
    Route::get('/products', [AdminProductController::class, 'index'])->name('admin.products.index');
    Route::get('/products/create', [AdminProductController::class, 'create'])->name('admin.products.create');
    Route::post('/products', [AdminProductController::class, 'store'])->name('admin.products.store');
    Route::get('/products/{product}', [AdminProductController::class, 'show'])->name('admin.products.show');
    Route::get('/products/{product}/edit', [AdminProductController::class, 'edit'])->name('admin.products.edit');
    Route::put('/products/{product}', [AdminProductController::class, 'update'])->name('admin.products.update');
    Route::delete('/products/{product}', [AdminProductController::class, 'destroy'])->name('admin.products.destroy');

    // Categories CRUD
    Route::get('/categories', [AdminCategoryController::class, 'index'])->name('admin.categories.index');
    Route::get('/categories/create', [AdminCategoryController::class, 'create'])->name('admin.categories.create');
    Route::post('/categories', [AdminCategoryController::class, 'store'])->name('admin.categories.store');
    Route::get('/categories/{category}/edit', [AdminCategoryController::class, 'edit'])->name('admin.categories.edit');
    Route::put('/categories/{category}', [AdminCategoryController::class, 'update'])->name('admin.categories.update');
    Route::delete('/categories/{category}', [AdminCategoryController::class, 'destroy'])->name('admin.categories.destroy');

    // Orders
    Route::get('/orders', [AdminOrderController::class, 'index'])->name('admin.orders.index');
    Route::get('/orders/{order}', [AdminOrderController::class, 'show'])->name('admin.orders.show');
    Route::put('/orders/{order}/status', [AdminOrderController::class, 'updateStatus'])->name('admin.orders.status');

    // Stock
    Route::get('/stock/low', [AdminStockController::class, 'lowStock'])->name('admin.stock.low');
});
