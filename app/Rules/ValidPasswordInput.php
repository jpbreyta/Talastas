<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class ValidPasswordInput implements ValidationRule
{
    public function __construct(private bool $forHashing = false) {}

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_string($value)) {
            return;
        }
        if (str_contains($value, "\0")) {
            $fail('The :attribute must not contain null bytes.');
        }
        if ($this->forHashing && config('hashing.driver', 'bcrypt') === 'bcrypt' && strlen($value) > 72) {
            $fail('The :attribute must not exceed 72 bytes when using bcrypt.');
        }
    }
}
