<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\ImageInput;
use App\Filament\Resources\Panel\SignerResource\Pages;
use App\Models\Signer;
use App\Models\User;
use Closure;
use Filament\Actions\ActionGroup;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\HtmlString;

class SignerResource extends Resource
{
    protected static ?string $model = Signer::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-pencil-square';

    protected static string|\UnitEnum|null $navigationGroup = 'Konten';

    protected static ?int $navigationSort = 30;

    /**
     * Cache per-request map id => nama user (lookup lintas koneksi,
     * menggantikan relasi Eloquent yang tidak mungkin lintas koneksi).
     *
     * @var array<int, string>|null
     */
    protected static ?array $userNameCache = null;

    public static function getModelLabel(): string
    {
        return 'Penandatangan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Penandatangan Surat';
    }

    public static function getNavigationLabel(): string
    {
        return 'Penandatangan Surat';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Select::make('user_id')
                        ->label('Pengguna')
                        ->options(fn (): array => User::query()
                            ->orderBy('name')
                            ->get(['id', 'name', 'email'])
                            ->mapWithKeys(fn (User $user): array => [$user->id => "{$user->name} ({$user->email})"])
                            ->all())
                        ->searchable()
                        ->preload()
                        ->required()
                        ->live()
                        ->helperText('Pengguna dari database sagansa_user — cari nama atau email; manajemen user tetap di resource Pengguna.')
                        ->afterStateUpdated(function (Set $set, ?string $state): void {
                            if (blank($state)) {
                                return;
                            }

                            if ($user = User::query()->find($state)) {
                                $set('display_name', $user->name);
                            }
                        })
                        ->rules([
                            fn (Get $get, ?Signer $record) => function (string $attribute, mixed $value, Closure $fail) use ($get, $record): void {
                                $role = $get('role');

                                if (blank($value) || blank($role)) {
                                    return;
                                }

                                $exists = Signer::query()
                                    ->where('user_id', $value)
                                    ->where('role', $role)
                                    ->when($record instanceof Signer, fn (Builder $query) => $query->where('id', '!=', $record->id))
                                    ->exists();

                                if ($exists) {
                                    $fail('Kombinasi pengguna dan peran ini sudah terdaftar.');
                                }
                            },
                        ]),

                    Select::make('role')
                        ->label('Peran')
                        ->options(static::roleLabels())
                        ->required(),

                    TextInput::make('display_name')
                        ->label('Nama Tampil')
                        ->required()
                        ->maxLength(255)
                        ->helperText('Terisi otomatis dari nama pengguna — boleh disunting.'),

                    TextInput::make('title')
                        ->label('Jabatan')
                        ->maxLength(255),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->required(),

                    // Unggahan langsung ke img service (img.sagansa.id) —
                    // pola ImageInput; state = path relatif yang dipindahkan
                    // ke signers.signature_asset_path saat simpan.
                    ImageInput::make('signatureUpload')
                        ->label('Tanda Tangan')
                        ->directory('images/Signer')
                        ->acceptedFileTypes(['image/png'])
                        ->maxSize(1024)
                        ->live()
                        ->helperText(function (?Signer $record): string {
                            if ($record !== null && $record->signatureUrl() !== null) {
                                return 'Tanda tangan tersimpan di img.sagansa.id — unggah ulang PNG untuk mengganti.';
                            }

                            return 'PNG maksimal 1 MB, disarankan latar transparan. Disimpan di img.sagansa.id.';
                        })
                        ->validationMessages([
                            'mimetypes' => 'Berkas harus berformat PNG.',
                            'max' => 'Ukuran berkas maksimal 1 MB.',
                        ]),

                    Checkbox::make('removeSignature')
                        ->label('Hapus tanda tangan tersimpan')
                        ->visible(fn (string $operation): bool => $operation === 'edit')
                        ->helperText('Centang lalu simpan untuk mengosongkan aset tanda tangan (diabaikan bila mengunggah berkas baru).'),

                    Placeholder::make('signaturePreview')
                        ->label('Pratinjau tanda tangan tersimpan')
                        ->visible(fn (?Signer $record): bool => $record?->signatureUrl() !== null)
                        ->content(function (?Signer $record): HtmlString {
                            $uri = $record?->signatureUrl();

                            if ($uri === null) {
                                return new HtmlString('');
                            }

                            return new HtmlString(
                                '<div style="display:flex;align-items:center;justify-content:center;'
                                .'min-height:96px;padding:12px;border:1px dashed rgba(120,120,120,.45);'
                                .'border-radius:10px;background:rgba(120,120,120,.06);">'
                                .'<img src="'.e($uri).'" alt="Tanda tangan tersimpan" '
                                .'style="max-width:240px;max-height:120px;object-fit:contain;" />'
                                .'</div>'
                            );
                        }),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->modifyQueryUsing(fn (Builder $query): Builder => $query
                // Kolom aset sengaja TIDAK ikut dimuat di list — hanya flag
                // hasil selectRaw (path img service ATAU base64 legacy).
                ->select([
                    'signers.id',
                    'signers.user_id',
                    'signers.role',
                    'signers.display_name',
                    'signers.title',
                    'signers.is_active',
                    'signers.created_at',
                    'signers.updated_at',
                ])
                ->selectRaw('(signature_asset IS NOT NULL OR signature_asset_path IS NOT NULL) as has_signature'))
            ->columns([
                TextColumn::make('user_id')
                    ->label('Pengguna')
                    ->formatStateUsing(fn (mixed $state): string => static::userName($state) ?? "User #{$state}"),

                TextColumn::make('display_name')
                    ->label('Nama Tampil')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('role')
                    ->label('Peran')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => static::roleLabels()[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        'direktur' => 'success',
                        'komisaris' => 'info',
                        'pemegang_saham' => 'warning',
                        default => 'gray',
                    }),

                TextColumn::make('title')
                    ->label('Jabatan')
                    ->placeholder('—'),

                IconColumn::make('is_active')
                    ->label('Aktif')
                    ->boolean(),

                IconColumn::make('has_signature')
                    ->label('TTD')
                    ->boolean()
                    ->trueIcon('heroicon-o-pencil-square')
                    ->trueColor('success')
                    ->falseIcon('heroicon-o-minus-circle')
                    ->falseColor('gray')
                    ->tooltip('Ketersediaan aset tanda tangan'),

                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('updated_at')
                    ->label('Diubah')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('role')
                    ->label('Peran')
                    ->options(static::roleLabels()),

                TernaryFilter::make('is_active')
                    ->label('Aktif'),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                ]),
            ])
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSigners::route('/'),
            'create' => Pages\CreateSigner::route('/create'),
            'edit' => Pages\EditSigner::route('/{record}/edit'),
        ];
    }

    /**
     * Label Indonesia untuk enum role signers.
     *
     * @return array<string, string>
     */
    public static function roleLabels(): array
    {
        return [
            'direktur' => 'Direktur',
            'komisaris' => 'Komisaris',
            'pemegang_saham' => 'Pemegang Saham',
        ];
    }

    /**
     * Nama user berdasarkan id (lookup manual lintas koneksi sagansa_user).
     */
    public static function userName(int|string|null $id): ?string
    {
        if (static::$userNameCache === null) {
            static::$userNameCache = User::query()->pluck('name', 'id')->all();
        }

        if ($id === null || $id === '') {
            return null;
        }

        return static::$userNameCache[(int) $id] ?? null;
    }

    /**
     * Mutasi data form sebelum create/save.
     *
     * State `signatureUpload` (bukan kolom DB) setelah unggahan sudah
     * berupa path relatif di img service (mis. `images/Signer/<uuid>.webp`)
     * — dipindahkan ke `signature_asset_path`, dan `signature_asset`
     * (base64 legacy) dikosongkan. Tanpa unggahan & tanpa centang hapus,
     * kedua kolom tidak disentuh (mengubah nama/jabatan tidak menghapus
     * aset). Checkbox `removeSignature` mengalah hanya bila tidak ada
     * unggahan baru.
     *
     * `signature_asset` lama sengaja TIDAK ikut di-fill ke form (lihat
     * EditSigner::mutateFormDataBeforeFill) agar payload Livewire tetap
     * kecil.
     *
     * @param  array<string, mixed>  $data
     * @param  string|null  $previousPath  Path img service milik record sebelumnya
     *                                     (edit) — berkas lama dihapus saat
     *                                     diganti/dihapus.
     * @return array<string, mixed>
     */
    public static function mutateSignatureData(array $data, ?string $previousPath = null): array
    {
        $upload = $data['signatureUpload'] ?? null;
        $path = null;

        if (filled($upload)) {
            $candidate = is_array($upload) ? ($upload[0] ?? null) : $upload;

            if (filled($candidate)) {
                $path = trim((string) $candidate);
            }
        }

        if ($path !== null) {
            $data['signature_asset_path'] = $path;
            $data['signature_asset'] = null;

            if (filled($previousPath) && $previousPath !== $path) {
                static::deleteImageFile($previousPath);
            }
        } elseif (! empty($data['removeSignature'])) {
            $data['signature_asset_path'] = null;
            $data['signature_asset'] = null;

            if (filled($previousPath)) {
                static::deleteImageFile($previousPath);
            }
        }

        unset($data['signatureUpload'], $data['removeSignature']);

        return $data;
    }

    /**
     * Hapus berkas lama di img service — best-effort: kegagalan hanya
     * dicatat, simpan tidak digagalkan (aset baru sudah tersimpan).
     */
    public static function deleteImageFile(string $path): void
    {
        $token = config('services.image.api_token');
        $serviceUrl = config('services.image.service_url', 'https://img.sagansa.id');

        if (blank($token)) {
            return;
        }

        try {
            $response = Http::withToken($token)
                ->timeout(15)
                ->acceptJson()
                ->delete(rtrim($serviceUrl, '/').'/api/images', ['path' => $path]);

            if (! $response->successful()) {
                Log::warning('Gagal menghapus aset tanda tangan lama di img service.', [
                    'path' => $path,
                    'status' => $response->status(),
                ]);
            }
        } catch (\Throwable $exception) {
            Log::warning('Gagal menghapus aset tanda tangan lama di img service.', [
                'path' => $path,
                'error' => $exception->getMessage(),
            ]);
        }
    }
}
