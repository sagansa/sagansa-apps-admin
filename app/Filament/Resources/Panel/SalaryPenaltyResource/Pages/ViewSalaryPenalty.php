<?php

namespace App\Filament\Resources\Panel\SalaryPenaltyResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\SalaryPenaltyResource;

class ViewSalaryPenalty extends ViewRecord
{
    protected static string $resource = SalaryPenaltyResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
