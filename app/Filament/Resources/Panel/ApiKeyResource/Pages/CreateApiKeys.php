<?php

namespace App\Filament\Resources\Panel\ApiKeyResource\Pages;

use App\Filament\Resources\Panel\ApiKeyResource;
use App\Models\ApiKey;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;

class CreateApiKeys extends CreateRecord
{
    protected static string $resource = ApiKeyResource::class;

    protected ?string $plainKey = null;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $this->plainKey = ApiKey::generateUniquePlainKey();

        $data['key_hash'] = ApiKey::hashKey($this->plainKey);
        $data['key_prefix'] = ApiKey::prefixOf($this->plainKey);
        $data['is_active'] = true;
        $data['created_by'] = auth()->id();

        return $data;
    }

    protected function afterCreate(): void
    {
        // Plaintext hanya hidup di session flash sekali pakai — tidak pernah ke DB/log.
        session()->flash('api_key_plain', $this->plainKey);
        session()->flash('api_key_id', $this->getRecord()->id);
    }

    protected function getCreatedNotification(): ?Notification
    {
        return null; // kunci ditampilkan di halaman reveal, bukan notifikasi
    }

    protected function getRedirectUrl(): string
    {
        return ApiKeyResource::getUrl('reveal');
    }
}
