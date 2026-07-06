<?php

namespace App\Filament\Resources\Panel\AssetResource\Pages;

use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\AssetResource;

class ListAssets extends ListRecords
{
    protected static string $resource = AssetResource::class;

    // Read-only: hide the create action in header.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
