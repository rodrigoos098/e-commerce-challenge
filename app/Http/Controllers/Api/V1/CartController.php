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
    ) {}

    /**
     * Get the authenticated user's cart.
     */
    public function index(Request $request): JsonResponse
    {
        $cart = $this->cartService->getOrCreateForUser($request->user()->id);

        return $this->successResponse(new CartResource($cart));
    }

    /**
     * Add an item to the cart.
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
     */
    public function clear(Request $request): JsonResponse
    {
        $this->cartService->clear($request->user()->id);

        return $this->successResponse(['message' => 'Cart cleared successfully.']);
    }
}
