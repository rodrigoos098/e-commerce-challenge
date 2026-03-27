<?php

namespace App\Services;

use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Support\Collection;

class CartTotalsService
{
    public function __construct(
        private readonly CartPricingService $cartPricingService,
    ) {
    }

    /**
     * Calculate commercial totals from cart-like items.
     *
     * @param  iterable<int, object>  $items
     * @return array{subtotal: float, tax: float, shipping_cost: float, total: float, shipping_zip_code: ?string, shipping_rule_label: string, shipping_rule_description: string, shipping_is_free: bool}
     */
    public function calculate(iterable $items, ?string $shippingZipCode = null): array
    {
        $normalizedItems = Collection::make($items);
        $subtotal = round((float) $normalizedItems->sum(function (object $item): float {
            $unitPrice = (float) data_get($item, 'product.price', 0);
            $quantity = (int) data_get($item, 'quantity', 0);

            return $unitPrice * $quantity;
        }), 2);
        $tax = round($subtotal * 0.1, 2);
        $shippingQuote = $this->cartPricingService->calculateShipping($subtotal, $shippingZipCode);
        $shippingCost = $shippingQuote['cost'];

        return [
            'subtotal' => $subtotal,
            'tax' => $tax,
            'shipping_cost' => $shippingCost,
            'total' => round($subtotal + $tax + $shippingCost, 2),
            'shipping_zip_code' => $shippingQuote['zip_code'],
            'shipping_rule_label' => $shippingQuote['rule_label'],
            'shipping_rule_description' => $shippingQuote['rule_description'],
            'shipping_is_free' => $shippingQuote['is_free'],
        ];
    }

    /**
     * Resolve the shipping CEP used as backend source of truth.
     *
     * @param  iterable<int, Arrayable|object|array<string, mixed>>  $addresses
     */
    public function resolveShippingZipCode(?string $preferredZipCode = null, iterable $addresses = [], bool $fallbackToAddresses = true): ?string
    {
        $normalizedPreferredZipCode = $this->cartPricingService->normalizeZipCode($preferredZipCode);

        if ($normalizedPreferredZipCode !== null) {
            return $normalizedPreferredZipCode;
        }

        if (! $fallbackToAddresses) {
            return null;
        }

        /** @var Collection<int, array<string, mixed>> $normalizedAddresses */
        $normalizedAddresses = Collection::make($addresses)->map(function (mixed $address): array {
            if ($address instanceof Arrayable) {
                $address = $address->toArray();
            }

            if (is_object($address)) {
                $address = (array) $address;
            }

            return is_array($address) ? $address : [];
        });

        /** @var array<string, mixed>|null $defaultShippingAddress */
        $defaultShippingAddress = $normalizedAddresses->first(fn (array $address): bool => (bool) data_get($address, 'is_default_shipping', false));
        /** @var array<string, mixed>|null $fallbackAddress */
        $fallbackAddress = $normalizedAddresses->first();

        return $this->cartPricingService->normalizeZipCode(
            (string) data_get($defaultShippingAddress ?? $fallbackAddress, 'zip_code', ''),
        );
    }
}
