<?php

namespace App\Http\Controllers;

use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductCollection;
use App\Http\Resources\Api\V1\ProductResource;
use App\Services\CategoryService;
use App\Services\ProductService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ProductPageController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category_id', 'price_min', 'price_max', 'min_price', 'max_price', 'active', 'in_stock']);
        $perPage = (int) $request->input('per_page', 15);

        // Map frontend filter names to backend filter names
        if (isset($filters['price_min'])) {
            $filters['min_price'] = $filters['price_min'];
            unset($filters['price_min']);
        }
        if (isset($filters['price_max'])) {
            $filters['max_price'] = $filters['price_max'];
            unset($filters['price_max']);
        }

        $products = $this->productService->paginate($filters, $perPage);
        $categories = $this->categoryService->tree();

        return Inertia::render('Products/Index', [
            'products' => (new ProductCollection($products))->response()->getData(true),
            'categories' => CategoryResource::collection($categories)->toArray($request),
            'filters' => $request->only(['search', 'category_id', 'price_min', 'price_max']),
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $product = $this->productService->findBySlug($slug);

        if (! $product) {
            abort(404);
        }

        $relatedProducts = $this->productService->paginate([
            'category_id' => $product->category_id,
            'active' => true,
        ], 4);

        $related = collect($relatedProducts->items())->filter(fn ($p) => $p->id !== $product->id)->take(4)->values();

        return Inertia::render('Products/Show', [
            'product' => (new ProductResource($product))->toArray($request),
            'related_products' => ProductResource::collection($related)->toArray($request),
        ]);
    }
}
