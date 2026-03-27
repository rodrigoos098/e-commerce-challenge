<?php

namespace App\Http\Controllers\Admin;

use App\DTOs\ProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\Admin\StoreProductRequest;
use App\Http\Requests\Web\Admin\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductCollection;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\StockService;
use App\Services\TagService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AdminProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly CategoryService $categoryService,
        private readonly StockService $stockService,
        private readonly TagService $tagService,
    ) {
    }

    public function index(Request $request): Response
    {
        $filters = $request->only(['search', 'category_id', 'active']);
        $perPage = (int) $request->input('per_page', 15);

        $products = $this->productService->paginate($filters, $perPage);
        $categories = $this->categoryService->all();

        return Inertia::render('Admin/Products/Index', [
            'products' => (new ProductCollection($products))->response()->getData(true),
            'categories' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'parent_id' => $cat->parent_id,
                'active' => (bool) $cat->active,
            ])->toArray(),
            'filters' => $filters,
        ]);
    }

    public function create(): Response
    {
        $categories = $this->categoryService->all();
        $tags = $this->tagService->all();

        return Inertia::render('Admin/Products/Create', [
            'categories' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'parent_id' => $cat->parent_id,
                'active' => (bool) $cat->active,
            ])->toArray(),
            'tags' => $tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->toArray(),
        ]);
    }

    public function store(StoreProductRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $dto = new ProductDTO(
            name: $validated['name'],
            slug: null,
            description: $validated['description'],
            price: (float) $validated['price'],
            costPrice: isset($validated['cost_price']) ? (float) $validated['cost_price'] : null,
            quantity: (int) $validated['quantity'],
            minQuantity: isset($validated['min_quantity']) ? (int) $validated['min_quantity'] : 5,
            active: $validated['active'] ?? true,
            categoryId: (int) $validated['category_id'],
            tagIds: $validated['tags'] ?? null,
        );

        $this->productService->create($dto);

        return redirect('/admin/products')->with('success', 'Produto criado com sucesso!');
    }

    public function show(Request $request, Product $product): Response
    {
        $product->load(['category', 'tags']);
        $movements = $this->stockService->paginateForProduct($product->id, 50);

        return Inertia::render('Admin/Products/Show', [
            'product' => (new ProductResource($product))->resolve($request),
            'movements' => $movements->items() ? collect($movements->items())->map(fn ($m) => [
                'id' => $m->id,
                'product_id' => $m->product_id,
                'type' => $m->type,
                'quantity' => (int) $m->quantity,
                'notes' => $m->reason,
                'created_at' => $m->created_at->toISOString(),
            ])->toArray() : [],
        ]);
    }

    public function edit(Request $request, Product $product): Response
    {
        $product->load(['category', 'tags']);
        $categories = $this->categoryService->all();
        $tags = $this->tagService->all();

        return Inertia::render('Admin/Products/Edit', [
            'product' => (new ProductResource($product))->resolve($request),
            'categories' => $categories->map(fn ($cat) => [
                'id' => $cat->id,
                'name' => $cat->name,
                'slug' => $cat->slug,
                'parent_id' => $cat->parent_id,
                'active' => (bool) $cat->active,
            ])->toArray(),
            'tags' => $tags->map(fn ($tag) => [
                'id' => $tag->id,
                'name' => $tag->name,
                'slug' => $tag->slug,
            ])->toArray(),
        ]);
    }

    public function update(UpdateProductRequest $request, Product $product): RedirectResponse
    {
        $validated = $request->validated();

        $adjustedQuantity = array_key_exists('quantity', $validated) ? (int) $validated['quantity'] : null;

        $dto = new ProductDTO(
            name: $validated['name'] ?? null,
            slug: null,
            description: $validated['description'] ?? null,
            price: isset($validated['price']) ? (float) $validated['price'] : null,
            costPrice: array_key_exists('cost_price', $validated) ? (isset($validated['cost_price']) ? (float) $validated['cost_price'] : null) : null,
            quantity: null,
            minQuantity: isset($validated['min_quantity']) ? (int) $validated['min_quantity'] : null,
            active: $validated['active'] ?? null,
            categoryId: isset($validated['category_id']) ? (int) $validated['category_id'] : null,
            tagIds: $validated['tags'] ?? null,
            presentFields: array_key_exists('cost_price', $validated) ? ['cost_price'] : [],
        );

        $this->productService->update($product, $dto);

        if ($adjustedQuantity !== null && $adjustedQuantity !== (int) $product->fresh()->quantity) {
            $this->stockService->adjustStock(
                productId: $product->id,
                targetQuantity: $adjustedQuantity,
                reason: (string) $validated['stock_adjustment_reason'],
            );
        }

        return redirect('/admin/products')->with('success', 'Produto atualizado com sucesso!');
    }

    public function destroy(Product $product): RedirectResponse
    {
        $this->productService->delete($product);

        return redirect('/admin/products')->with('success', 'Produto excluído com sucesso!');
    }
}
