<?php

namespace App\Filament\Resources\Panel\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected ?array $pendingRoles = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->pendingRoles = $data['roles'] ?? [];
        unset($data['roles']);

        return $data;
    }

    protected function afterCreate(): void
    {
        // Keep the 'customer' role assigned by the User model boot hook
        // (static::created) even though syncRoles replaces all roles.
        $roles = array_values(array_unique([...$this->pendingRoles ?? [], 'customer']));
        $this->record->syncRoles($roles);
    }
}
