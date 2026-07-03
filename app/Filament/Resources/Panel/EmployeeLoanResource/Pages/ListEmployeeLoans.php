<?php

namespace App\Filament\Resources\Panel\EmployeeLoanResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\EmployeeLoanResource;

class ListEmployeeLoans extends ListRecords
{
    protected static string $resource = EmployeeLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }
}
