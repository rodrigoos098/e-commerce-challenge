<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Stubs — serão substituídas na Fase de Integração com dados reais.
| Estas rotas permitem que os Agentes 3 e 4 testem suas páginas
| visualmente durante o desenvolvimento.
|
*/

// Public routes
Route::get('/', fn () => Inertia::render('Home'));
Route::get('/products', fn () => Inertia::render('Products/Index'));
Route::get('/products/{slug}', fn () => Inertia::render('Products/Show'));
Route::get('/login', fn () => Inertia::render('Auth/Login'))->name('login');
Route::get('/register', fn () => Inertia::render('Auth/Register'));

// Authenticated routes
Route::middleware('auth')->group(function (): void {
    Route::get('/cart', fn () => Inertia::render('Customer/Cart'));
    Route::get('/checkout', fn () => Inertia::render('Customer/Checkout'));
    Route::get('/orders', fn () => Inertia::render('Customer/Orders/Index'));
    Route::get('/profile', fn () => Inertia::render('Customer/Profile'));
});

// Admin routes
Route::prefix('admin')->group(function (): void {
    Route::get('/dashboard', fn () => Inertia::render('Admin/Dashboard'));
    Route::get('/products', fn () => Inertia::render('Admin/Products/Index'));
    Route::get('/products/create', fn () => Inertia::render('Admin/Products/Create'));
    Route::get('/categories', fn () => Inertia::render('Admin/Categories/Index'));
    Route::get('/orders', fn () => Inertia::render('Admin/Orders/Index'));
    Route::get('/stock/low', fn () => Inertia::render('Admin/Stock/LowStock'));
});
