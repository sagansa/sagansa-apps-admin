<?php

namespace App\Filament\Resources\Panel\AssetResource\Pages;

use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\AssetResource;

class ViewAsset extends ViewRecord
{
    protected static string $resource = AssetResource::class;

    // Read-only: jangan tampilkan tombol edit.
    protected function getHeaderActions(): array
    {
        return [];
    }
}
