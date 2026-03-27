<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Contracts\CartRepositoryInterface;
use Illuminate\Database\MultipleRecordsFoundException;
use Illuminate\Database\QueryException;

class CartRepository implements CartRepositoryInterface
{
    /**
     * Get or create a cart for the given user.
     */
    public function findOrCreateForUser(int $userId): Cart
    {
        $cart = $this->findByUserId($userId);

        if ($cart) {
            return $cart;
        }

        try {
            $cart = $this->createCart($userId);
        } catch (QueryException $exception) {
            if (! $this->isUniqueUserCartViolation($exception)) {
                throw $exception;
            }

            $cart = $this->findByUserId($userId);

            if ($cart) {
                return $cart;
            }

            throw $exception;
        }

        return $cart->load(['items.product.category']);
    }

    public function findOrCreateForSession(string $sessionId): Cart
    {
        $cart = $this->findBySessionId($sessionId);

        if ($cart) {
            return $cart;
        }

        return $this->createCart(null, $sessionId)->load(['items.product.category']);
    }

    protected function createCart(?int $userId = null, ?string $sessionId = null): Cart
    {
        /** @var Cart $cart */
        $cart = Cart::query()->create([
            'user_id' => $userId,
            'session_id' => $sessionId,
        ]);

        return $cart;
    }

    /**
     * Find a cart by user ID with items and products eager-loaded.
     */
    public function findByUserId(int $userId): ?Cart
    {
        $carts = Cart::query()
            ->with(['items.product.category'])
            ->where('user_id', $userId)
            ->get();

        if ($carts->isEmpty()) {
            return null;
        }

        if ($carts->count() > 1) {
            throw new MultipleRecordsFoundException($carts->count());
        }

        /** @var Cart $cart */
        $cart = $carts->first();

        return $cart;
    }

    /**
     * Find a cart by user ID with items and products eager-loaded and locked.
     */
    public function findByUserIdForUpdate(int $userId): ?Cart
    {
        return Cart::query()
            ->with(['items.product.category'])
            ->where('user_id', $userId)
            ->lockForUpdate()
            ->first();
    }

    /**
     * Find a cart by session ID with items and products eager-loaded.
     */
    public function findBySessionId(string $sessionId): ?Cart
    {
        return Cart::query()
            ->with(['items.product.category'])
            ->where('session_id', $sessionId)
            ->first();
    }

    /**
     * Add an item to the cart (or update quantity if already exists).
     */
    public function addItem(Cart $cart, int $productId, int $quantity): CartItem
    {
        /** @var CartItem $cartItem */
        $cartItem = $cart->items()->where('product_id', $productId)->first();

        if ($cartItem) {
            $cartItem->update(['quantity' => $cartItem->quantity + $quantity]);
        } else {
            $cartItem = $cart->items()->create([
                'product_id' => $productId,
                'quantity' => $quantity,
            ]);
        }

        return $cartItem->load('product');
    }

    /**
     * Update the quantity of a cart item.
     */
    public function updateItem(CartItem $cartItem, int $quantity): CartItem
    {
        $cartItem->update(['quantity' => $quantity]);

        return $cartItem->fresh('product');
    }

    /**
     * Remove a cart item.
     */
    public function removeItem(CartItem $cartItem): bool
    {
        return (bool) $cartItem->delete();
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(Cart $cart): bool
    {
        return (bool) $cart->items()->delete();
    }

    /**
     * Find a specific cart item by ID.
     */
    public function findItemById(int $cartItemId): ?CartItem
    {
        return CartItem::query()->with(['cart', 'product'])->find($cartItemId);
    }

    /**
     * Find a cart item by cart and product.
     */
    public function findItemByCartAndProduct(Cart $cart, int $productId): ?CartItem
    {
        return $cart->items()->where('product_id', $productId)->first();
    }

    /**
     * Delete a cart record.
     */
    public function deleteCart(Cart $cart): bool
    {
        return (bool) $cart->delete();
    }

    protected function isUniqueUserCartViolation(QueryException $exception): bool
    {
        $sqlState = $exception->errorInfo[0] ?? null;
        $driverCode = $exception->errorInfo[1] ?? null;

        return $sqlState === '23000' || $sqlState === '23505' || $driverCode === 19;
    }
}
