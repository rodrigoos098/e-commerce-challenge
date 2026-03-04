<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\OrderService;
use App\Services\ProductService;
use Inertia\Inertia;
use Inertia\Response;

class AdminDashboardController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly OrderService $orderService,
    ) {
    }

    public function index(): Response
    {
        $totalProducts = $this->productService->totalCount();
        $totalOrders = $this->orderService->totalCount();
        $totalRevenue = $this->orderService->totalRevenue();
        $lowStockProducts = $this->productService->lowStock();
        $recentOrders = $this->orderService->recent(5);

        return Inertia::render('Admin/Dashboard', [
            'stats' => [
                'total_products' => $totalProducts,
                'total_orders' => $totalOrders,
                'total_revenue' => $totalRevenue,
                'low_stock_count' => $lowStockProducts->count(),
                'recent_orders' => $recentOrders->map(fn ($order) => [
                    'id' => $order->id,
                    'status' => $order->status,
                    'total' => (float) $order->total,
                    'created_at' => $order->created_at->toISOString(),
                    'user' => $order->user ? [
                        'id' => $order->user->id,
                        'name' => $order->user->name,
                        'email' => $order->user->email,
                    ] : null,
                ]),
                'low_stock_products' => $lowStockProducts->map(fn ($product) => [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'price' => (float) $product->price,
                    'quantity' => (int) $product->quantity,
                    'min_quantity' => (int) $product->min_quantity,
                    'active' => (bool) $product->active,
                    'category' => $product->category ? [
                        'id' => $product->category->id,
                        'name' => $product->category->name,
                    ] : null,
                ]),
            ],
        ]);
    }
}
