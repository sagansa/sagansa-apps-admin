<?php

namespace App\Filament\Pages;

use App\Models\PersonalAccessToken;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Filament\Tables\Table;
use UnitEnum;

/**
 * Penerbit token API dari panel — token Sanctum dibuat lewat koneksi
 * mysql_auth (DB sagansa_user, lihat App\Models\PersonalAccessToken) sehingga
 * langsung valid di aplikasi yang memakai DB auth terpusat yang sama
 * (superadmin/admin_api). Plaintext token hanya ditampilkan sekali saat dibuat.
 */
class ApiTokens extends Page implements HasTable
{
    use InteractsWithTable;

    // Tipe wajib persis sama dengan Page::$navigationGroup (invariance property PHP)
    protected static string|UnitEnum|null $navigationGroup = 'Akses';

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static ?string $navigationLabel = 'API Token';

    protected static ?string $title = 'API Token';

    protected static ?int $navigationSort = 99;

    public function getView(): string
    {
        return 'filament.pages.api-tokens';
    }

    /** Plaintext token baru — hanya terisi sekali tepat setelah dibuat. */
    public ?string $plainToken = null;

    public string $tokenName = 'esta-superadmin';

    public function createToken(): void
    {
        $this->validate([
            'tokenName' => ['required', 'string', 'max:255'],
        ]);

        $this->plainToken = auth()->user()
            ->createToken(trim($this->tokenName))
            ->plainTextToken;
    }

    public function hideToken(): void
    {
        $this->plainToken = null;
    }

    public function table(Table $table): Table
    {
        return $table
            // Hanya token milik user yang sedang login — user lain tak terlihat.
            ->query(fn () => auth()->user()->tokens())
            ->columns([
                TextColumn::make('name')
                    ->label('Nama'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
                TextColumn::make('last_used_at')
                    ->label('Terakhir dipakai')
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            // id desc = token terbaru dulu (deterministik walau created_at seri).
            ->defaultSort('id', 'desc')
            ->paginated([10, 25, 50, 100])
            ->defaultPaginationPageOption(25)
            ->emptyStateHeading('Belum ada token')
            ->emptyStateDescription('Buat token pertama dengan form di atas.')
            ->actions([
                Action::make('cabut')
                    ->label('Cabut')
                    ->color('danger')
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation()
                    ->modalDescription('Cabut token ini? Aplikasi yang memakainya langsung kehilangan akses.')
                    ->action(fn (PersonalAccessToken $record) => $record->delete()),
            ]);
    }
}
