<?php

namespace App\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class CartItemDTO
{
    public function __construct(
        public int $productId,
        public int $quantity,
    ) {
    }

    /**
     * Create a CartItemDTO from a Form Request.
     */
    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            productId: (int) $request->input('product_id'),
            quantity: (int) $request->input('quantity', 1),
        );
    }

    /**
     * Convert DTO to array for Eloquent operations.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'product_id' => $this->productId,
            'quantity' => $this->quantity,
        ];
    }
}
