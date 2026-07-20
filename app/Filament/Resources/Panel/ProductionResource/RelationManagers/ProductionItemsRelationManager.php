<?php

namespace App\Filament\Resources\Panel\ProductionResource\RelationManagers;

use App\Models\Product;
use App\Models\Recipe;
use Filament\Forms;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Schema;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * Mengelola item produksi terpadu (bahan baku + output hasil) di halaman
 * View/Edit Production. Menggantikan 3 RelationManager lama (MainFroms /
 * SupportFroms / Tos) dengan 1 yang lebih konsisten.
 *
 * direction = in  → ingredient (bahan baku)
 * direction = out → output (produk hasil)
 */
class ProductionItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'items';

    protected static ?string $recordTitleAttribute = 'notes';

    public static function getLabel(): string
    {
        return 'Item Produksi';
    }

    public static function getPluralLabel(): string
    {
        return 'Item Produksi';
    }

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Grid::make(['default' => 2])->schema([
                Forms\Components\Select::make('direction')
                    ->label('Arah')
                    ->options([
                        'out' => 'Hasil Produksi (output)',
                        'in' => 'Bahan Baku (input)',
                    ])
                    ->required()
                    ->default('in')
                    ->live()
                    ->helperText('out = produk yang dihasilkan, in = bahan baku yang dikonsumsi.'),

                Forms\Components\Select::make('source')
                    ->label('Sumber')
                    ->options([
                        'recipe_default' => 'Dari Resep (default)',
                        'invoice' => 'Dari Purchase Invoice',
                        'manual' => 'Manual',
                    ])
                    ->default('manual')
                    ->required(),

                Forms\Components\Select::make('product_id')
                    ->label('Produk')
                    ->relationship(
                        name: 'product',
                        titleAttribute: 'name',
                        modifyQueryUsing: fn (Builder $query) => $query->orderBy('name', 'asc'),
                    )
                    ->required()
                    ->searchable()
                    ->preload()
                    ->live()
                    ->afterStateUpdated(function ($state, $set) {
                        $set('unit_id', Product::find($state)?->unit_id);
                    }),

                Forms\Components\TextInput::make('quantity')
                    ->label('Qty')
                    ->required()
                    ->numeric()
                    ->minValue(0)
                    ->step(0.001)
                    ->default(1),

                Forms\Components\Select::make('unit_id')
                    ->label('Satuan')
                    ->relationship('unit', 'name')
                    ->nullable()
                    ->searchable()
                    ->preload(),

                Forms\Components\Select::make('detail_invoice_id')
                    ->label('Detail Invoice (bila dari pembelian)')
                    ->relationship(
                        name: 'detailInvoice',
                        titleAttribute: 'id',
                        modifyQueryUsing: fn (Builder $query) => $query->orderBy('id', 'desc'),
                    )
                    ->nullable()
                    ->searchable()
                    ->preload()
                    ->visible(fn ($get) => $get('source') === 'invoice')
                    ->helperText('Hanya diisi bila sumber = invoice (bahan baku dari pembelian).'),

                Forms\Components\Textarea::make('notes')
                    ->label('Catatan')
                    ->rows(2)
                    ->nullable()
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->defaultSort('direction', 'asc')
            ->columns([
                TextColumn::make('direction')
                    ->label('Arah')
                    ->badge()
                    ->formatStateUsing(fn ($state) => $state === 'out' ? 'Hasil' : 'Bahan')
                    ->color(fn ($state) => $state === 'out' ? 'success' : 'warning'),

                TextColumn::make('product.name')
                    ->label('Produk')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quantity')
                    ->label('Qty')
                    ->formatStateUsing(function ($state, $record) {
                        $unit = $record->unit?->name ?? '';
                        return rtrim(rtrim(number_format($state, 3, ',', '.'), '0'), ',') . ' ' . $unit;
                    }),

                TextColumn::make('source')
                    ->label('Sumber')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match ($state) {
                        'recipe_default' => 'Resep',
                        'invoice' => 'Invoice',
                        default => 'Manual',
                    }),

                TextColumn::make('detailInvoice.id')
                    ->label('Inv #')
                    ->placeholder('-')
                    ->toggleable(),
            ])
            ->filters([
                \Filament\Tables\Filters\SelectFilter::make('direction')
                    ->options([
                        'in' => 'Bahan Baku',
                        'out' => 'Hasil Produksi',
                    ]),
            ])
            ->headerActions([
                \Filament\Tables\Actions\AttachAction::make()->hidden(),
                \Filament\Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Tables\Actions\EditAction::make(),
                    \Filament\Tables\Actions\DeleteAction::make(),
                ]),
            ])
            ->bulkActions([
                \Filament\Tables\Actions\BulkActionGroup::make([
                    \Filament\Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
