<?php

namespace App\Filament\Resources\Panel\EmployeeLoanResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\EmployeeLoanResource;

class ViewEmployeeLoan extends ViewRecord
{
    protected static string $resource = EmployeeLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\EditAction::make()];
    }
}
