<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class UniqueSlug implements ValidationRule
{
    public function __construct(
        private readonly ?int $exceptId = null,
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        $query = Product::withTrashed()->where('slug', $value);

        if ($this->exceptId !== null) {
            $query->where('id', '!=', $this->exceptId);
        }

        if ($query->exists()) {
            $fail('This slug is already in use by another product.');
        }
    }
}
