<?php

namespace App\Filament\Resources\Panel\ApiKeyResource\Pages;

use App\Filament\Resources\Panel\ApiKeyResource;
use App\Models\ApiKey;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\Page;

/**
 * Halaman tampil-sekali: menampilkan plaintext API key tepat satu kali
 * setelah create/rotasi (via session flash). Tanpa flash → tolak.
 * Hanya super_admin (mereka satu-satunya yang bisa membuat key).
 */
class RevealApiKey extends Page
{
    protected static string $resource = ApiKeyResource::class;

    protected string $view = 'filament.resources.api-key-resource.pages.reveal-api-key';

    public ?string $plainKey = null;

    public ?string $keyName = null;

    public function mount(): void
    {
        abort_unless(auth()->user()?->hasRole('super_admin'), 403);

        $this->plainKey = session()->pull('api_key_plain');
        $keyId = session()->pull('api_key_id');

        if (blank($this->plainKey) || blank($keyId)) {
            Notification::make()
                ->title('Tidak ada API key untuk ditampilkan')
                ->danger()
                ->send();

            $this->redirect(ApiKeyResource::getUrl('index'), navigate: false);

            return;
        }

        $this->keyName = ApiKey::query()->whereKey($keyId)->value('name');
    }

    public function getHeading(): string
    {
        return 'API Key Dibuat';
    }
}
