<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Stock;
use App\Filament\Columns\ActiveColumn;
use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\BaseTextInput;
use App\Filament\Forms\DecimalInput;
use App\Models\Recipe;
use App\Models\Product;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\RecipeResource\Pages;

/**
 * CRUD master resep produksi: definisi ingredient default untuk membuat
 * sebuah produk output. Dipakai oleh ProductionResource sebagai starting
 * point saat user pilih produk yang akan diproduksi.
 */
class RecipeResource extends Resource
{
    protected static ?string $model = Recipe::class;

    protected static ?string $cluster = Stock::class;

    protected static ?int $navigationSort = 5;

    protected static ?string $pluralLabel = 'Resep Produksi';

    public static function getModelLabel(): string
    {
        return 'Resep Produksi';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Resep Produksi';
    }

    public static function getNavigationLabel(): string
    {
        return 'Resep Produksi';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make(' Produk Hasil')->schema([
                Grid::make(['default' => 2])->schema([
                    BaseSelect::make('product_id')
                        ->label('Produk Output')
                        ->relationship(
                            name: 'product',
                            titleAttribute: 'name',
                            modifyQueryUsing: fn (Builder $query) => $query->orderBy('name', 'asc'),
                        )
                        ->helperText('Produk yang dihasilkan oleh resep ini.')
                        ->required()
                        ->searchable()
                        ->preload()
                        ->live()
                        // Saat user pilih produk output, auto-set output_unit_id
                        // dari unit produk (bisa di-override sesudahnya).
                        ->afterStateUpdated(function ($state, $set) {
                            $product = Product::find($state);
                            $set('output_unit_id', $product?->unit_id);
                        }),

                    DecimalInput::make('output_qty')
                        ->label('Jumlah Output')
                        ->helperText('Berapa produk dihasilkan sekali pakai resep ini.')
                        ->default(1)
                        ->required(),
                ]),
                Grid::make(['default' => 2])->schema([
                    BaseSelect::make('output_unit_id')
                        ->label('Satuan Output')
                        ->relationship('outputUnit', 'name')
                        ->nullable()
                        ->searchable()
                        ->preload(),

                    BaseTextInput::make('name')
                        ->label('Nama Resep (opsional)')
                        ->placeholder('cth: Resep standar Kopi Susu')
                        ->nullable(),
                ]),
            ]),

            Section::make('Ingredient (Bahan Baku)')
                ->description('Daftar bahan baku yang dibutuhkan per sekali jalan resep di atas.')
                ->schema([
                    Forms\Components\Repeater::make('ingredients')
                        ->relationship()
                        ->hiddenLabel()
                        ->columns(12)
                        ->addActionLabel('Tambah Ingredient')
                        ->reorderable()
                        ->schema([
                            Forms\Components\Select::make('product_id')
                                ->label('Produk Bahan')
                                ->relationship(
                                    name: 'product',
                                    titleAttribute: 'name',
                                    modifyQueryUsing: fn (Builder $query) => $query->orderBy('name', 'asc'),
                                )
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required()
                                ->searchable()
                                ->preload()
                                ->columnSpan(5)
                                // Auto-set unit dari produk yang dipilih.
                                ->live()
                                ->afterStateUpdated(function ($state, $set) {
                                    $set('unit_id', Product::find($state)?->unit_id);
                                }),

                            DecimalInput::make('quantity')
                                ->label('Qty')
                                ->columnSpan(2),

                            Forms\Components\Select::make('unit_id')
                                ->label('Satuan')
                                ->relationship('unit', 'name')
                                ->nullable()
                                ->searchable()
                                ->preload()
                                ->columnSpan(2),

                            Forms\Components\Toggle::make('is_optional')
                                ->label('Opsional')
                                ->default(false)
                                ->columnSpan(1),

                            Forms\Components\TextInput::make('notes')
                                ->label('Catatan')
                                ->nullable()
                                ->columnSpan(2),
                        ]),
                ]),

            Section::make('Status & Catatan')->schema([
                Grid::make(['default' => 2])->schema([
                    Forms\Components\Toggle::make('is_active')
                        ->label('Resep Aktif')
                        ->default(true)
                        ->helperText('Hanya 1 resep aktif per produk output. Aktifkan ini akan menonaktifkan resep lain untuk produk yang sama.'),

                    Forms\Components\Textarea::make('notes')
                        ->label('Catatan Resep')
                        ->rows(2)
                        ->nullable(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Produk Output')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('output_qty')
                    ->label('Qty Output')
                    ->formatStateUsing(fn ($state, $record) =>
                        rtrim(rtrim(number_format($state, 3, ',', '.'), '0'), ',') .
                        ' ' . ($record->outputUnit?->name ?? '')),

                TextColumn::make('ingredients_count')
                    ->label('Bahan')
                    ->counts('ingredients')
                    ->badge(),

                TextColumn::make('productions_count')
                    ->label('Dipakai Produksi')
                    ->counts('productions')
                    ->badge(),

                ActiveColumn::make('is_active'),

                TextColumn::make('updated_at')
                    ->label('Diperbarui')
                    ->since(),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->label('Produk Output')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),

                TernaryFilter::make('is_active')
                    ->label('Aktif')
                    ->placeholder('Semua')
                    ->trueLabel('Aktif')
                    ->falseLabel('Nonaktif'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRecipes::route('/'),
            'create' => Pages\CreateRecipe::route('/create'),
            'view' => Pages\ViewRecipe::route('/{record}'),
            'edit' => Pages\EditRecipe::route('/{record}/edit'),
        ];
    }
}
