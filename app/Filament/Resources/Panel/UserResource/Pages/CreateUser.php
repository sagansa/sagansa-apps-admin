<?php

namespace App\Filament\Resources\Panel\UserResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\UserResource;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        // Kolom "roles" (Select relationship multiple) sudah disinkronkan oleh
        // Filament sebelum hook ini. Boot hook User menambahkan role 'customer'
        // saat record dibuat, tapi bisa ter-detach jika tidak dipilih di form —
        // assignRole di sini memastikan 'customer' selalu ada tanpa menghapus
        // role lain yang dipilih.
        $this->record->assignRole('customer');
    }
}
