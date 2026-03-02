<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Api\V1\CategoryResource;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Category;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ProductService $productService,
    ) {}

    /**
     * List all categories as a hierarchical tree.
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->tree();

        return $this->successResponse(CategoryResource::collection($categories));
    }

    /**
     * Display a specific category.
     */
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->findById($id);

        if (! $category) {
            return $this->notFoundResponse('Category not found.');
        }

        return $this->successResponse(new CategoryResource($category));
    }

    /**
     * List products belonging to a category (paginated).
     */
    public function products(Request $request, Category $category): JsonResponse
    {
        $perPage = (int) $request->input('per_page', 15);
        $filters = array_merge(
            $request->only(['search', 'in_stock', 'min_price', 'max_price', 'sort_by', 'sort_dir']),
            ['category_id' => $category->id, 'active' => true],
        );

        $products = $this->productService->paginate($filters, $perPage);

        return $this->paginatedResponse(ProductResource::collection($products));
    }
}
