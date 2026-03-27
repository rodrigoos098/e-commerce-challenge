<?php

namespace App\Rules;

use App\Models\Product;
use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class SufficientStock implements ValidationRule
{
    public function __construct(
        private readonly ?int $productId = null,
    ) {
    }

    /**
     * Run the validation rule.
     *
     * @param  \Closure(string, ?string=): \Illuminate\Translation\PotentiallyTranslatedString  $fail
     */
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if ($this->productId === null) {
            $fail('This cart item is no longer available.');

            return;
        }

        $product = Product::query()->find($this->productId);

        if (! $product) {
            $fail('This cart item is no longer available.');

            return;
        }

        if (! $product->active) {
            $fail('This cart item is no longer available.');

            return;
        }

        if ($product->quantity < (int) $value) {
            $fail("Insufficient stock. Only {$product->quantity} unit(s) available.");
        }
    }
}
