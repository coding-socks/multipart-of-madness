<?php

namespace CodingSocks\MultipartOfMadness\Http\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;

class S3MetadataRule implements ValidationRule
{

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (!is_array($value)) {
            $fail('The :attribute cannot be accepted as metadata.');
            return;
        }
        foreach ($value as $key => $val) {
            if (!is_string($key) || !is_string($val)) {
                $fail('The :attribute cannot be accepted as metadata.');
                return;
            }
        }
    }
}
