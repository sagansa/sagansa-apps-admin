<?php

namespace App\Filament\Resources\Panel\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected ?array $pendingRoles = null;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingRoles = $data['roles'] ?? [];
        unset($data['roles']);

        return $data;
    }

    protected function afterSave(): void
    {
        $this->record->syncRoles($this->pendingRoles ?? []);
    }

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
