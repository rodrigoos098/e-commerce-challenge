<?php

namespace App\Http\Requests\Api\V1;

use App\Models\Tag;
use Illuminate\Foundation\Http\FormRequest;

class UpdateTagRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasRole('admin') ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $routeTag = $this->route('tag');
        $tagId = $routeTag instanceof Tag ? $routeTag->id : (int) $routeTag;

        return [
            'name' => ['sometimes', 'required', 'string', 'max:255', "unique:tags,name,{$tagId}"],
            'slug' => ['nullable', 'string', 'max:255', "unique:tags,slug,{$tagId}"],
        ];
    }
}
