<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class HomeController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
    ) {
    }

    public function index(Request $request): Response
    {
        $featuredProducts = $this->productService->paginate(['active' => true, 'in_stock' => true], 8);
        $categories = $this->categoryService->tree();

        // Add product counts for homepage category cards
        $categoryModels = \App\Models\Category::query()
            ->where('active', true)
            ->withCount('products')
            ->orderByDesc('products_count')
            ->get();

        return Inertia::render('Home', [
            'featured_products' => ProductResource::collection($featuredProducts->items())->toArray($request),
            'categories' => $categoryModels->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'products_count' => $cat->products_count,
            ])->toArray(),
            'stats' => [
                'product_count' => Product::query()->where('active', true)->count(),
                'category_count' => count($categories),
            ],
        ]);
    }
}
