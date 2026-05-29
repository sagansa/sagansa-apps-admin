<?php

namespace App\Filament\Tables;

use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Columns\StatusColumn;
use App\Models\AdvancePurchase;
use App\Support\PublicStorageUrl;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

class AdvancePurchaseTable
{
    public static function schema(): array
    {
        return [
            ImageOpenUrlColumn::make('image')->visibility('public')
                ->url(fn($record) => PublicStorageUrl::from($record->image)),

            TextColumn::make('store.nickname'),

            TextColumn::make('date'),

            TextColumn::make('detailAdvancePurchases')
                ->label('Detail Purchases')
                ->html()
                ->formatStateUsing(function (AdvancePurchase $record) {
                    return implode('<br>', $record->detailAdvancePurchases->map(function ($item) {
                        $unitPrice = number_format($item->unit_price, 0, ',', '.'); // add thousands separator
                        return "{$item->product->name} ({$item->quantity} {$item->product->unit->unit}) - Rp {$unitPrice}"; // add "Rp" prefix
                    })->toArray());
                })
                ->extraAttributes(['class' => 'whitespace-pre-wrap']),

            CurrencyColumn::make('total_price'),

            StatusColumn::make('status'),
        ];
    }
}
