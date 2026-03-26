<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\Contracts\ProductRepositoryInterface;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class CartService
{
    public function __construct(
        private readonly CartRepositoryInterface $cartRepository,
        private readonly ProductRepositoryInterface $productRepository,
    ) {
    }

    /**
     * Get the cart for a user (create if not exists).
     */
    public function getOrCreateForUser(int $userId): Cart
    {
        return $this->cartRepository->findOrCreateForUser($userId);
    }

    /**
     * Add an item to the cart.
     *
     * @throws ValidationException
     */
    public function addItem(int $userId, int $productId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($userId, $productId, $quantity): CartItem {
            $product = $this->productRepository->findById($productId);

            if (! $product || ! $product->active) {
                throw ValidationException::withMessages([
                    'product_id' => ['Product not found or inactive.'],
                ]);
            }

            $cart = $this->cartRepository->findOrCreateForUser($userId);
            $existingItem = $this->cartRepository->findItemByCartAndProduct($cart, $productId);
            $totalQuantity = $quantity + (int) ($existingItem?->quantity ?? 0);

            if ($product->quantity < $totalQuantity) {
                throw ValidationException::withMessages([
                    'quantity' => ["Insufficient stock. Only {$product->quantity} units available."],
                ]);
            }

            return $this->cartRepository->addItem($cart, $productId, $quantity);
        });
    }

    /**
     * Update a cart item quantity.
     *
     * @throws ValidationException
     */
    public function updateItem(CartItem $cartItem, int $quantity): CartItem
    {
        $product = $this->productRepository->findById($cartItem->product_id);

        if (! $product || ! $product->active) {
            throw ValidationException::withMessages([
                'quantity' => ['This cart item is no longer available.'],
            ]);
        }

        if ($product->quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => ["Insufficient stock. Only {$product->quantity} units available."],
            ]);
        }

        return $this->cartRepository->updateItem($cartItem, $quantity);
    }

    /**
     * Remove a cart item.
     */
    public function removeItem(CartItem $cartItem): bool
    {
        return $this->cartRepository->removeItem($cartItem);
    }

    /**
     * Clear the user's cart.
     */
    public function clear(int $userId): bool
    {
        $cart = $this->cartRepository->findByUserId($userId);

        if (! $cart) {
            return true;
        }

        return $this->cartRepository->clear($cart);
    }

    /**
     * Find a cart item by ID.
     */
    public function findItemById(int $cartItemId): ?CartItem
    {
        return $this->cartRepository->findItemById($cartItemId);
    }
}
