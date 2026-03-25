<?php

namespace App\DTOs;

use Illuminate\Foundation\Http\FormRequest;

readonly class ProductDTO
{
    /**
     * @param  array<int>|null  $tagIds
     */
    public function __construct(
        public ?string $name = null,
        public ?string $description = null,
        public ?float $price = null,
        public ?float $costPrice = null,
        public ?int $quantity = null,
        public ?int $minQuantity = null,
        public ?bool $active = null,
        public ?int $categoryId = null,
        public ?array $tagIds = null,
        public ?string $slug = null,
    ) {
    }

    /**
     * Create a ProductDTO from a Form Request.
     * Only fields present in the request payload are set (safe for partial updates).
     */
    public static function fromRequest(FormRequest $request): self
    {
        return new self(
            name: $request->has('name') ? $request->string('name')->toString() : null,
            description: $request->has('description') ? $request->string('description')->toString() : null,
            price: $request->has('price') ? (float) $request->input('price') : null,
            costPrice: $request->has('cost_price') ? (float) $request->input('cost_price') : null,
            quantity: $request->has('quantity') ? (int) $request->input('quantity') : null,
            minQuantity: $request->has('min_quantity') ? (int) $request->input('min_quantity') : null,
            active: $request->has('active') ? (bool) $request->input('active') : null,
            categoryId: $request->has('category_id') ? (int) $request->input('category_id') : null,
            tagIds: $request->has('tag_ids') ? $request->input('tag_ids', []) : null,
            slug: $request->has('slug') ? $request->input('slug') : null,
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
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'price' => $this->price,
            'cost_price' => $this->costPrice,
            'quantity' => $this->quantity,
            'min_quantity' => $this->minQuantity,
            'active' => $this->active,
            'category_id' => $this->categoryId,
        ], fn ($value) => $value !== null);
    }
}
