<?php

namespace App\Filament\Resources\Panel\SopCategoryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\SopCategoryResource;

class ListSopCategories extends ListRecords
{
    protected static string $resource = SopCategoryResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
