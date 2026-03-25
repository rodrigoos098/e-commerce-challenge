<?php

namespace App\Rules;

use App\Models\Category;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidParentCategory implements ValidationRule
{
    public function __construct(
        private readonly ?int $currentCategoryId = null,
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $parent = Category::query()->find($value);

        if (! $parent) {
            $fail('The selected parent category does not exist.');

            return;
        }

        if ($this->currentCategoryId !== null && (int) $value === $this->currentCategoryId) {
            $fail('A category cannot be its own parent.');

            return;
        }

        // Check for circular reference
        if ($this->currentCategoryId !== null && $this->isDescendantOf((int) $value, $this->currentCategoryId)) {
            $fail('Setting this parent would create a circular reference.');
        }
    }

    /**
     * Check if a category is a descendant of another.
     */
    private function isDescendantOf(int $categoryId, int $ancestorId): bool
    {
        $category = Category::query()->find($categoryId);

        if (! $category || $category->parent_id === null) {
            return false;
        }

        if ($category->parent_id === $ancestorId) {
            return true;
        }

        return $this->isDescendantOf($category->parent_id, $ancestorId);
    }
}
