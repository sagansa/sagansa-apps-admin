<?php

namespace App\Filament\Resources\Panel\ProductOnlineGroupResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\ProductOnlineGroupResource;

class EditProductOnlineGroup extends EditRecord
{
    protected static string $resource = ProductOnlineGroupResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
