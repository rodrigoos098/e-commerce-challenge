<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Product;
use App\Rules\UniqueSlug;
use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        $product = $this->route('product');

        if (! $product instanceof Product) {
            return false;
        }

        return $this->user()?->can('update', $product) ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routeProduct = $this->route('product');
        $productId = $routeProduct instanceof \App\Models\Product ? $routeProduct->id : (int) $routeProduct;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', "unique:products,name,{$productId}"],
            'slug' => ['nullable', 'string', 'max:255', new UniqueSlug($productId)],
            'description' => ['sometimes', 'required', 'string'],
            'price' => ['sometimes', 'required', 'numeric', 'gt:0'],
            'cost_price' => array_values(array_filter(['nullable', 'numeric', 'gt:0', $this->has('price') ? 'lt:price' : null])),
            'quantity' => ['sometimes', 'required', 'integer', 'min:0'],
            'min_quantity' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'category_id' => ['sometimes', 'required', 'integer', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
            'stock_adjustment_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * Get custom messages for validation errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'name.unique' => 'A product with this name already exists.',
            'price.gt' => 'The price must be greater than zero.',
            'cost_price.lt' => 'The cost price must be less than the selling price.',
            'category_id.exists' => 'The selected category does not exist.',
        ];
    }
}
