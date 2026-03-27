<?php

namespace App\Http\Requests\Web\Admin;

class StoreProductRequest extends AdminFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:products,name'],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'gt:0'],
            'cost_price' => ['nullable', 'numeric', 'gte:0', 'lt:price'],
            'quantity' => ['required', 'integer', 'min:0'],
            'min_quantity' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'active' => ['boolean'],
        ];
    }
}
