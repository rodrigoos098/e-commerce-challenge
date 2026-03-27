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
        $totals = $this->whenLoaded('items', fn () => app(\App\Services\CartTotalsService::class)->calculate($this->items));

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'items' => CartItemResource::collection($this->whenLoaded('items')),
            'items_count' => $this->whenLoaded('items', fn () => $this->items->count()),
            'subtotal' => $this->whenLoaded('items', fn () => $totals['subtotal']),
            'tax' => $this->whenLoaded('items', fn () => $totals['tax']),
            'shipping_cost' => $this->whenLoaded('items', fn () => $totals['shipping_cost']),
            'total' => $this->whenLoaded('items', fn () => $totals['total']),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
