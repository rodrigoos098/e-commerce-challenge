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
        $featuredProducts = $this->productService->paginatePublic(['in_stock' => true], 8);
        $categories = $this->categoryService->tree();

        // Add product counts for homepage category cards, including subcategories
        $categoryModels = \App\Models\Category::query()
            ->where('active', true)
            ->whereNull('parent_id')
            ->with(['children' => function ($query) {
                $query->withCount(['products' => fn ($q) => $q->where('active', true)]);
            }])
            ->withCount(['products' => fn ($q) => $q->where('active', true)])
            ->get()
            ->map(function ($cat) {
                $cat->total_products_count = $cat->products_count + $cat->children->sum('products_count');
                return $cat;
            })
            ->sortByDesc('total_products_count')
            ->values();

        return Inertia::render('Home', [
            'featured_products' => ProductResource::collection($featuredProducts->items())->toArray($request),
            'categories' => $categoryModels->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'products_count' => $cat->total_products_count, // Use total included children
            ])->toArray(),
            'stats' => [
                'product_count' => Product::query()->where('active', true)->count(),
                'category_count' => count($categories),
            ],
        ]);
    }
}
