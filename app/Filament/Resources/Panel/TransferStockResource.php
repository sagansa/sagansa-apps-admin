<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Bulks\ValidBulkAction;
use App\Filament\Clusters\Stock;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Columns\StatusColumn;
use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\NominalInput;
use App\Filament\Forms\Notes;
use App\Filament\Forms\StatusSelectInput;
use App\Filament\Forms\StoreSelect;
use Filament\Tables;
use Filament\Tables\Filters\SelectFilter;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\TransferStock;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\TransferStockResource\Pages;
use App\Models\Product;
use Filament\Forms\Components\Repeater;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class TransferStockResource extends Resource
{
    protected static ?string $model = TransferStock::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Stock::class;


    public static function getModelLabel(): string
    {
        return __('crud.transferStocks.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.transferStocks.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.transferStocks.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([

                    ImageInput::make('image')
                        ->directory('images/TransferStock'),

                ]),

                Grid::make(['default' => 2])->schema([

                    DateInput::make('date'),

                    StoreSelect::make('from_store_id')
                        ->required()
                        ->relationship(
                            name: 'storeFrom',
                            titleAttribute: 'nickname',
                            modifyQueryUsing: fn (Builder $query) => $query->where('status', '<>', 8)->orderBy('name', 'asc'),)
                        ->preload()
                        ->reactive()
                        ->rules(fn ($get) => ['different:to_store_id']),

                    StoreSelect::make('to_store_id')
                        ->required()
                        ->relationship(
                            name: 'storeTo',
                            titleAttribute: 'nickname',
                            modifyQueryUsing: fn (Builder $query) => $query->where('status', '<>', 8)->orderBy('name', 'asc'),)
                        ->preload()
                        ->reactive(),

                    StatusSelectInput::make('status'),

                    BaseSelect::make('sent_by_id')
                        ->label('Dikirim Oleh')
                        ->relationship('sentBy', 'name', fn (Builder $query) => $query
                            ->whereHas('roles', fn (Builder $q) => $q
                                ->where('name', 'staff')))
                        ->default(fn () => Auth::user()->hasRole('staff') ? Auth::id() : null)
                        ->required()
                        ->searchable(),

                    BaseSelect::make('received_by_id')
                        ->relationship('receivedBy', 'name', fn (Builder $query) => $query
                            ->whereHas('roles', fn (Builder $q) => $q
                                ->where('name', 'staff')))
                        ->searchable(),

                ]),
            ]),

            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Repeater::make('productTransferStocks')
                        ->relationship()
                        ->schema([
                            Select::make('product_id')
                                ->label('Product')
                                ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                ->required()
                                ->options(Product::where('remaining', '1')->get()->pluck('name','id'))
                                ->columnSpan([
                                    'md' => 5,
                                ]),
                            NominalInput::make('quantity')
                                ->suffix(function ($get) {
                                    $product = Product::find($get('product_id'));
                                    return $product ? $product->unit->unit : '';
                                })
                                ->columnSpan([
                                    'md' => 5,
                                ]),
                        ])
                ]),
            ]),

            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Notes::make('notes'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $transferStocks = TransferStock::query();

        if (Auth::user()->hasRole('staff') || Auth::user()->hasRole('supervisor')) {
            $transferStocks->where(function($query) {
                $query->where('received_by_id', Auth::id())
                    ->orWhere('sent_by_id', Auth::id());
            });
        }

        return $table
            ->query($transferStocks)
            ->poll('60s')
            ->columns([
                ImageOpenUrlColumn::make('image'),

                TextColumn::make('date'),

                TextColumn::make('storeFrom.nickname'),

                TextColumn::make('storeTo.nickname'),

                TextColumn::make('productTransferStocks', 'Product Transfer Stocks')
                    ->label('Transfer Stocks')
                    ->html()
                    ->formatStateUsing(function (TransferStock $record) {
                        return implode('<br>', $record->productTransferStocks->map(function ($productTransferStock) {
                            return "{$productTransferStock->product->name} = {$productTransferStock->quantity} {$productTransferStock->product->unit->unit}";
                        })->toArray());
                    })
                    ->extraAttributes(['class' => 'whitespace-pre-wrap']),

                StatusColumn::make('status'),

                TextColumn::make('receivedBy.name'),

                TextColumn::make('sentBy.name'),


            ])
            ->filters([
                SelectFilter::make('from_store_id')
                    ->label('Store Asal')
                    ->relationship('storeFrom', 'nickname')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('to_store_id')
                    ->label('Store Tujuan')
                    ->relationship('storeTo', 'nickname')
                    ->searchable()
                    ->preload(),
                SelectFilter::make('status')
                    ->options([
                        '1' => 'belum diperiksa',
                        '2' => 'valid',
                        '3' => 'diperbaiki',
                        '4' => 'periksa ulang',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    ValidBulkAction::make('setStatusToValid')
                        ->action(function (Collection $records) {
                            TransferStock::whereIn('id', $records->pluck('id'))->update(['status' => TransferStock::STATUS_VALID]);
                        }),
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
            'index' => Pages\ListTransferStocks::route('/'),
            'create' => Pages\CreateTransferStock::route('/create'),
            'view' => Pages\ViewTransferStock::route('/{record}'),
            'edit' => Pages\EditTransferStock::route('/{record}/edit'),
        ];
    }
}
