<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\ProductDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\StoreProductRequest;
use App\Http\Requests\Api\V1\UpdateProductRequest;
use App\Http\Resources\Api\V1\ProductResource;
use App\Models\Product;
use App\Services\CategoryService;
use App\Services\ProductService;
use App\Services\StockService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CategoryService $categoryService,
        private readonly ProductService $productService,
        private readonly StockService $stockService,
    ) {
    }

    /**
     * List products with filters, search, sorting and pagination.
     *
     * @OA\Get(
     *     path="/products",
     *     summary="Listar produtos",
     *     tags={"Produtos"},
     *     @OA\Parameter(name="search", in="query", description="Busca por nome ou descrição", @OA\Schema(type="string")),
     *     @OA\Parameter(name="category_id", in="query", description="Filtrar por categoria", @OA\Schema(type="integer")),
     *     @OA\Parameter(name="active", in="query", description="Filtrar por status ativo", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="in_stock", in="query", description="Filtrar com estoque disponível", @OA\Schema(type="boolean")),
     *     @OA\Parameter(name="min_price", in="query", @OA\Schema(type="number")),
     *     @OA\Parameter(name="max_price", in="query", @OA\Schema(type="number")),
     *     @OA\Parameter(name="sort_by", in="query", @OA\Schema(type="string", enum={"name","price","created_at","quantity"})),
     *     @OA\Parameter(name="sort_dir", in="query", @OA\Schema(type="string", enum={"asc","desc"})),
     *     @OA\Parameter(name="per_page", in="query", @OA\Schema(type="integer", default=15)),
     *     @OA\Response(response=200, description="Lista paginada de produtos")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $filters = $request->only([
            'search', 'category_id', 'active', 'in_stock', 'low_stock',
            'min_price', 'max_price', 'sort_by', 'sort_dir',
        ]);

        $perPage = (int) $request->input('per_page', 15);
        $products = $this->productService->paginatePublic($filters, $perPage);

        return $this->paginatedResponse(ProductResource::collection($products));
    }

    /**
     * Display a specific product.
     *
     * @OA\Get(
     *     path="/products/{id}",
     *     summary="Exibir produto",
     *     tags={"Produtos"},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Dados do produto"),
     *     @OA\Response(response=404, description="Produto não encontrado", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function show(int $id): JsonResponse
    {
        $product = $this->productService->findById($id);

        if (! $product || ! $product->active) {
            return $this->notFoundResponse('Product not found.');
        }

        if (! $this->categoryService->findPublicById($product->category_id)) {
            return $this->notFoundResponse('Product not found.');
        }

        return $this->successResponse(new ProductResource($product));
    }

    /**
     * Store a new product (admin only).
     *
     * @OA\Post(
     *     path="/products",
     *     summary="Criar produto (admin)",
     *     tags={"Produtos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"name","price","quantity","category_id"},
     *             @OA\Property(property="name", type="string", example="Playstation 5"),
     *             @OA\Property(property="description", type="string"),
     *             @OA\Property(property="price", type="number", example=3550.00),
     *             @OA\Property(property="cost_price", type="number", example=2800.00),
     *             @OA\Property(property="quantity", type="integer", example=100),
     *             @OA\Property(property="min_quantity", type="integer", example=10),
     *             @OA\Property(property="category_id", type="integer", example=1),
     *             @OA\Property(property="active", type="boolean", example=true),
     *             @OA\Property(property="tag_ids", type="array", @OA\Items(type="integer"))
     *         )
     *     ),
     *     @OA\Response(response=201, description="Produto criado com sucesso"),
     *     @OA\Response(response=403, description="Acesso negado"),
     *     @OA\Response(response=422, description="Dados inválidos", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $product = $this->productService->create(ProductDTO::fromRequest($request));

        return $this->createdResponse(new ProductResource($product));
    }

    /**
     * Update a product (admin only).
     *
     * @OA\Put(
     *     path="/products/{id}",
     *     summary="Atualizar produto (admin)",
     *     tags={"Produtos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(required=true, @OA\JsonContent(ref="#/components/schemas/SuccessResponse")),
     *     @OA\Response(response=200, description="Produto atualizado com sucesso"),
     *     @OA\Response(response=403, description="Acesso negado"),
     *     @OA\Response(response=404, description="Produto não encontrado")
     * )
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $validated = $request->validated();
        $adjustedQuantity = array_key_exists('quantity', $validated) ? (int) $validated['quantity'] : null;

        if ($adjustedQuantity !== null && $adjustedQuantity !== (int) $product->quantity && empty($validated['stock_adjustment_reason'])) {
            throw ValidationException::withMessages([
                'stock_adjustment_reason' => ['A stock adjustment reason is required when quantity changes.'],
            ]);
        }

        $dto = new ProductDTO(
            name: array_key_exists('name', $validated) ? (string) $validated['name'] : null,
            description: array_key_exists('description', $validated) ? (string) $validated['description'] : null,
            price: array_key_exists('price', $validated) ? (float) $validated['price'] : null,
            costPrice: array_key_exists('cost_price', $validated) ? (isset($validated['cost_price']) ? (float) $validated['cost_price'] : null) : null,
            quantity: null,
            minQuantity: array_key_exists('min_quantity', $validated) && $validated['min_quantity'] !== null ? (int) $validated['min_quantity'] : null,
            active: array_key_exists('active', $validated) && $validated['active'] !== null ? (bool) $validated['active'] : null,
            categoryId: array_key_exists('category_id', $validated) ? (int) $validated['category_id'] : null,
            tagIds: array_key_exists('tag_ids', $validated) ? $validated['tag_ids'] : null,
            slug: array_key_exists('slug', $validated) ? $validated['slug'] : null,
            presentFields: array_values(array_filter([
                array_key_exists('slug', $validated) ? 'slug' : null,
                array_key_exists('cost_price', $validated) ? 'cost_price' : null,
            ])),
        );

        $updated = $this->productService->update($product, $dto);

        if ($adjustedQuantity !== null && $adjustedQuantity !== (int) $product->fresh()->quantity) {
            $this->stockService->adjustStock(
                productId: $product->id,
                targetQuantity: $adjustedQuantity,
                reason: $validated['stock_adjustment_reason'],
            );

            $updated = $updated->fresh(['category', 'tags']) ?? $updated;
        }

        return $this->successResponse(new ProductResource($updated));
    }

    /**
     * Soft delete a product (admin only).
     *
     * @OA\Delete(
     *     path="/products/{id}",
     *     summary="Excluir produto (admin, soft delete)",
     *     tags={"Produtos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Produto excluído com sucesso"),
     *     @OA\Response(response=403, description="Acesso negado"),
     *     @OA\Response(response=404, description="Produto não encontrado")
     * )
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return $this->successResponse(['message' => 'Product deleted successfully.']);
    }

    /**
     * List products with low stock (admin only).
     *
     * @OA\Get(
     *     path="/products/low-stock",
     *     summary="Produtos com estoque baixo (admin)",
     *     tags={"Produtos"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Lista de produtos com estoque abaixo do mínimo"),
     *     @OA\Response(response=403, description="Acesso negado")
     * )
     */
    public function lowStock(): JsonResponse
    {
        $products = $this->productService->lowStock();

        return $this->successResponse(ProductResource::collection($products));
    }
}
