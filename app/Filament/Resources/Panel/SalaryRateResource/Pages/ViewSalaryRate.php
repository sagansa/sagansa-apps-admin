<?php

namespace App\Filament\Resources\Panel\SalaryRateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\SalaryRateResource;

class ViewSalaryRate extends ViewRecord
{
    protected static string $resource = SalaryRateResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
        ];
    }
}
