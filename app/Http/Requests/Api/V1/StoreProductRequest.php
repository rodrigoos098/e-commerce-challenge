<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\UniqueSlug;
use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255', 'unique:products,name'],
            'slug' => ['nullable', 'string', 'max:255', new UniqueSlug()],
            'description' => ['required', 'string'],
            'price' => ['required', 'numeric', 'gt:0'],
            'cost_price' => ['nullable', 'numeric', 'gt:0', 'lt:price'],
            'quantity' => ['required', 'integer', 'min:0'],
            'min_quantity' => ['nullable', 'integer', 'min:0'],
            'active' => ['nullable', 'boolean'],
            'category_id' => ['required', 'integer', 'exists:categories,id'],
            'tag_ids' => ['nullable', 'array'],
            'tag_ids.*' => ['integer', 'exists:tags,id'],
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
            'tag_ids.*.exists' => 'One or more selected tags do not exist.',
        ];
    }
}
