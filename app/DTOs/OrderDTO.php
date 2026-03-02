<?php

namespace App\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class OrderDTO
{
    /**
     * @param  array<string, mixed>  $shippingAddress
     * @param  array<string, mixed>  $billingAddress
     */
    public function __construct(
        public int $userId,
        public array $shippingAddress,
        public array $billingAddress,
        public ?string $notes = null,
    ) {}

    /**
     * Create an OrderDTO from a Form Request.
     */
    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            userId: $request->user()->id,
            shippingAddress: $request->input('shipping_address', []),
            billingAddress: $request->input('billing_address', []),
            notes: $request->input('notes'),
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
            'user_id' => $this->userId,
            'shipping_address' => $this->shippingAddress,
            'billing_address' => $this->billingAddress,
            'notes' => $this->notes,
        ];
    }
}
