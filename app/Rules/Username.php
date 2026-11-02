<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class Username implements ValidationRule
{
    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! is_string($value) || ! preg_match('/^[a-z0-9](?:[a-z0-9._-]{1,28})[a-z0-9]$/', $value)) {
            $fail('Username 3–30 karakter, hanya huruf kecil, angka, titik, garis bawah, atau tanda hubung, dan harus diawali serta diakhiri huruf atau angka.');
        }
    }
}
