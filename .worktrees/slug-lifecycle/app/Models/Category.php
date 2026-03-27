<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    /** @use HasFactory<\Database\Factories\CategoryFactory> */
    use HasFactory;

    private bool $slugWasExplicitlySet = false;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'active',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'active' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Category $category): void {
            $category->slug = filled($category->slug)
                ? static::generateUniqueSlug($category->slug)
                : static::generateUniqueSlug($category->name);
        });

        static::updating(function (Category $category): void {
            if ($category->slugWasExplicitlySet) {
                if (filled($category->slug)) {
                    $category->slug = static::generateUniqueSlug($category->slug, $category->id);

                    return;
                }

                if ($category->isDirty('name')) {
                    $category->slug = static::generateUniqueSlug($category->name, $category->id);

                    return;
                }

                $category->slug = $category->getOriginal('slug');

                return;
            }

            if ($category->isDirty('name')) {
                $category->slug = static::generateUniqueSlug($category->name, $category->id);
            }
        });

        static::saved(function (Category $category): void {
            $category->slugWasExplicitlySet = false;
        });
    }

    public function setSlugAttribute(?string $value): void
    {
        $this->slugWasExplicitlySet = true;
        $this->attributes['slug'] = $value;
    }

    /**
     * Generate a unique slug for the category.
     */
    private static function generateUniqueSlug(string $value, ?int $exceptId = null): string
    {
        $slug = Str::slug($value);
        $originalSlug = $slug;
        $count = 1;

        while (static::slugExists($slug, $exceptId)) {
            $slug = "{$originalSlug}-{$count}";
            $count++;
        }

        return $slug;
    }

    /**
     * Determine whether the slug already exists.
     */
    private static function slugExists(string $slug, ?int $exceptId = null): bool
    {
        $query = static::query()->where('slug', $slug);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        return $query->exists();
    }

    /**
     * Get the parent category.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Get the child categories.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Get the products for the category.
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }
}
