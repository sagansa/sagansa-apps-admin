<?php

namespace App\Filament\Forms;

use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;

class ImageInput extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->rules([
            fn (string $attribute, mixed $value, Closure $fail) => $this->validateImage($attribute, $value, $fail),
        ])
            ->nullable()
            ->openable()
            ->downloadable()
            ->fetchFileInformation(false)
            ->disk('public')
            ->saveUploadedFileUsing(function (UploadedFile $file): string {
                $processedPath = app(\App\Services\ImageProcessor::class)->process($file);

                $newFile = new UploadedFile(
                    $processedPath,
                    pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME) . '.webp',
                    'image/webp',
                    null,
                    true,
                );

                return $newFile->store($this->getDirectory(), $this->getDiskName());
            });

        $this->acceptedFileTypes(['image/*', 'image/heic', 'image/heif']);
    }

    public function image(): static
    {
        return $this;
    }

    public function acceptedFileTypes(array | \Illuminate\Contracts\Support\Arrayable | Closure $types): static
    {
        $this->acceptedFileTypes = $types;

        return $this;
    }

    private function validateImage(string $attribute, mixed $value, Closure $fail): void
    {
        if (! $value instanceof UploadedFile) {
            return;
        }

        $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'heic', 'heif'];

        if (in_array(strtolower($value->getClientOriginalExtension()), $allowed, true)) {
            return;
        }

        if (str_starts_with($value->getMimeType(), 'image/')) {
            return;
        }

        $fail('The :attribute must be a valid image file (JPEG, PNG, GIF, WebP, HEIC).');
    }
}
