<?php

namespace App\Filament\Resources\Panel\EmployeeLoanResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\EmployeeLoanResource;

class EditEmployeeLoan extends EditRecord
{
    protected static string $resource = EmployeeLoanResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make(), Actions\DeleteAction::make()];
    }
}
