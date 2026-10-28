<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Resources\Panel\SignerResource\Pages;
use App\Models\Signer;
use App\Models\User;
use Closure;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Checkbox;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Storage;
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

                    FileUpload::make('signatureUpload')
                        ->label('Tanda Tangan')
                        ->disk('local')
                        ->acceptedFileTypes(['image/png'])
                        ->maxSize(500)
                        ->live()
                        ->helperText(function (?Signer $record): string {
                            if ($record !== null && filled($record->signature_asset)) {
                                $kb = number_format(strlen((string) $record->signature_asset) / 1024, 1);

                                return "Tanda tangan tersimpan ({$kb} KB base64) — unggah ulang untuk mengganti.";
                            }

                            return 'PNG maksimal 500 KB, disarankan latar transparan.';
                        })
                        ->validationMessages([
                            'mimetypes' => 'Berkas harus berformat PNG.',
                            'max' => 'Ukuran berkas maksimal 500 KB.',
                        ]),

                    Checkbox::make('removeSignature')
                        ->label('Hapus tanda tangan tersimpan')
                        ->visible(fn (string $operation): bool => $operation === 'edit')
                        ->helperText('Centang lalu simpan untuk mengosongkan aset tanda tangan (diabaikan bila mengunggah berkas baru).'),

                    Placeholder::make('signaturePreview')
                        ->label('Pratinjau tanda tangan tersimpan')
                        ->visible(fn (?Signer $record): bool => $record?->signatureDataUri() !== null)
                        ->content(function (?Signer $record): HtmlString {
                            $uri = $record?->signatureDataUri();

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
                // Kolom aset (LONGTEXT) sengaja TIDAK ikut dimuat di list —
                // hanya flag hasil selectRaw (PRD §3 #3).
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
                ->selectRaw('(signature_asset IS NOT NULL) as has_signature'))
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
                    \Filament\Actions\EditAction::make(),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
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
     * Mutasi data form sebelum create/save: berkas unggahan sementara
     * (signatureUpload, bukan kolom DB) dibaca isinya lalu disimpan sebagai
     * base64 di signature_asset; berkas temp dihapus. Checkbox removeSignature
     * (hanya edit) mengosongkan signature_asset.
     *
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    public static function mutateSignatureData(array $data): array
    {
        $upload = $data['signatureUpload'] ?? null;

        if (filled($upload)) {
            $path = is_array($upload) ? ($upload[0] ?? null) : $upload;

            if (filled($path) && Storage::disk('local')->exists($path)) {
                $data['signature_asset'] = base64_encode(Storage::disk('local')->get($path));
                Storage::disk('local')->delete($path);
            }
        } elseif (! empty($data['removeSignature'])) {
            $data['signature_asset'] = null;
        }

        unset($data['signatureUpload'], $data['removeSignature']);

        return $data;
    }
}
