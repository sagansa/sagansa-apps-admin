<?php

namespace App\Filament\Resources\Panel\AppVersionResource\Pages;

use App\Filament\Resources\Panel\AppVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListAppVersions extends ListRecords
{
    protected static string $resource = AppVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
