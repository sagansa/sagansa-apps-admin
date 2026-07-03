<?php

namespace App\Filament\Resources\Panel\SalaryRateResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\SalaryRateResource;

class ListSalaryRates extends ListRecords
{
    protected static string $resource = SalaryRateResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
