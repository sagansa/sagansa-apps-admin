<?php

namespace App\Filament\Resources\Panel\SalaryPenaltyResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\SalaryPenaltyResource;

class EditSalaryPenalty extends EditRecord
{
    protected static string $resource = SalaryPenaltyResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\ViewAction::make(), Actions\DeleteAction::make()];
    }
}
