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
        $filters = $request->only(['search', 'category_id', 'active', 'in_stock']);

        if ($request->filled('price_min') || $request->filled('min_price')) {
            $filters['min_price'] = $request->input('price_min', $request->input('min_price'));
        }

        if ($request->filled('price_max') || $request->filled('max_price')) {
            $filters['max_price'] = $request->input('price_max', $request->input('max_price'));
        }

        $perPage = (int) $request->input('per_page', 15);

        $products = $this->productService->paginatePublic($filters, $perPage);
        $categories = $this->categoryService->tree();

        return Inertia::render('Products/Index', [
            'products' => (new ProductCollection($products))->response()->getData(true),
            'categories' => CategoryResource::collection($categories)->toArray($request),
            'filters' => [
                'search' => $request->input('search'),
                'category_id' => $request->input('category_id'),
                'price_min' => $request->input('price_min', $request->input('min_price')),
                'price_max' => $request->input('price_max', $request->input('max_price')),
                'page' => $request->input('page'),
            ],
        ]);
    }

    public function show(Request $request, string $slug): Response
    {
        $product = $this->productService->findBySlug($slug);

        if (! $product || ! $product->active) {
            abort(404);
        }

        if (! $this->categoryService->findPublicById($product->category_id)) {
            abort(404);
        }

        $relatedProducts = $this->productService->paginatePublic([
            'category_id' => $product->category_id,
        ], 4);

        $related = collect($relatedProducts->items())->filter(fn ($p) => $p->id !== $product->id)->take(4)->values();

        return Inertia::render('Products/Show', [
            'product' => (new ProductResource($product))->resolve($request),
            'related_products' => ProductResource::collection($related)->resolve($request),
        ]);
    }
}
