<?php

namespace App\Filament\Tables;

use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Columns\PaymentStatusColumn;
use App\Support\PublicStorageUrl;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

class FuelServiceTable
{
    public static function schema(): array
    {
        return [
            ImageOpenUrlColumn::make('image')
                ->url(fn($record) => PublicStorageUrl::from($record->image)),

            TextColumn::make('fuel_service')
                ->formatStateUsing(
                    fn(string $state): string => match ($state) {
                        '1' => 'fuel',
                        '2' => 'service',
                    }
                )
                ->toggleable(isToggledHiddenByDefault: false),

            TextColumn::make('supplier')
                ->copyable()
                ->formatStateUsing(
                    fn($record): string => '<ul>' . implode('', [
                        '<li>Nama Supplier: ' . $record->supplier->name . '</li>',
                        '<li>Bank: ' . ($record->supplier->bank ? $record->supplier->bank->name : 'tidak tersedia') . '</li>',
                        '<li>Nama Rekening: ' . ($record->supplier->bank_account_name ? $record->supplier->bank_account_name : 'tidak tersedia') . '</li>',
                        '<li>No. Rekening: ' . ($record->supplier->bank_account_no ? $record->supplier->bank_account_no : 'tidak tersedia') . '</li>',
                    ]) . '</ul>'
                )
                ->html()
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('date'),

            TextColumn::make('vehicle.no_register'),

            TextColumn::make('vehicle.store.nickname')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('paymentType.name'),

            TextColumn::make('Rp/liter')
                ->getStateUsing(function ($record) {
                    if ($record->fuel_service === 1) {
                        return $record->liter > 0 ? $record->amount / $record->liter : 0;
                    } elseif ($record->fuel_service === 2) {
                        return '';
                    }
                })
                ->numeric(thousandsSeparator: '.')
                ->prefix('Rp ')
                ->suffix(' /l'),

            TextColumn::make('km')->numeric(thousandsSeparator: '.')->label('km'),

            TextColumn::make('liter')
                ->toggleable(isToggledHiddenByDefault: true),

            CurrencyColumn::make('amount')
                ->toggleable(isToggledHiddenByDefault: false)
                ->summarize(Sum::make()
                    ->prefix('Rp ')
                    ->label('')
                    ->numeric(thousandsSeparator: '.')),

            TextColumn::make('createdBy.name')
                ->hidden(fn() => !Auth::user()->hasRole('admin'))
                ->toggleable(isToggledHiddenByDefault: false),

            PaymentStatusColumn::make('status')
                ->formatStateUsing(
                    fn(string $state): string => match ($state) {
                        '1' => 'Belum Diperiksa',
                        '2' => 'Sudah Dibayar',
                        '3' => 'Siap Dibayar',
                        '4' => 'Periksa Ulang',
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

            TextColumn::make('paymentReceipt')
                ->hidden(fn() => !Auth::user()->hasRole('admin'))
                ->formatStateUsing(function ($record) {
                    return $record->paymentReceipts->first()?->created_at?->format('d/m/Y H:i');
                })
                ->toggleable(isToggledHiddenByDefault: false),

        ];
    }
}
