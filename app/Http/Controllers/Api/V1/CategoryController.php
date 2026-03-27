<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreCategoryRequest;
use App\Http\Requests\Api\V1\UpdateCategoryRequest;
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
    ) {
    }

    /**
     * List all categories as a hierarchical tree.
     *
     * @OA\Get(
     *     path="/categories",
     *     summary="Listar categorias (hierarquia em árvore)",
     *     tags={"Categorias"},
     *     @OA\Response(response=200, description="Árvore de categorias")
     * )
     */
    public function index(): JsonResponse
    {
        $categories = $this->categoryService->tree();

        return $this->successResponse(CategoryResource::collection($categories));
    }

    /**
     * Display a specific category.
     *
     * @OA\Get(
     *     path="/categories/{id}",
     *     summary="Exibir categoria",
     *     tags={"Categorias"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Dados da categoria"),
     *     @OA\Response(response=404, description="Categoria não encontrada")
     * )
     */
    public function show(int $id): JsonResponse
    {
        $category = $this->categoryService->findPublicById($id);

        if (! $category) {
            return $this->notFoundResponse('Category not found.');
        }

        return $this->successResponse(new CategoryResource($category));
    }

    /**
     * Create a new category (admin only).
     *
     * @OA\Post(
     *     path="/categories",
     *     summary="Criar categoria (admin)",
     *     tags={"Categorias"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name"},
     *             @OA\Property(property="name", type="string", example="Eletrônicos"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="parent_id", type="integer", nullable=true),
     *             @OA\Property(property="active", type="boolean", default=true)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Categoria criada com sucesso"),
     *     @OA\Response(response=403, description="Acesso negado")
     * )
     */
    public function store(StoreCategoryRequest $request): JsonResponse
    {
        $category = $this->categoryService->create($request->validated());

        return $this->createdResponse(new CategoryResource($category));
    }

    /**
     * Update an existing category (admin only).
     *
     * @OA\Put(
     *     path="/categories/{id}",
     *     summary="Atualizar categoria (admin)",
     *     tags={"Categorias"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Categoria atualizada com sucesso"),
     *     @OA\Response(response=403, description="Acesso negado"),
     *     @OA\Response(response=404, description="Categoria não encontrada")
     * )
     */
    public function update(UpdateCategoryRequest $request, Category $category): JsonResponse
    {
        $updated = $this->categoryService->update($category, $request->validated());

        return $this->successResponse(new CategoryResource($updated));
    }

    /**
     * Delete a category (admin only).
     *
     * @OA\Delete(
     *     path="/categories/{id}",
     *     summary="Excluir categoria (admin)",
     *     tags={"Categorias"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Categoria excluída"),
     *     @OA\Response(response=403, description="Acesso negado")
     * )
     */
    public function destroy(Category $category): JsonResponse
    {
        $this->categoryService->delete($category);

        return $this->successResponse(null);
    }

    /**
     * List products belonging to a category (paginated).
     *
     * @OA\Get(
     *     path="/categories/{id}/products",
     *     summary="Listar produtos da categoria",
     *     tags={"Categorias"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="search", in="query", @OA\Schema(type="string")),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Lista paginada de produtos da categoria"),
     *     @OA\Response(response=404, description="Categoria não encontrada")
     * )
     */
    public function products(Request $request, int $id): JsonResponse
    {
        $category = $this->categoryService->findPublicById($id);

        if (! $category) {
            return $this->notFoundResponse('Category not found.');
        }

        $perPage = (int) $request->input('per_page', 15);
        $filters = array_merge(
            $request->only(['search', 'in_stock', 'min_price', 'max_price', 'sort_by', 'sort_dir']),
            ['category_id' => $category->id],
        );

        $products = $this->productService->paginatePublic($filters, $perPage);

        return $this->paginatedResponse(ProductResource::collection($products));
    }
}
