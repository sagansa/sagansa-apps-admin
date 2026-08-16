<?php

namespace App\Filament\Resources\Panel\DailySalaryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\DailySalaryResource;
use Illuminate\Support\Facades\Auth;

class CreateDailySalary extends CreateRecord
{
    protected static string $resource = DailySalaryResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {

        $data['status'] = 1;
        $data['created_by_id'] = Auth::id();

        return $data;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('index');
    }
}
