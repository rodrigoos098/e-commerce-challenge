<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;

class TagService
{
    /**
     * Get all tags ordered by name.
     */
    public function all(): Collection
    {
        return Tag::query()->orderBy('name')->get();
    }
}
