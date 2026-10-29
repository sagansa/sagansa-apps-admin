<?php

namespace App\Filament\Resources\Panel\SopResource\Pages;

use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\SopResource;

class CreateSop extends CreateRecord
{
    protected static string $resource = SopResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['revision'] = 1;

        if (!empty($data['is_published'])) {
            $data['published_at'] = $data['published_at'] ?? now();
        }

        return $data;
    }
}
