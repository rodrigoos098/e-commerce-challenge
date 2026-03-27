<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\ProductResource;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminStockController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
    ) {
    }

    public function lowStock(Request $request): Response
    {
        $products = $this->productService->lowStock();

        return Inertia::render('Admin/Stock/LowStock', [
            'products' => ProductResource::collection($products)->toArray($request),
        ]);
    }
}
