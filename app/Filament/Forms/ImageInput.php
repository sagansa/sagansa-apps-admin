<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\FileUpload;

class ImageInput extends FileUpload
{
    protected function setUp(): void
    {
        parent::setUp();

        $this->rules(['image'])
            ->nullable()
            ->openable()
            ->downloadable()
            ->fetchFileInformation(false)
            ->disk('public');
    }
}
