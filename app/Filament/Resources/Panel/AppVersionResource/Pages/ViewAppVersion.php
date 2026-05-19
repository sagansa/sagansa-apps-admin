<?php

namespace App\Filament\Resources\Panel\AppVersionResource\Pages;

use App\Filament\Resources\Panel\AppVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewAppVersion extends ViewRecord
{
    protected static string $resource = AppVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
