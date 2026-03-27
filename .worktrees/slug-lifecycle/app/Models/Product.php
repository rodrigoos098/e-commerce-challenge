<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;
    use SoftDeletes;

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
        'price',
        'cost_price',
        'quantity',
        'min_quantity',
        'active',
        'image_url',
        'category_id',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'cost_price' => 'decimal:2',
            'quantity' => 'integer',
            'min_quantity' => 'integer',
            'active' => 'boolean',
        ];
    }

    /**
     * Boot the model.
     */
    protected static function booted(): void
    {
        static::creating(function (Product $product): void {
            $product->slug = filled($product->slug)
                ? static::generateUniqueSlug($product->slug)
                : static::generateUniqueSlug($product->name);
        });

        static::updating(function (Product $product): void {
            if ($product->slugWasExplicitlySet) {
                if (filled($product->slug)) {
                    $product->slug = static::generateUniqueSlug($product->slug, $product->id);

                    return;
                }

                if ($product->isDirty('name')) {
                    $product->slug = static::generateUniqueSlug($product->name, $product->id);

                    return;
                }

                $product->slug = $product->getOriginal('slug');

                return;
            }

            if ($product->isDirty('name')) {
                $product->slug = static::generateUniqueSlug($product->name, $product->id);
            }
        });

        static::saved(function (Product $product): void {
            $product->slugWasExplicitlySet = false;
        });
    }

    public function setSlugAttribute(?string $value): void
    {
        $this->slugWasExplicitlySet = true;
        $this->attributes['slug'] = $value;
    }

    /**
     * Generate a unique slug for the product.
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
        $query = static::withTrashed()->where('slug', $slug);

        if ($exceptId !== null) {
            $query->whereKeyNot($exceptId);
        }

        return $query->exists();
    }

    /**
     * Get the category that owns the product.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Get the tags for the product.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class)->withTimestamps();
    }

    /**
     * Get the order items for the product.
     */
    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    /**
     * Get the stock movements for the product.
     */
    public function stockMovements(): HasMany
    {
        return $this->hasMany(StockMovement::class);
    }

    /**
     * Scope a query to only include active products.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('active', true);
    }

    /**
     * Scope a query to only include products in stock.
     */
    public function scopeInStock(Builder $query): Builder
    {
        return $query->where('quantity', '>', 0);
    }

    /**
     * Scope a query to only include products with low stock.
     */
    public function scopeLowStock(Builder $query): Builder
    {
        return $query->whereColumn('quantity', '<=', 'min_quantity');
    }
}
