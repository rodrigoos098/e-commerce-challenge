<?php

namespace App\Services;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Product;
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
     * Get the cart for a session (create if not exists).
     */
    public function getOrCreateForSession(string $sessionId): Cart
    {
        return $this->cartRepository->findOrCreateForSession($sessionId);
    }

    /**
     * Get the current cart for the active context.
     */
    public function getOrCreateForContext(?int $userId, string $sessionId): Cart
    {
        if ($userId !== null) {
            return $this->getOrCreateForUser($userId);
        }

        return $this->getOrCreateForSession($sessionId);
    }

    /**
     * Add an item to the cart.
     *
     * @throws ValidationException
     */
    public function addItem(int $userId, int $productId, int $quantity): CartItem
    {
        return $this->addItemForContext($userId, null, $productId, $quantity);
    }

    /**
     * Add an item to the cart for the current context.
     *
     * @throws ValidationException
     */
    public function addItemForContext(?int $userId, ?string $sessionId, int $productId, int $quantity): CartItem
    {
        return DB::transaction(function () use ($userId, $sessionId, $productId, $quantity): CartItem {
            $product = $this->productRepository->findById($productId);

            if (! $product || ! $product->active) {
                throw ValidationException::withMessages([
                    'product_id' => ['Product not found or inactive.'],
                ]);
            }

            $cart = $userId !== null
                ? $this->cartRepository->findOrCreateForUser($userId)
                : $this->cartRepository->findOrCreateForSession((string) $sessionId);
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
     * Clear the current cart context.
     */
    public function clearForContext(?int $userId, string $sessionId): bool
    {
        $cart = $userId !== null
            ? $this->cartRepository->findByUserId($userId)
            : $this->cartRepository->findBySessionId($sessionId);

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

    /**
     * Merge a session cart into the authenticated user's cart.
     */
    public function mergeSessionCartIntoUser(string $sessionId, int $userId, ?int $sessionCartId = null): Cart
    {
        return DB::transaction(function () use ($sessionId, $userId, $sessionCartId): Cart {
            $sessionCart = $this->cartRepository->findBySessionId($sessionId);

            if (! $sessionCart && $sessionCartId !== null) {
                $sessionCart = Cart::query()
                    ->with(['items.product.category'])
                    ->whereKey($sessionCartId)
                    ->whereNull('user_id')
                    ->first();
            }

            $userCart = $this->cartRepository->findOrCreateForUser($userId);

            if (! $sessionCart || $sessionCart->id === $userCart->id) {
                return $userCart;
            }

            if ($userCart->items->isEmpty()) {
                $this->cartRepository->deleteCart($userCart);
                $sessionCart->update([
                    'user_id' => $userId,
                    'session_id' => null,
                ]);

                return $sessionCart->fresh(['items.product.category']);
            }

            foreach ($sessionCart->items as $sessionItem) {
                $product = $this->productRepository->findById($sessionItem->product_id);

                if (! $product instanceof Product || ! $product->active || $product->quantity <= 0) {
                    continue;
                }

                $existingItem = $this->cartRepository->findItemByCartAndProduct($userCart, $sessionItem->product_id);
                $mergedQuantity = min(
                    $product->quantity,
                    (int) ($existingItem?->quantity ?? 0) + (int) $sessionItem->quantity,
                );

                if ($mergedQuantity <= 0) {
                    continue;
                }

                if ($existingItem) {
                    $this->cartRepository->updateItem($existingItem, $mergedQuantity);
                } else {
                    $this->cartRepository->addItem($userCart, $sessionItem->product_id, $mergedQuantity);
                }
            }

            $this->cartRepository->clear($sessionCart);
            $this->cartRepository->deleteCart($sessionCart);

            return $userCart->fresh(['items.product.category']);
        });
    }
}
