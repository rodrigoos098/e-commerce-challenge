<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Services\ProductService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly ProductService $productService,
    ) {}

    /**
     * List products with filters, search, sorting and pagination.
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'category_id', 'active', 'in_stock', 'low_stock',
            'min_price', 'max_price', 'sort_by', 'sort_dir',
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $products = $this->productService->paginate($filters, $perPage);

        return $this->paginatedResponse(ProductResource::collection($products));
    }

    /**
     * Display a specific product.
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (! $product) {
            return $this->notFoundResponse('Product not found.');
        }

        return $this->successResponse(new ProductResource($product));
    }

    /**
     * Store a new product (admin only).
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create(ProductDTO::fromRequest($request));

        return $this->createdResponse(new ProductResource($product));
    }

    /**
     * Update a product (admin only).
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $updated = $this->productService->update($product, ProductDTO::fromRequest($request));

        return $this->successResponse(new ProductResource($updated));
    }

    /**
     * Soft delete a product (admin only).
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return $this->successResponse(['message' => 'Product deleted successfully.']);
    }

    /**
     * List products with low stock (admin only).
     */
    public function lowStock(): JsonResponse
    {
        $products = $this->productService->lowStock();

        return $this->successResponse(ProductResource::collection($products));
    }
}
