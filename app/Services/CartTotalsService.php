<?php

namespace App\Services;

use Illuminate\Support\Collection;

class CartTotalsService
{
    /**
     * Calculate commercial totals from cart-like items.
     *
     * @param  iterable<int, object>  $items
     * @return array{subtotal: float, tax: float, shipping_cost: float, total: float}
     */
    public function calculate(iterable $items): array
    {
        $normalizedItems = Collection::make($items);
        $subtotal = round((float) $normalizedItems->sum(function (object $item): float {
            $unitPrice = (float) data_get($item, 'product.price', 0);
            $quantity = (int) data_get($item, 'quantity', 0);

            return $unitPrice * $quantity;
        }), 2);
        $tax = round($subtotal * 0.1, 2);
        $shippingCost = $this->calculateShipping($subtotal);

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_cost' => $shippingCost,
            'total' => round($subtotal + $tax + $shippingCost, 2),
        ];
    }

    private function calculateShipping(float $subtotal): float
    {
        if ($subtotal <= 0) {
            return 0.0;
        }

        if ($subtotal >= 200.0) {
            return 0.0;
        }

        return 19.9;
    }
}
