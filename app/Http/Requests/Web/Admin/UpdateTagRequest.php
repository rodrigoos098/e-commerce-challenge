<?php

namespace App\Http\Requests\Web\Admin;

use App\Models\Tag;

class UpdateTagRequest extends AdminFormRequest
{
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
