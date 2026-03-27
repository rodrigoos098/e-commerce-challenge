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
        /** @var list<string> */
        public array $presentFields = [],
    ) {
    }

    /**
     * Create a ProductDTO from a Form Request.
     * Only fields present in the request payload are set (safe for partial updates).
     */
    public static function fromRequest(FormRequest $request): self
    {
        $input = $request->all();

        return new self(
            name: array_key_exists('name', $input) ? (string) $request->input('name') : null,
            description: array_key_exists('description', $input) ? (string) $request->input('description') : null,
            price: array_key_exists('price', $input) && $request->input('price') !== null ? (float) $request->input('price') : null,
            costPrice: array_key_exists('cost_price', $input) && $request->input('cost_price') !== null ? (float) $request->input('cost_price') : null,
            quantity: array_key_exists('quantity', $input) && $request->input('quantity') !== null ? (int) $request->input('quantity') : null,
            minQuantity: array_key_exists('min_quantity', $input) && $request->input('min_quantity') !== null ? (int) $request->input('min_quantity') : null,
            active: array_key_exists('active', $input) && $request->input('active') !== null ? (bool) $request->input('active') : null,
            categoryId: array_key_exists('category_id', $input) && $request->input('category_id') !== null ? (int) $request->input('category_id') : null,
            tagIds: array_key_exists('tag_ids', $input) ? $request->input('tag_ids', []) : null,
            slug: array_key_exists('slug', $input) ? $request->input('slug') : null,
            presentFields: array_values(array_intersect(array_keys($input), [
                'name',
                'slug',
                'description',
                'price',
                'cost_price',
                'quantity',
                'min_quantity',
                'active',
                'category_id',
            ])),
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
        ], fn ($value, string $key) => $value !== null || in_array($key, $this->presentFields, true), ARRAY_FILTER_USE_BOTH);
    }
}
