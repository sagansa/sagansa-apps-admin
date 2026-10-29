<?php

namespace App\Filament\Resources\Panel\SopCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\SopCategoryResource;

class EditSopCategory extends EditRecord
{
    protected static string $resource = SopCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
