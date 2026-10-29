<?php

namespace App\Filament\Resources\Panel\SopResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\SopResource;

class EditSop extends EditRecord
{
    protected static string $resource = SopResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        // Setiap penyimpanan menaikkan revisi — penanda versi bagi staf.
        $data['revision'] = (int) $this->getRecord()->revision + 1;

        if (!empty($data['is_published']) && !$this->getRecord()->published_at) {
            $data['published_at'] = now();
        }

        return $data;
    }
}
