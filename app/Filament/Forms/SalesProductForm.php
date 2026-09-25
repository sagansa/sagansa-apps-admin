<?php

namespace App\Filament\Forms;

use App\Models\Product;
use App\Models\SalesOrderDirect;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Facades\Auth;

class SalesProductForm
{
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

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('detailSalesOrders')
            ->label('')
            ->minItems(1)
            ->relationship()
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set) {
                self::updateTotalPriceFromRoot($get, $set);
            })
            ->schema([

                Select::make('product_id')
                    ->placeholder('Product')
                    ->hiddenLabel()
                    ->searchable()
                    ->options(Product::query()
                        ->whereNotIn('online_category_id', [4])
                        ->pluck('name', 'id')
                        ->map(function ($name, $id) {
                            $product = Product::find($id);
                            return $product->name;
                    }))
                    ->required()
                    ->live()
                    ->afterStateUpdated(function ($state, Get $get, Set $set) {
                        if ($state) {
                            $product = Product::find($state);
                            if ($product && filled($product->online_price)) {
                                $set('unit_price', (int) $product->online_price);
                            }
                        }
                        self::updateSubtotalAndTotal($get, $set);
                    })
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan([
                        'md' => 4,
                    ])
                    ->searchable(),

                TextInput::make('quantity')
                    ->hiddenLabel()
                    ->placeholder('Quantity')
                    ->numeric()
                    ->default(1)
                    ->minValue(1)
                    ->required()
                    ->live(debounce: 500)
                    ->columnSpan([
                        'md' => 2,
                    ])
                    ->suffix(function (Get $get) {
                        $product = Product::find($get('product_id'));
                        return $product ? $product->unit->unit : '';
                    })
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateSubtotalAndTotal($get, $set);
                    }),

                CurrencyRepeaterInput::make('unit_price')
                    ->placeholder('Unit Price')
                    ->live(debounce: 500)
                    ->columnSpan([
                        'md' => 2,
                    ])
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateSubtotalAndTotal($get, $set);
                    }),

                CurrencyRepeaterInput::make('subtotal_price')
                    ->placeholder('Subtotal Price')
                    ->readOnly()
                    ->columnSpan([
                        'md' => 2,
                    ])

                ])
                ->columns([
                'md' => 10,
            ]);
    }

    public static function updateSubtotalAndTotal(Get $get, Set $set): void
    {
        // 1. Hitung subtotal untuk item saat ini
        $qty = static::parseCurrency($get('quantity'));
        if ($qty <= 0) {
            $qty = 1;
        }
        $unitPrice = static::parseCurrency($get('unit_price'));
        $subtotal = $qty * $unitPrice;

        $set('subtotal_price', $subtotal);

        // 2. Hitung total_price secara menyeluruh
        $repeaterItems = $get('../../detailSalesOrders') ?? [];
        $shippingCost = static::parseCurrency($get('../../shipping_cost'));

        $totalSubtotal = 0;
        if (is_array($repeaterItems)) {
            foreach ($repeaterItems as $item) {
                $iQty = static::parseCurrency($item['quantity'] ?? 1);
                if ($iQty <= 0) {
                    $iQty = 1;
                }
                $iPrice = static::parseCurrency($item['unit_price'] ?? 0);
                $totalSubtotal += ($iQty * $iPrice);
            }
        }

        // Bila repeater state belum memuat item baru / nilai yang baru saja diketik
        if ($totalSubtotal < $subtotal) {
            $totalSubtotal = $subtotal;
        }

        $totalPrice = $totalSubtotal + $shippingCost;

        $set('../../total_price', $totalPrice);
    }

    public static function updateTotalPriceFromRoot(Get $get, Set $set): void
    {
        $repeaterItems = $get('detailSalesOrders') ?? [];
        $shippingCost = static::parseCurrency($get('shipping_cost'));

        $totalSubtotal = 0;
        if (is_array($repeaterItems)) {
            foreach ($repeaterItems as $item) {
                $iQty = static::parseCurrency($item['quantity'] ?? 1);
                if ($iQty <= 0) {
                    $iQty = 1;
                }
                $iPrice = static::parseCurrency($item['unit_price'] ?? 0);
                $totalSubtotal += ($iQty * $iPrice);
            }
        }

        $totalPrice = $totalSubtotal + $shippingCost;

        $set('total_price', $totalPrice);
    }
}
