<?php

namespace App\Filament\Resources\Panel\SalaryPenaltyResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\SalaryPenaltyResource;

class ListSalaryPenalties extends ListRecords
{
    protected static string $resource = SalaryPenaltyResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
