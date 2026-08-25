<?php

namespace App\Filament\Resources\Panel\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use App\Filament\Resources\Panel\UserResource;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    // Kolom "roles" adalah Select relationship multiple: Filament menyinkronkan
    // pivot-nya sendiri saat getState(). Jangan syncRoles manual dari $data
    // karena nilai kolom tersebut tidak pernah ter-dehydrate ke $data.

    protected function getHeaderActions(): array
    {
        return [Actions\DeleteAction::make()];
    }
}
