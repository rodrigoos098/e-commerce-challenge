<?php

namespace App\Http\Requests\Api\V1;

use App\Rules\ValidParentCategory;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCategoryRequest extends FormRequest
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
        $routeCategory = $this->route('category');
        $categoryId = $routeCategory instanceof \App\Models\Category ? $routeCategory->id : (int) $routeCategory;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', "unique:categories,name,{$categoryId}"],
            'slug' => ['nullable', 'string', 'max:255', "unique:categories,slug,{$categoryId}"],
            'description' => ['nullable', 'string'],
            'parent_id' => ['nullable', 'integer', new ValidParentCategory($categoryId)],
            'active' => ['nullable', 'boolean'],
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
            'name.unique' => 'A category with this name already exists.',
            'slug.unique' => 'A category with this slug already exists.',
        ];
    }
}
