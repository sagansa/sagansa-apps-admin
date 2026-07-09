<?php

namespace App\Filament\Forms;

use App\Rules\ImageFile;
use Closure;
use Filament\Forms\Components\FileUpload;
use Illuminate\Http\UploadedFile;

class ImageInput extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->rules([new ImageFile])
            ->nullable()
            ->openable()
            ->downloadable()
            ->fetchFileInformation(false)
            ->disk('public')
            ->imagePreviewHeight('120px')
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
}
