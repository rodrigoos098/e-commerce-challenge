<?php

namespace App\Repositories\Contracts;

use App\Models\Cart;
use App\Models\CartItem;

interface CartRepositoryInterface
{
    /**
     * Get or create a cart for the given user.
     */
    public function findOrCreateForUser(int $userId): Cart;

    /**
     * Find a cart by user ID with items and products eager-loaded.
     */
    public function findByUserId(int $userId): ?Cart;

    /**
     * Find a cart by user ID with a row lock for checkout finalization.
     */
    public function findByUserIdForUpdate(int $userId): ?Cart;

    /**
     * Get or create a cart for the given session.
     */
    public function findOrCreateForSession(string $sessionId): Cart;

    /**
     * Find a cart by session ID with items and products eager-loaded.
     */
    public function findBySessionId(string $sessionId): ?Cart;

    /**
     * Add an item to the cart (or update quantity if already exists).
     */
    public function addItem(Cart $cart, int $productId, int $quantity): CartItem;

    /**
     * Update the quantity of a cart item.
     */
    public function updateItem(CartItem $cartItem, int $quantity): CartItem;

    /**
     * Remove a cart item.
     */
    public function removeItem(CartItem $cartItem): bool;

    /**
     * Clear all items from the cart.
     */
    public function clear(Cart $cart): bool;

    /**
     * Find a specific cart item by ID.
     */
    public function findItemById(int $cartItemId): ?CartItem;

    /**
     * Find a cart item by cart and product.
     */
    public function findItemByCartAndProduct(Cart $cart, int $productId): ?CartItem;

    /**
     * Delete a cart record.
     */
    public function deleteCart(Cart $cart): bool;
}
