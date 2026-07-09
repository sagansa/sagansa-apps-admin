<?php

namespace App\Rules;

use Closure;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Http\UploadedFile;

class ImageFile implements ValidationRule
{
    private array $allowedExtensions = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif'];

    public function validate(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        if (in_array(strtolower($value->getClientOriginalExtension()), $this->allowedExtensions, true)) {
            return;
        }

        if (str_starts_with($value->getMimeType(), 'image/')) {
            return;
        }

        $fail('The :attribute must be a valid image file (JPEG, PNG, GIF, WebP, HEIC).');
    }
}
