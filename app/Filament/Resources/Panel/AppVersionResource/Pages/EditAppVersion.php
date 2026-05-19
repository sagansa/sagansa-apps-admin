<?php

namespace App\Filament\Resources\Panel\AppVersionResource\Pages;

use App\Filament\Resources\Panel\AppVersionResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditAppVersion extends EditRecord
{
    protected static string $resource = AppVersionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\ViewAction::make(),
            Actions\DeleteAction::make(),
        ];
    }
}
