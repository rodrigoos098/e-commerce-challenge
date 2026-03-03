<?php

namespace App\Http\Controllers\Api\V1;

use App\DTOs\CartItemDTO;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\V1\AddCartItemRequest;
use App\Http\Requests\Api\V1\UpdateCartItemRequest;
use App\Http\Resources\Api\V1\CartItemResource;
use App\Http\Resources\Api\V1\CartResource;
use App\Services\CartService;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CartController extends Controller
{
    use ApiResponseTrait;

    public function __construct(
        private readonly CartService $cartService,
    ) {
    }

    /**
     * Get the authenticated user's cart.
     *
     * @OA\Get(
     *     path="/cart",
     *     summary="Obter carrinho do usuário",
     *     tags={"Carrinho"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Carrinho com itens e totais"),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateForUser($request->user()->id);

        return $this->successResponse(new CartResource($cart));
    }

    /**
     * Add an item to the cart.
     *
     * @OA\Post(
     *     path="/cart/items",
     *     summary="Adicionar item ao carrinho",
     *     tags={"Carrinho"},
     *     security={{"bearerAuth":{}}},
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"product_id","quantity"},
     *             @OA\Property(property="product_id", type="integer", example=1),
     *             @OA\Property(property="quantity", type="integer", example=2)
     *         )
     *     ),
     *     @OA\Response(response=201, description="Item adicionado ao carrinho"),
     *     @OA\Response(response=422, description="Dados inválidos ou estoque insuficiente", @OA\JsonContent(ref="#/components/schemas/ErrorResponse"))
     * )
     */
    public function addItem(AddCartItemRequest $request): JsonResponse
    {
        $dto = CartItemDTO::fromRequest($request);
        $cartItem = $this->cartService->addItem(
            $request->user()->id,
            $dto->productId,
            $dto->quantity,
        );

        return $this->createdResponse(new CartItemResource($cartItem));
    }

    /**
     * Update a cart item's quantity.
     *
     * @OA\Put(
     *     path="/cart/items/{id}",
     *     summary="Atualizar quantidade de item no carrinho",
     *     tags={"Carrinho"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"quantity"},
     *             @OA\Property(property="quantity", type="integer", example=3)
     *         )
     *     ),
     *     @OA\Response(response=200, description="Item atualizado"),
     *     @OA\Response(response=404, description="Item não encontrado")
     * )
     */
    public function updateItem(UpdateCartItemRequest $request, int $itemId): JsonResponse
    {
        $cartItem = $this->cartService->findItemById($itemId);

        if (! $cartItem || $cartItem->cart->user_id !== $request->user()->id) {
            return $this->notFoundResponse('Cart item not found.');
        }

        $updated = $this->cartService->updateItem($cartItem, (int) $request->input('quantity'));

        return $this->successResponse(new CartItemResource($updated));
    }

    /**
     * Remove an item from the cart.
     *
     * @OA\Delete(
     *     path="/cart/items/{id}",
     *     summary="Remover item do carrinho",
     *     tags={"Carrinho"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="integer")),
     *     @OA\Response(response=200, description="Item removido"),
     *     @OA\Response(response=404, description="Item não encontrado")
     * )
     */
    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $cartItem = $this->cartService->findItemById($itemId);

        if (! $cartItem || $cartItem->cart->user_id !== $request->user()->id) {
            return $this->notFoundResponse('Cart item not found.');
        }

        $this->cartService->removeItem($cartItem);

        return $this->successResponse(['message' => 'Item removed from cart.']);
    }

    /**
     * Clear all items from the cart.
     *
     * @OA\Delete(
     *     path="/cart",
     *     summary="Limpar carrinho",
     *     tags={"Carrinho"},
     *     security={{"bearerAuth":{}}},
     *     @OA\Response(response=200, description="Carrinho limpo com sucesso"),
     *     @OA\Response(response=401, description="Não autenticado")
     * )
     */
    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clear($request->user()->id);

        return $this->successResponse(['message' => 'Cart cleared successfully.']);
    }
}
