<?php

namespace App\Services;

use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class TagService
{
    /**
     * Get all tags ordered by name.
     */
    public function all(): Collection
    {
        return Tag::query()->orderBy('name')->get();
    }

    /**
     * Create a new tag.
     *
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): Tag
    {
        $data['slug'] = $this->generateUniqueSlug($data['slug'] ?? $data['name']);

        return Tag::query()->create($data);
    }

    /**
     * Update an existing tag.
     *
     * @param  array<string, mixed>  $data
     */
    public function update(Tag $tag, array $data): Tag
    {
        if (! empty($data['slug'])) {
            $data['slug'] = $this->generateUniqueSlug($data['slug'], $tag->id);
        } elseif (isset($data['name']) && $data['name'] !== $tag->name) {
            $data['slug'] = $this->generateUniqueSlug($data['name'], $tag->id);
        }

        $tag->update($data);

        return $tag->fresh();
    }

    /**
     * Delete a tag.
     */
    public function delete(Tag $tag): bool
    {
        return (bool) $tag->delete();
    }

    /**
     * Generate a unique slug for a tag.
     */
    private function generateUniqueSlug(string $value, ?int $exceptId = null): string
    {
        $slug = Str::slug($value);
        $originalSlug = $slug;
        $count = 1;

        while (Tag::query()
            ->where('slug', $slug)
            ->when($exceptId, fn ($query) => $query->whereKeyNot($exceptId))
            ->exists()) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }
}
