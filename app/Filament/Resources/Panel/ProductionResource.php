<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Stock;
use App\Filament\Columns\StatusColumn;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\StoreSelect;
use App\Filament\Resources\Panel\ProductionResource\Pages;
use App\Filament\Resources\Panel\ProductionResource\RelationManagers;
use App\Models\Production;
use App\Models\Recipe;
use App\Services\ProductionLedgerService;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;

class ProductionResource extends Resource
{
    protected static ?string $model = Production::class;

    protected static ?int $navigationSort = 6;

    protected static ?string $cluster = Stock::class;

    protected static ?string $pluralLabel = 'Produksi';

    public static function getModelLabel(): string
    {
        return 'Produksi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Produksi';
    }

    public static function getNavigationLabel(): string
    {
        return 'Produksi';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Detail Produksi')->schema([
                Grid::make(['default' => 2])->schema([
                    StoreSelect::make('store_id'),
                    DateInput::make('date'),
                ]),
                Grid::make(['default' => 2])->schema([
                    Select::make('recipe_id')
                        ->label('Resep (opsional)')
                        ->relationship(
                            name: 'recipe',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn ($query) => $query
                                ->where('is_active', true)
                                ->with('product')
                                ->orderBy('id', 'desc'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Recipe $r) =>
                            "{$r->product?->name} (out: " .
                            rtrim(rtrim(number_format($r->output_qty, 3, ',', '.'), '0'), ',') .
                            ')')
                        ->nullable()
                        ->searchable()
                        ->preload()
                        ->helperText('Pilih resep aktif untuk auto-prefill ingredient (bisa di-override di tab Item Produksi).'),

                    Select::make('status')
                        ->label('Status')
                        ->options([
                            '1' => 'belum diperiksa',
                            '2' => 'valid',
                            '3' => 'perbaiki',
                            '4' => 'periksa ulang',
                        ])
                        ->default('1')
                        ->required(),
                ]),
            ]),

            Section::make('Catatan')->schema([
                Textarea::make('notes')
                    ->label('Catatan Produksi')
                    ->rows(3)
                    ->nullable(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('store.nickname')
                    ->label('Toko')
                    ->searchable(),

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('recipe.product.name')
                    ->label('Resep / Output')
                    ->placeholder('Manual')
                    ->searchable(),

                StatusColumn::make('status'),

                TextColumn::make('items_count')
                    ->label('Items')
                    ->counts('items')
                    ->badge(),

                TextColumn::make('applied_at')
                    ->label('Stok')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state ? 'Sudah dipakai' : 'Belum')
                    ->color(fn ($state) => $state ? 'success' : 'gray'),

                TextColumn::make('createdBy.name')
                    ->label('Dibuat oleh')
                    ->toggleable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options([
                        '1' => 'belum diperiksa',
                        '2' => 'valid',
                        '3' => 'perbaiki',
                        '4' => 'periksa ulang',
                    ]),
                TernaryFilter::make('applied')
                    ->label('Stok sudah dipakai')
                    ->placeholder('Semua')
                    ->trueLabel('Sudah dipakai')
                    ->falseLabel('Belum dipakai')
                    ->queries(
                        true: fn ($query) => $query->whereNotNull('applied_at'),
                        false: fn ($query) => $query->whereNull('applied_at'),
                        blank: fn ($query) => $query,
                    ),
            ])
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
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManager baru (terpadu, recipe-aware).
            RelationManagers\ProductionItemsRelationManager::class,
            // RelationManager LEGACY tetap dipertahankan agar data lama masih
            // bisa dilihat/dikelola sebelum migrasi penuh ke production_items.
            RelationManagers\ProductionMainFromsRelationManager::class,
            RelationManagers\ProductionSupportFromsRelationManager::class,
            RelationManagers\ProductionTosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductions::route('/'),
            'create' => Pages\CreateProduction::route('/create'),
            'view' => Pages\ViewProduction::route('/{record}'),
            'edit' => Pages\EditProduction::route('/{record}/edit'),
        ];
    }
}
