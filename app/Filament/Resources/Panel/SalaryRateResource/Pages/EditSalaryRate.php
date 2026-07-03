<?php

namespace App\Filament\Resources\Panel\SalaryRateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\SalaryRateResource;

class EditSalaryRate extends EditRecord
{
    protected static string $resource = SalaryRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
