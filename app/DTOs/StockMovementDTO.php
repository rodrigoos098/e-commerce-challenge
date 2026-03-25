<?php

namespace App\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class StockMovementDTO
{
    public function __construct(
        public int $productId,
        public string $type,
        public int $quantity,
        public string $reason,
        public ?string $referenceType = null,
        public ?int $referenceId = null,
    ) {
    }

    /**
     * Create a StockMovementDTO from a Form Request.
     */
    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            productId: (int) $request->input('product_id'),
            type: $request->string('type')->toString(),
            quantity: (int) $request->input('quantity'),
            reason: $request->string('reason')->toString(),
            referenceType: $request->input('reference_type'),
            referenceId: $request->input('reference_id') !== null ? (int) $request->input('reference_id') : null,
        );
    }

    /**
     * Convert DTO to array for Eloquent operations.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter([
            'product_id' => $this->productId,
            'type' => $this->type,
            'quantity' => $this->quantity,
            'reason' => $this->reason,
            'reference_type' => $this->referenceType,
            'reference_id' => $this->referenceId,
        ], fn ($value) => $value !== null);
    }
}
