<?php

namespace App\Filament\Tables;

use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Models\InvoicePurchase;
use App\Support\PublicStorageUrl;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

class InvoicePurchaseTable
{
    public static function schema(): array
    {
        return [
            // =========================================================
            // Desktop: tabel ringkas dengan kolom-kolom proper.
            // Mobile: $table->stackedOnMobile() (di Resource) render
            // tiap baris jadi card vertikal otomatis. Kolom dengan
            // ->hiddenFrom('md') hanya tampil di desktop.
            // =========================================================

            ImageOpenUrlColumn::make('image')
                ->visibility('public')
                ->url(fn (InvoicePurchase $record) => PublicStorageUrl::from($record->image)),

            TextColumn::make('date')
                ->date('d M Y')
                ->sortable()
                ->searchable()
                ->toggleable(),

            // Supplier — hanya nama (1 baris). Info bank/no rek
            // dipindah ke halaman View untuk hindari baris tinggi.
            TextColumn::make('supplier.name')
                ->label('Supplier')
                ->searchable()
                ->toggleable(),

            // Detail produk ringkas — 1 baris ("3 produk" atau nama pertama).
            // Rincian lengkap ada di halaman View.
            TextColumn::make('detail_summary')
                ->label('Items')
                ->state(function (InvoicePurchase $record): string {
                    $count = $record->detailInvoices->count();

                    if ($count === 0) {
                        return '—';
                    }

                    $first = $record->detailInvoices->first();
                    $firstName = $first?->detailRequest?->product?->name ?? 'Item';

                    return $count === 1
                        ? $firstName
                        : "{$firstName} +".($count - 1).' lainnya';
                })
                ->toggleable(),

            CurrencyColumn::make('total_price')
                ->searchable()
                ->summarize(Sum::make()
                    ->numeric(thousandsSeparator: '.')
                    ->label('')
                    ->prefix('Rp ')),

            TextColumn::make('payment_status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    '1' => 'warning',
                    '2' => 'success',
                    '3' => 'danger',
                    default => $state,
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    '1' => 'belum dibayar',
                    '2' => 'sudah dibayar',
                    '3' => 'tidak valid',
                    default => $state,
                }),

            TextColumn::make('order_status')
                ->badge()
                ->color(fn (string $state): string => match ($state) {
                    '1' => 'warning',
                    '2' => 'success',
                    '3' => 'danger',
                    default => $state,
                })
                ->formatStateUsing(fn (string $state): string => match ($state) {
                    '1' => 'belum diterima',
                    '2' => 'sudah diterima',
                    '3' => 'dikembalikan',
                    default => $state,
                }),

            // Kolom sekunder — hanya tampil di desktop (hidden on mobile).
            TextColumn::make('paymentType.name')
                ->toggleable(isToggledHiddenByDefault: true)
                ->hiddenFrom('md'),

            TextColumn::make('createdBy.name')
                ->hidden(fn () => Auth::user()?->hasRole('staff') ?? false)
                ->toggleable(isToggledHiddenByDefault: true)
                ->hiddenFrom('md'),

            TextColumn::make('payment_receipt_status')
                ->label('Payment Receipt')
                ->badge()
                ->state(fn (InvoicePurchase $record): string =>
                    $record->paymentReceipts->isNotEmpty() ? 'Sudah Dibayar' : 'Belum Dibayar'
                )
                ->color(fn (string $state): string => match ($state) {
                    'Sudah Dibayar' => 'success',
                    default => 'warning',
                })
                ->hiddenFrom('md'),
        ];
    }
}
