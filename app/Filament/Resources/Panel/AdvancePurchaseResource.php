<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Bulks\ValidBulkAction;
use App\Filament\Clusters\Purchases;
use App\Filament\Forms\CurrencyInput;
use App\Filament\Forms\CurrencyRepeaterInput;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\Notes;
use App\Filament\Forms\StoreSelect;
use App\Filament\Forms\SupplierSelect;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\AdvancePurchase;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\Panel\AdvancePurchaseResource\Pages;
use App\Filament\Tables\AdvancePurchaseTable;
use App\Models\CashAdvance;
use Filament\Forms\Components\Repeater;
use App\Models\Product;
use Filament\Actions\ActionGroup;
use Filament\Actions\DeleteBulkAction;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;

class AdvancePurchaseResource extends Resource
{
    protected static ?string $model = AdvancePurchase::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-s-shopping-cart';

    protected static ?int $navigationSort = 40;

    protected static ?string $cluster = Purchases::class;


    public static function getModelLabel(): string
    {
        return __('crud.advancePurchases.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.advancePurchases.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.advancePurchases.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Grid::make(['default' => 1, 'md' => 3])
                ->schema([
                    // Left Column (Grid span 2 for details and products)
                    Grid::make(['default' => 1])
                        ->schema([
                            Section::make('Informasi Pembelian')
                                ->schema([
                                    SupplierSelect::make('supplier_id'),
                                    StoreSelect::make('store_id'),
                                    DateInput::make('date'),
                                ])
                                ->columns(2),

                            Section::make('Detail Barang Belanja')
                                ->schema([
                                    self::getItemsRepeater(),
                                ]),
                        ])
                        ->columnSpan(['md' => 2]),

                    // Right Column (Grid span 1 for cash advance, status, summary, and receipt)
                    Grid::make(['default' => 1])
                        ->schema([
                            Section::make('Sumber Dana & Validasi')
                                ->schema([
                                    Select::make('cash_advance_id')
                                        ->required(fn() => Auth::user()->hasRole('staff'))
                                        ->label('Cash Advance')
                                        ->inlineLabel()
                                        ->relationship(
                                            name: 'cashAdvance',
                                            modifyQueryUsing: fn(Builder $query) =>
                                            Auth::user()->hasRole('staff')
                                            ? $query->where('user_id', Auth::id())->where('status', 1)
                                            : $query,
                                        )
                                        ->getOptionLabelFromRecordUsing(fn(CashAdvance $record) => "{$record->cash_advance_name}")
                                        ->default(function () {
                                            if (Auth::user()->hasRole('staff')) {
                                                $latest = CashAdvance::where('user_id', Auth::id())
                                                    ->where('status', 1)
                                                    ->latest('created_at')
                                                    ->first();
                                                return $latest?->id;
                                            }
                                            return null;
                                        })
                                        ->disabled(fn(?AdvancePurchase $record) => !Auth::user()->hasRole('admin') && $record?->status === 2),

                                    Select::make('status')
                                        ->required()
                                        ->inlineLabel()
                                        ->required(fn() => Auth::user()->hasRole('admin'))
                                        ->hidden(fn($operation) => $operation === 'create')
                                        ->disabled(fn() => Auth::user()->hasRole('staff'))
                                        ->preload()
                                        ->options([
                                            '1' => 'belum diperiksa',
                                            '2' => 'valid',
                                            '3' => 'diperbaiki',
                                            '4' => 'periksa ulang',
                                        ]),
                                ]),

                            Section::make('Ringkasan Biaya')
                                ->schema([
                                    CurrencyInput::make('subtotal_price')
                                        ->readOnly(),

                                    CurrencyInput::make('discount_price')
                                        ->debounce(2000)
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, Set $set, Get $get) => $set('total_price', $get('subtotal_price') - $state)),

                                    CurrencyInput::make('total_price')
                                        ->readOnly()
                                        ->reactive(),

                                    Notes::make('notes'),
                                ]),

                            Section::make('Bukti Nota / Faktur')
                                ->schema([
                                    ImageInput::make('image')
                                        ->directory('images/AdvancePurchase'),
                                ]),
                        ])
                        ->columnSpan(['md' => 1]),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $advancePurchases = AdvancePurchase::query();

        if (!Auth::user()->hasRole('admin')) {
            $advancePurchases->where('user_id', Auth::id());
        }

        return $table
            ->query($advancePurchases)
            ->poll('60s')
            ->columns(AdvancePurchaseTable::schema())
            ->filters([])
            ->actions([
                \Filament\Actions\Action::make('validate')
                    ->label('Validate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (AdvancePurchase $record) => $record->update(['status' => 2]))
                    ->visible(fn (AdvancePurchase $record) => Auth::user()->hasRole('admin') && $record->status !== 2),
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ValidBulkAction::make('setStatusToValid')
                        ->action(function (Collection $records) {
                            AdvancePurchase::whereIn('id', $records->pluck('id'))->update(['status' => 2]);
                        }),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAdvancePurchases::route('/'),
            'create' => Pages\CreateAdvancePurchase::route('/create'),
            'view' => Pages\ViewAdvancePurchase::route('/{record}'),
            'edit' => Pages\EditAdvancePurchase::route('/{record}/edit'),
        ];
    }



    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('detailAdvancePurchases')
            ->relationship()
            ->schema([
                Select::make('product_id')
                    // ->label('Product')
                    ->hiddenLabel()
                    ->placeholder('Product')
                    ->searchable()
                    ->options(Product::query()->pluck('name', 'id'))
                    ->required()
                    ->reactive()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan([
                        'md' => 4,
                    ])
                    ->searchable(),

                TextInput::make('quantity')
                    ->label('Quantity')
                    ->numeric()
                    ->hiddenlabel()
                    ->placeholder('quantity')
                    ->default(1)
                    ->minValue(1)
                    ->required()
                    ->suffix(function (Get $get) {
                        $product = Product::find($get('product_id'));
                        return $product ? $product->unit->unit : '';
                    })
                    ->debounce(2000)
                    ->columnSpan([
                        'md' => 2,
                    ])
                    ->reactive()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateUnitPrice($get, $set);
                    }),

                CurrencyRepeaterInput::make('price')
                    ->columnSpan([
                        'md' => 2,
                    ])
                    ->debounce(2000)
                    ->reactive()
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateUnitPrice($get, $set);
                        self::updateTotalPrice($get, $set);
                    }),

                CurrencyRepeaterInput::make('unit_price')
                    ->label('Unit Price')
                    ->readOnly()
                    ->columnSpan([
                        'md' => 2,
                    ]),
            ])
            ->columns([
                'md' => 10,
            ])
            ->afterStateUpdated(function (Get $get, Set $set) {
                self::updateTotalPrice($get, $set);
            });
    }



    protected static function updateUnitPrice(Get $get, Set $set): void
    {
        // Mengambil nilai dan mengonversi ke float, dengan default 0 untuk price dan 1 untuk quantity
        $price = $get('price') !== null ? (int) $get('price') : 1;
        $quantity = $get('quantity') !== null ? (int) $get('quantity') : 1;

        // Cek jika quantity 0 untuk menghindari pembagian dengan 0
        $unitPrice = $quantity > 0 ? $price / $quantity : 0;

        // $unitPrice = $price / $quantity;
        $set('unit_price', number_format($unitPrice, 0, ',', ''));
    }

    protected static function updateTotalPrice(Get $get, Set $set): void
    {
        // Get the repeater items or initialize to an empty array if null
        $repeaterItems = $get('detailAdvancePurchases') ?? [];

        $subtotalPrice = 0;

        foreach ($repeaterItems as $item) {
            if (isset($item['price'])) {
                $subtotalPrice += (int) $item['price'];
            }
        }

        $discountPrice = $get('discount_price') !== null ? (int) $get('discount_price') : 0;
        $totalPrice = $subtotalPrice - $discountPrice;

        $set('subtotal_price', $subtotalPrice);
        $set('total_price', $totalPrice);
    }
}

