<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Asset;
use App\Models\Asset as AssetModel;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Split;
use Filament\Schemas\Schema;
use Filament\Actions\ActionGroup;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use App\Filament\Resources\Panel\AssetResource\Pages;

/**
 * Resource READ-ONLY untuk monitoring instance aset. Create/update tidak
 * disediakan di sini — aset dibuat via app mobile (product-driven) atau
 * otomatis dari invoice procurement. Admin hanya melihat & memfilter.
 */
class AssetResource extends Resource
{
    protected static ?string $model = AssetModel::class;

    protected static ?string $cluster = Asset::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $pluralLabel = 'Aset';

    public static function getModelLabel(): string
    {
        return 'Aset';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Aset';
    }

    public static function getNavigationLabel(): string
    {
        return 'Instance Aset';
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Informasi Aset')->schema([
                Grid::make(['default' => 2])->schema([
                    TextColumn::make('code')->label('Kode'),
                    TextColumn::make('name')->label('Nama'),

                    Split::make([
                        TextColumn::make('condition')
                            ->label('Kondisi')
                            ->formatStateUsing(fn ($state) => [
                                1 => 'Baik',
                                2 => 'Rusak Ringan',
                                3 => 'Rusak Berat',
                                4 => 'Hilang',
                            ][$state] ?? 'Tidak Diketahui'),
                        TextColumn::make('status')
                            ->label('Status')
                            ->formatStateUsing(fn ($state) => [
                                1 => 'Aktif',
                                2 => 'Dipelihara',
                                3 => 'Non-Aktif',
                            ][$state] ?? 'Tidak Diketahui'),
                    ]),
                ]),
            ]),

            Section::make('Asal & Penempatan')->schema([
                Grid::make(['default' => 2])->schema([
                    TextColumn::make('category.name')->label('Kategori'),
                    TextColumn::make('store.nickname')->label('Toko'),
                    TextColumn::make('product.name')->label('Produk Asal')
                        ->placeholder('— (manual) —'),
                    TextColumn::make('purchase_date')->label('Tgl Pembelian')
                        ->date('d M Y'),
                    TextColumn::make('createdBy.name')->label('Dibuat Oleh'),
                ]),
            ]),

            Section::make('Jadwal Pemeriksaan')->schema([
                Grid::make(['default' => 2])->schema([
                    TextColumn::make('last_check_at')->label('Check Terakhir')
                        ->dateTime('d M Y H:i')
                        ->placeholder('Belum pernah'),
                    TextColumn::make('next_check_at')->label('Next Check')
                        ->dateTime('d M Y H:i')
                        ->placeholder('—'),
                ]),
            ]),

            Section::make('Statistik')->schema([
                Grid::make(['default' => 3])->schema([
                    TextColumn::make('checks_count')
                        ->label('Total Check')
                        ->counts('checks'),
                    TextColumn::make('open_issues_count')
                        ->label('Issue Terbuka')
                        ->state(function ($record) {
                            return $record->issues()
                                ->where('status', 1)->count();
                        }),
                    TextColumn::make('issues_count')
                        ->label('Total Issue')
                        ->counts('issues'),
                ]),
            ]),

            Section::make('Catatan')->schema([
                TextColumn::make('notes')
                    ->placeholder('—'),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('code')
                    ->label('Kode')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('name')
                    ->label('Nama Aset')
                    ->searchable()
                    ->sortable()
                    ->limit(40),

                TextColumn::make('category.name')
                    ->label('Kategori')
                    ->sortable(),

                TextColumn::make('store.nickname')
                    ->label('Toko')
                    ->sortable(),

                TextColumn::make('condition')
                    ->label('Kondisi')
                    ->badge()
                    ->formatStateUsing(fn ($state) => [
                        1 => 'Baik',
                        2 => 'Rusak Ringan',
                        3 => 'Rusak Berat',
                        4 => 'Hilang',
                    ][$state] ?? '?')
                    ->color(fn ($state) => match ((int) $state) {
                        1 => 'success',
                        2 => 'warning',
                        3, 4 => 'danger',
                        default => 'gray',
                    }),

                TextColumn::make('next_check_at')
                    ->label('Next Check')
                    ->dateTime('d M Y')
                    ->sortable()
                    ->color(fn ($state) => $state && \Carbon\Carbon::parse($state)->isPast()
                        ? 'danger'
                        : null),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->formatStateUsing(fn ($state) => [
                        1 => 'Aktif',
                        2 => 'Dipelihara',
                        3 => 'Non-Aktif',
                    ][$state] ?? '?')
                    ->color(fn ($state) => match ((int) $state) {
                        1 => 'success',
                        2 => 'warning',
                        3 => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                SelectFilter::make('asset_category_id')
                    ->label('Kategori')
                    ->relationship('category', 'name')
                    ->preload(),

                SelectFilter::make('store_id')
                    ->label('Toko')
                    ->relationship('store', 'nickname')
                    ->preload(),

                SelectFilter::make('condition')
                    ->label('Kondisi')
                    ->options([
                        1 => 'Baik',
                        2 => 'Rusak Ringan',
                        3 => 'Rusak Berat',
                        4 => 'Hilang',
                    ]),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        1 => 'Aktif',
                        2 => 'Dipelihara',
                        3 => 'Non-Aktif',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                ]),
            ])
            ->bulkActions([])
            ->defaultSort('next_check_at', 'asc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAssets::route('/'),
            'view' => Pages\ViewAsset::route('/{record}'),
        ];
    }
}
