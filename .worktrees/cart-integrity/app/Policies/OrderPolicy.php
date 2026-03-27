<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\User;

class OrderPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view a specific order.
     * Admins can view any; customers only their own.
     */
    public function view(User $user, Order $order): bool
    {
        return $user->hasRole('admin') || $user->id === $order->user_id;
    }

    /**
     * Determine whether the user can create an order.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can update (admin only: status change).
     */
    public function update(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can delete an order.
     */
    public function delete(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can restore the model.
     */
    public function restore(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }

    /**
     * Determine whether the user can permanently delete the model.
     */
    public function forceDelete(User $user, Order $order): bool
    {
        return $user->hasRole('admin');
    }
}
