<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

/**
 * Penerbit token API dari panel — token Sanctum dibuat lewat koneksi
 * mysql_auth (DB sagansa_user, lihat App\Models\PersonalAccessToken) sehingga
 * langsung valid di aplikasi yang memakai DB auth terpusat yang sama
 * (superadmin/admin_api). Plaintext token hanya ditampilkan sekali saat dibuat.
 */
class ApiTokens extends Page
{
    protected static ?string $navigationGroup = 'Akses';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'API Token';

    protected static ?string $title = 'API Token';

    protected static ?int $navigationSort = 99;

    public function getView(): string
    {
        return 'filament.pages.api-tokens';
    }

    /** Daftar token user yang sedang login (tanpa nilai plaintext). */
    public array $tokens = [];

    /** Plaintext token baru — hanya terisi sekali tepat setelah dibuat. */
    public ?string $plainToken = null;

    public string $tokenName = 'esta-superadmin';

    public function mount(): void
    {
        $this->loadTokens();
    }

    public function loadTokens(): void
    {
        $this->tokens = auth()->user()->tokens()
            ->orderByDesc('created_at')
            ->get()
            ->map(fn ($token) => [
                'id' => $token->id,
                'name' => $token->name,
                'created_at' => $token->created_at?->format('d M Y H:i'),
                'last_used_at' => $token->last_used_at?->format('d M Y H:i'),
            ])
            ->all();
    }

    public function createToken(): void
    {
        $this->validate([
            'tokenName' => ['required', 'string', 'max:255'],
        ]);

        $this->plainToken = auth()->user()
            ->createToken(trim($this->tokenName))
            ->plainTextToken;

        $this->loadTokens();
    }

    public function hideToken(): void
    {
        $this->plainToken = null;
    }

    public function revokeToken(int $id): void
    {
        // Hanya token milik user yang sedang login yang boleh dicabut.
        $token = auth()->user()->tokens()->whereKey($id)->first();
        if ($token) {
            $token->delete();
        }
        if ($this->plainToken !== null) {
            $this->hideToken();
        }
        $this->loadTokens();
    }
}
