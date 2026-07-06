<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Asset;
use App\Filament\Columns\ActiveColumn;
use App\Filament\Forms\ActiveStatusSelect;
use App\Filament\Forms\BaseTextInput;
use App\Models\AssetCategory;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use App\Filament\Resources\Panel\AssetCategoryResource\Pages;

class AssetCategoryResource extends Resource
{
    protected static ?string $model = AssetCategory::class;

    protected static ?string $cluster = Asset::class;

    protected static ?int $navigationSort = 1;

    protected static ?string $pluralLabel = 'Kategori Aset';

    public static function getModelLabel(): string
    {
        return 'Kategori Aset';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Kategori Aset';
    }

    public static function getNavigationLabel(): string
    {
        return 'Kategori Aset';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Informasi Kategori')->schema([
                Grid::make(['default' => 1])->schema([
                    BaseTextInput::make('name')
                        ->label('Nama Kategori')
                        ->required(),

                    Textarea::make('description')
                        ->label('Deskripsi')
                        ->rows(2)
                        ->nullable(),
                ]),
            ]),

            Section::make('Pemeriksaan Berkala')->schema([
                Grid::make(['default' => 1])->schema([
                    TextInput::make('frequency_days')
                        ->label('Frekuensi Pemeriksaan (hari)')
                        ->numeric()
                        ->minValue(1)
                        ->maxValue(3650)
                        ->default(30)
                        ->required()
                        ->helperText('30=Bulanan, 90=Triwulan, 180=Semester, 365=Tahunan.'),

                    Toggle::make('is_active')
                        ->label('Aktif')
                        ->default(true),
                ]),
            ]),

            Section::make('Checklist Baku')
                ->description('Daftar item checklist yang harus diperiksa setiap kali pemeriksaan aset kategori ini dilakukan.')
                ->schema([
                    Repeater::make('checklist_definition')
                        ->label('Item Checklist')
                        ->schema([
                            TextInput::make('label')
                                ->label('Label Item')
                                ->required(),
                        ])
                        ->addActionLabel('Tambah Item')
                        ->reorderable()
                        ->default([])
                        ->columnSpanFull(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('name')
                    ->label('Kategori')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('frequency_days')
                    ->label('Frekuensi')
                    ->formatStateUsing(fn ($state) => match ((int) $state) {
                        1 => 'Harian',
                        7 => 'Mingguan',
                        30 => 'Bulanan',
                        90 => 'Triwulan',
                        180 => 'Semester',
                        365 => 'Tahunan',
                        default => "{$state} hari",
                    })
                    ->badge(),

                TextColumn::make('checklist_definition')
                    ->label('Jumlah Checklist')
                    ->formatStateUsing(function ($state) {
                        if (is_array($state)) return count($state) . ' item';
                        return '0 item';
                    }),

                TextColumn::make('products_count')
                    ->label('Digunakan Produk')
                    ->counts('products')
                    ->sortable(),

                TextColumn::make('assets_count')
                    ->label('Jumlah Aset')
                    ->counts('assets')
                    ->sortable(),

                ActiveColumn::make('is_active'),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssetCategories::route('/'),
            'create' => Pages\CreateAssetCategory::route('/create'),
            'view' => Pages\ViewAssetCategory::route('/{record}'),
            'edit' => Pages\EditAssetCategory::route('/{record}/edit'),
        ];
    }
}
