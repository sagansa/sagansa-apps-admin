<?php

namespace App\Filament\Forms;

use App\Models\Product;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Illuminate\Database\Eloquent\Builder;

class StockRepeaterForm
{
    public static function getRemainingRepeater(): Repeater
    {
        $products = Product::where('remaining', '1')->orderBy('name', 'asc')->get()->map(function ($item) {
            return [
                'product_id' => $item->id,
                'quantity' => $item->quantity,
            ];
        })->toArray();

        return Repeater::make('detailStockCards')
            ->hiddenLabel()
            ->default($products)
            ->relationship()
            ->addable(false)
            ->deletable(false)
            ->columns(12)
            ->schema([
                \Filament\Forms\Components\Hidden::make('product_id'),

                \Filament\Forms\Components\Placeholder::make('product_name')
                    ->label('Produk')
                    ->hiddenLabel()
                    ->content(fn($get) => Product::find($get('product_id'))?->name)
                    ->extraAttributes(['class' => 'pt-2 font-medium text-gray-700 dark:text-gray-200'])
                    ->columnSpan(6),

                \Filament\Forms\Components\Placeholder::make('previous_quantity')
                    ->label('Sebelumnya')
                    ->content(function ($get) {
                        $storeId = $get('../../store_id');
                        $productId = $get('product_id');
                        if (!$storeId || !$productId) {
                            return '-';
                        }

                        $latestStockCardQuery = \App\Models\StockCard::where('store_id', $storeId)
                            ->where('for', 'remaining_store');

                        $recordId = $get('../../id');
                        if ($recordId) {
                            $latestStockCardQuery->where('id', '<>', $recordId);
                            $currentDate = $get('../../date');
                            if ($currentDate) {
                                $latestStockCardQuery->where('date', '<', $currentDate);
                            }
                        }

                        $latestStockCard = $latestStockCardQuery
                            ->latest('date')
                            ->latest('id')
                            ->first();

                        if (!$latestStockCard) {
                            return '0';
                        }

                        $detail = $latestStockCard->detailStockCards()
                            ->where('product_id', $productId)
                            ->first();

                        $qty = $detail ? $detail->quantity : 0;
                        $product = Product::find($productId);
                        $unit = $product ? $product->unit->unit : '';

                        return number_format($qty, 0, ',', '.') . ' ' . $unit;
                    })
                    ->extraAttributes(['class' => 'pt-2 text-sm text-gray-500 dark:text-gray-400'])
                    ->columnSpan(3),

                NominalRepeaterInput::make('quantity')
                    ->label('Jumlah')
                    ->placeholder('0')
                    ->suffix(function ($get) {
                        $product = Product::find($get('product_id'));
                        return $product ? $product->unit->unit : '';
                    })
                    ->columnSpan(3),
            ]);
    }

    public static function getStorageRepeater(): Repeater
    {
        $products = Product::where('request', '1')->orderBy('name', 'asc')->get()->map(function ($item) {
            return [
                'product_id' => $item->id,
                'quantity' => $item->quantity,
            ];
        })->toArray();

        return Repeater::make('detailStockCards')
            ->hiddenLabel()
            ->default($products)
            ->relationship()
            ->addable(false)
            ->deletable(false)
            ->columns(12)
            ->schema([
                \Filament\Forms\Components\Hidden::make('product_id'),

                \Filament\Forms\Components\Placeholder::make('product_name')
                    ->label('Produk')
                    ->hiddenLabel()
                    ->content(fn($get) => Product::find($get('product_id'))?->name)
                    ->extraAttributes(['class' => 'pt-2 font-medium text-gray-700 dark:text-gray-200'])
                    ->columnSpan(6),

                \Filament\Forms\Components\Placeholder::make('previous_quantity')
                    ->label('Sebelumnya')
                    ->content(function ($get) {
                        $storeId = $get('../../store_id');
                        $productId = $get('product_id');
                        if (!$storeId || !$productId) {
                            return '-';
                        }

                        $latestStockCardQuery = \App\Models\StockCard::where('store_id', $storeId)
                            ->where('for', 'remaining_storage');

                        $recordId = $get('../../id');
                        if ($recordId) {
                            $latestStockCardQuery->where('id', '<>', $recordId);
                            $currentDate = $get('../../date');
                            if ($currentDate) {
                                $latestStockCardQuery->where('date', '<', $currentDate);
                            }
                        }

                        $latestStockCard = $latestStockCardQuery
                            ->latest('date')
                            ->latest('id')
                            ->first();

                        if (!$latestStockCard) {
                            return '0';
                        }

                        $detail = $latestStockCard->detailStockCards()
                            ->where('product_id', $productId)
                            ->first();

                        $qty = $detail ? $detail->quantity : 0;
                        $product = Product::find($productId);
                        $unit = $product ? $product->unit->unit : '';

                        return number_format($qty, 0, ',', '.') . ' ' . $unit;
                    })
                    ->extraAttributes(['class' => 'pt-2 text-sm text-gray-500 dark:text-gray-400'])
                    ->columnSpan(3),

                NominalRepeaterInput::make('quantity')
                    ->label('Jumlah')
                    ->placeholder('0')
                    ->suffix(function ($get) {
                        $product = Product::find($get('product_id'));
                        return $product ? $product->unit->unit : '';
                    })
                    ->columnSpan(3),
            ]);
    }
}
