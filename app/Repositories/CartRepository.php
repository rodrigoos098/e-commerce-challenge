<?php

namespace App\Repositories;

use App\Models\Cart;
use App\Models\CartItem;
use App\Repositories\Contracts\CartRepositoryInterface;

class CartRepository implements CartRepositoryInterface
{
    /**
     * Get or create a cart for the given user.
     */
    public function findOrCreateForUser(int $userId): Cart
    {
        /** @var Cart $cart */
        $cart = Cart::query()->firstOrCreate(
            ['user_id' => $userId],
            ['user_id' => $userId]
        );

        return $cart->load(['items.product']);
    }

    /**
     * Find a cart by user ID with items and products eager-loaded.
     */
    public function findByUserId(int $userId): ?Cart
    {
        return Cart::query()
            ->with(['items.product.category'])
            ->where('user_id', $userId)
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
}
