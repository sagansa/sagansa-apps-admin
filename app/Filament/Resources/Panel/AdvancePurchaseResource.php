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
                                        ->disabled(fn(?AdvancePurchase $record) => !Auth::user()->hasAnyRole(['admin', 'super_admin']) && $record?->status === 2),

                                    Select::make('status')
                                        ->required()
                                        ->inlineLabel()
                                        ->required(fn() => Auth::user()->hasAnyRole(['admin', 'super_admin']))
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
                                        ->live(debounce: 500)
                                        ->afterStateUpdated(fn($state, Set $set, Get $get) => self::updateTotalPrice($get, $set)),

                                    CurrencyInput::make('total_price')
                                        ->readOnly(),

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

        if (!Auth::user()->hasAnyRole(['admin', 'super_admin'])) {
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
                    ->visible(fn (AdvancePurchase $record) => Auth::user()->hasAnyRole(['admin', 'super_admin']) && $record->status !== 2),
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
                    ->live(debounce: 500)
                    ->columnSpan([
                        'md' => 2,
                    ])
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateUnitPrice($get, $set);
                        self::updateTotalPriceFromItem($get, $set);
                    }),

                CurrencyRepeaterInput::make('price')
                    ->columnSpan([
                        'md' => 2,
                    ])
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateUnitPrice($get, $set);
                        self::updateTotalPriceFromItem($get, $set);
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
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set) {
                self::updateTotalPrice($get, $set);
            });
    }

    public static function parseCurrency(mixed $val): int
    {
        if ($val === null || $val === '') {
            return 0;
        }
        if (is_int($val)) {
            return $val;
        }
        if (is_float($val)) {
            return (int) round($val);
        }
        $digits = preg_replace('/[^\d]/', '', (string) $val);
        return $digits !== '' ? (int) $digits : 0;
    }

    protected static function updateUnitPrice(Get $get, Set $set): void
    {
        $price = static::parseCurrency($get('price'));
        $quantity = static::parseCurrency($get('quantity'));
        if ($quantity <= 0) {
            $quantity = 1;
        }

        $unitPrice = $quantity > 0 ? (int) round($price / $quantity) : 0;
        $set('unit_price', $unitPrice);
    }

    protected static function updateTotalPriceFromItem(Get $get, Set $set): void
    {
        $repeaterItems = $get('../../detailAdvancePurchases') ?? [];
        $discountPrice = static::parseCurrency($get('../../discount_price'));

        $subtotalPrice = 0;
        if (is_array($repeaterItems)) {
            foreach ($repeaterItems as $item) {
                $subtotalPrice += static::parseCurrency($item['price'] ?? 0);
            }
        }

        $currentPrice = static::parseCurrency($get('price'));
        if ($subtotalPrice < $currentPrice) {
            $subtotalPrice = $currentPrice;
        }

        $totalPrice = $subtotalPrice - $discountPrice;

        $set('../../subtotal_price', $subtotalPrice);
        $set('../../total_price', $totalPrice);
    }

    protected static function updateTotalPrice(Get $get, Set $set): void
    {
        $repeaterItems = $get('detailAdvancePurchases') ?? [];
        $discountPrice = static::parseCurrency($get('discount_price'));

        $subtotalPrice = 0;
        if (is_array($repeaterItems)) {
            foreach ($repeaterItems as $item) {
                $subtotalPrice += static::parseCurrency($item['price'] ?? 0);
            }
        }

        $totalPrice = $subtotalPrice - $discountPrice;

        $set('subtotal_price', $subtotalPrice);
        $set('total_price', $totalPrice);
    }
}

