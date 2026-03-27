<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\Product;
use Illuminate\Validation\Validator;

class UpdateProductRequest extends AdminFormRequest
{
    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routeProduct = $this->route('product');
        $productId = $routeProduct instanceof Product ? $routeProduct->id : (int) $routeProduct;

        return [
            'name' => ['sometimes', 'string', 'max:255', "unique:products,name,{$productId}"],
            'description' => ['sometimes', 'string'],
            'price' => ['sometimes', 'numeric', 'gt:0'],
            'cost_price' => ['nullable', 'numeric', 'gte:0'],
            'quantity' => ['sometimes', 'integer', 'min:0'],
            'min_quantity' => ['nullable', 'integer', 'min:0'],
            'category_id' => ['sometimes', 'integer', 'exists:categories,id'],
            'tags' => ['nullable', 'array'],
            'tags.*' => ['integer', 'exists:tags,id'],
            'active' => ['boolean'],
            'stock_adjustment_reason' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $product = $this->route('product');

            if (! $product instanceof Product) {
                return;
            }

            $validated = $validator->safe()->all();

            if (! array_key_exists('quantity', $validated)) {
                return;
            }

            $adjustedQuantity = $validated['quantity'] === null ? null : (int) $validated['quantity'];

            if ($adjustedQuantity !== null && $adjustedQuantity !== (int) $product->quantity && empty($validated['stock_adjustment_reason'])) {
                $validator->errors()->add('stock_adjustment_reason', 'Informe o motivo do ajuste de estoque.');
            }
        });
    }
}
