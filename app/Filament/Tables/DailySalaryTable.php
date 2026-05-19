<?php

namespace App\Filament\Tables;

use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\PaymentStatusColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;

class DailySalaryTable
{
    public static function schema(): array
    {
        return [
            TextColumn::make('createdBy.name')
                ->label('For'),

            TextColumn::make('store.nickname')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('shiftStore.name'),

            TextColumn::make('date'),

            CurrencyColumn::make('amount')
                ->summarize(Sum::make()
                    ->prefix('Rp ')
                    ->label('')
                    ->numeric(thousandsSeparator: '.')),

            PaymentStatusColumn::make('status')
                ->formatStateUsing(
                    fn(string $state): string => match ($state) {
                        '1' => 'Belum Dibayar',
                        '2' => 'Sudah Dibayar',
                        '3' => 'Siap Dibayar',
                        '4' => 'Perbaiki',
                        default => $state,
                    }
                )
                ->badge()
                ->color(
                    fn(string $state): string => match ($state) {
                        '1' => 'warning',
                        '2' => 'success',
                        '3' => 'info',
                        '4' => 'danger',
                        default => 'gray',
                    }
                ),

            TextColumn::make('paymentType.name')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
