<?php

namespace App\Http\Resources\Api\V1;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CartResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $cartTotalsService = app(\App\Services\CartTotalsService::class);
        $addresses = collect();

        if ($request->user()) {
            $addresses = $request->user()->addresses()
                ->orderByDesc('is_default_shipping')
                ->latest()
                ->get(['zip_code', 'is_default_shipping']);
        }

        $shippingZipCode = $cartTotalsService->resolveShippingZipCode(addresses: $addresses);

        $totals = $this->whenLoaded('items', fn () => $cartTotalsService->calculate($this->items, $shippingZipCode));

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenLoaded('items', fn () => $this->items->count()),
            'subtotal' => $this->whenLoaded('items', fn () => $totals['subtotal']),
            'tax' => $this->whenLoaded('items', fn () => $totals['tax']),
            'shipping_cost' => $this->whenLoaded('items', fn () => $totals['shipping_cost']),
            'total' => $this->whenLoaded('items', fn () => $totals['total']),
            'shipping_zip_code' => $this->whenLoaded('items', fn () => $totals['shipping_zip_code']),
            'shipping_rule_label' => $this->whenLoaded('items', fn () => $totals['shipping_rule_label']),
            'shipping_rule_description' => $this->whenLoaded('items', fn () => $totals['shipping_rule_description']),
            'shipping_is_free' => $this->whenLoaded('items', fn () => $totals['shipping_is_free']),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
