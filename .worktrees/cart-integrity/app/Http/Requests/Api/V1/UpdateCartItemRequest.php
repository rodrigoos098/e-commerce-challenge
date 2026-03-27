<?php

namespace App\Http\Requests\Api\V1;

use App\Models\CartItem;
use App\Rules\SufficientStock;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCartItemRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $cartItem = CartItem::query()->find($this->route('itemId'));
        $productId = $cartItem?->product_id;

        return [
            'quantity' => ['required', 'integer', 'min:1', new SufficientStock($productId)],
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
            'quantity.min' => 'The quantity must be at least 1.',
        ];
    }
}
