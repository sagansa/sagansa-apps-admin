<?php

namespace App\Filament\Resources\Panel;

use App\Models\AppVersion;
use Filament\Forms;
use Filament\Tables;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\FileUpload;
use Filament\Actions\ActionGroup;
use App\Filament\Resources\Panel\AppVersionResource\Pages;
use BackedEnum;
use UnitEnum;

class AppVersionResource extends Resource
{
    protected static ?string $model = AppVersion::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-arrow-up-on-square-stack';

    protected static string|UnitEnum|null $navigationGroup = 'System Settings';

    protected static ?int $navigationSort = 99;

    public static function getModelLabel(): string
    {
        return 'App Version';
    }

    public static function getPluralModelLabel(): string
    {
        return 'App Versions';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    Select::make('app_name')
                        ->required()
                        ->options([
                            'presence' => 'Presence App (Absensi)',
                            'point_of_sale' => 'Point of Sale (Kasir)',
                            'admin' => 'Admin Mobile',
                        ])
                        ->native(false),

                    TextInput::make('version_code')
                        ->required()
                        ->placeholder('e.g., 1.0.0')
                        ->string(),

                    TextInput::make('build_number')
                        ->required()
                        ->numeric()
                        ->placeholder('e.g., 1')
                        ->integer(),

                    FileUpload::make('apk_file')
                        ->label('APK File')
                        ->directory('apks')
                        ->acceptedFileTypes(['application/vnd.android.package-archive'])
                        ->nullable()
                        ->downloadable(),

                    Toggle::make('is_active')
                        ->label('Active')
                        ->default(true),

                    Toggle::make('is_force_update')
                        ->label('Force Update')
                        ->default(false),
                ]),

                Grid::make(['default' => 1])->schema([
                    Textarea::make('release_notes')
                        ->label('Release Notes')
                        ->nullable()
                        ->rows(4),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('app_name')
                    ->formatStateUsing(
                        fn(string $state): string => match ($state) {
                            'presence' => 'Presence (Absensi)',
                            'point_of_sale' => 'Point of Sale (Kasir)',
                            'admin' => 'Admin Mobile',
                            default => $state,
                        }
                    )
                    ->searchable()
                    ->sortable(),

                TextColumn::make('version_code')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('build_number')
                    ->sortable(),

                TextColumn::make('apk_file')
                    ->label('APK')
                    ->formatStateUsing(fn($state) => $state ? 'Available' : 'None')
                    ->color(fn($state) => $state ? 'success' : 'gray'),

                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),

                IconColumn::make('is_force_update')
                    ->label('Force Update')
                    ->boolean()
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                ])
            ])
            ->bulkActions([])
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAppVersions::route('/'),
            'create' => Pages\CreateAppVersion::route('/create'),
            'view' => Pages\ViewAppVersion::route('/{record}'),
            'edit' => Pages\EditAppVersion::route('/{record}/edit'),
        ];
    }
}
