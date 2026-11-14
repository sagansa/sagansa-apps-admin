<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Forms\BaseTextInput;
use App\Models\ApiKey;
use App\Filament\Resources\Panel\ApiKeyResource\Pages;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ApiKeyResource extends Resource
{
    protected static ?string $model = ApiKey::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-key';

    protected static string|\UnitEnum|null $navigationGroup = 'Sistem';

    protected static ?int $navigationSort = 90;

    public static function getModelLabel(): string
    {
        return 'API Key';
    }

    public static function getPluralModelLabel(): string
    {
        return 'API Keys';
    }

    public static function getNavigationLabel(): string
    {
        return 'API Key';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    BaseTextInput::make('name')
                        ->label('Nama')
                        ->maxLength(100),

                    TagsInput::make('scopes')
                        ->label('Scopes')
                        ->placeholder('mis. products:read')
                        ->suggestions(['products:read', 'sales:read', 'inventory:read', 'hr:read'])
                        ->helperText('Kosongkan jika key berlaku penuh. Bebas, dipisah Enter.'),

                    TagsInput::make('allowed_ips')
                        ->label('IP Diizinkan')
                        ->placeholder('mis. 103.10.20.30')
                        ->helperText('Kosongkan untuk mengizinkan semua IP.')
                        ->rules([
                            // Closure luar dievaluasi Filament; closure dalam
                            // adalah rule Laravel-standar ($attribute, $value, $fail).
                            fn (): \Closure => function (string $attribute, $value, \Closure $fail): void {
                                foreach ((array) $value as $ip) {
                                    if (filter_var($ip, FILTER_VALIDATE_IP) === false) {
                                        $fail("IP tidak valid: {$ip}");
                                    }
                                }
                            },
                        ]),

                    TextInput::make('rate_limit')
                        ->label('Rate Limit (permintaan/menit)')
                        ->numeric()
                        ->minValue(1)
                        ->default(60),

                    DateTimePicker::make('expires_at')
                        ->label('Kedaluwarsa')
                        ->helperText('Kosongkan agar tidak pernah kedaluwarsa.'),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true)
                        ->visible(fn (string $operation): bool => $operation === 'edit'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->emptyStateHeading('Belum ada API key')
            ->emptyStateDescription('Buat API key untuk dipakai project konsumen.')
            ->columns([
                TextColumn::make('name')
                    ->label('Nama')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('key_prefix')
                    ->label('Key')
                    ->fontFamily('mono')
                    ->copyable(false),
                TextColumn::make('scopes')
                    ->label('Scopes')
                    ->badge()
                    ->placeholder('—'),
                TextColumn::make('rate_limit')
                    ->label('Rate Limit'),
                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->state(fn (ApiKey $record): string => $record->status())
                    ->color(fn (string $state): string => match ($state) {
                        'Aktif' => 'success',
                        'Nonaktif' => 'warning',
                        'Kedaluwarsa' => 'danger',
                    }),
                TextColumn::make('last_used_at')
                    ->label('Terakhir Dipakai')
                    ->since()
                    ->placeholder('Belum pernah'),
                TextColumn::make('expires_at')
                    ->label('Kedaluwarsa Pada')
                    ->dateTime()
                    ->placeholder('Tidak pernah')
                    ->sortable(),
                TextColumn::make('creator.name')
                    ->label('Dibuat Oleh')
                    ->placeholder('—'),
                TextColumn::make('created_at')
                    ->label('Dibuat')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        'aktif' => 'Aktif',
                        'nonaktif' => 'Nonaktif',
                        'kedaluwarsa' => 'Kedaluwarsa',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        return match ($value) {
                            'aktif' => $query->where('is_active', true)
                                ->where(fn (Builder $q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now())),
                            'nonaktif' => $query->where('is_active', false),
                            'kedaluwarsa' => $query->where('expires_at', '<=', now()),
                            default => $query,
                        };
                    }),
            ])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\EditAction::make(),
                    // rotate & revoke ditambahkan di Task 6
                    \Filament\Actions\DeleteAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManager audit log ditambahkan di Task 7
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApiKeys::route('/'),
            'create' => Pages\CreateApiKeys::route('/create'),
            'reveal' => Pages\RevealApiKey::route('/reveal'),
            'view' => Pages\ViewApiKey::route('/{record}'),
            'edit' => Pages\EditApiKey::route('/{record}/edit'),
        ];
    }
}
