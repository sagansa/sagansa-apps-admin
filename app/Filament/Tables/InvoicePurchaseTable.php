<?php

namespace App\Filament\Tables;

use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Models\InvoicePurchase;
use App\Support\PublicStorageUrl;
use Filament\Tables\Columns\Layout\Panel;
use Filament\Tables\Columns\Layout\Split;
use Filament\Tables\Columns\Layout\Stack;
use Filament\Tables\Columns\Summarizers\Sum;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

class InvoicePurchaseTable
{
    public static function schema(): array
    {
        return [
            // =========================================================
            // ROW UTAMA — Split horizontal. Filament otomatis render
            // sebagai card vertikal di mobile.
            // =========================================================
            Split::make([
                // Image square kecil — mobile only
                ImageOpenUrlColumn::make('image')
                    ->visibility('public')
                    ->url(fn (InvoicePurchase $record) => PublicStorageUrl::from($record->image))
                    ->grow(false)
                    ->hiddenFrom('md'),

                // Image normal — desktop only
                ImageOpenUrlColumn::make('image')
                    ->visibility('public')
                    ->url(fn (InvoicePurchase $record) => PublicStorageUrl::from($record->image))
                    ->grow(false)
                    ->visibleFrom('md'),

                // Stack info utama (desktop): supplier + store·date
                Stack::make([
                    TextColumn::make('supplier.name')
                        ->weight('bold')
                        ->searchable(),
                    Split::make([
                        TextColumn::make('store.nickname')
                            ->color('gray')
                            ->size('sm')
                            ->searchable(),
                        TextColumn::make('date')
                            ->date('d M Y')
                            ->color('gray')
                            ->size('sm')
                            ->searchable(),
                    ])->grow(false),
                ])->visibleFrom('md'),

                // Stack info utama (mobile): supplier + subtitle 1 baris
                Stack::make([
                    TextColumn::make('supplier.name')
                        ->weight('bold')
                        ->searchable(),
                    TextColumn::make('mobile_subtitle')
                        ->label('Store · Date')
                        ->state(function (InvoicePurchase $record): string {
                            return sprintf(
                                '%s · %s',
                                $record->store?->nickname ?? '-',
                                $record->date?->format('d M Y') ?? '-',
                            );
                        })
                        ->color('gray')
                        ->size('sm'),
                ])->hiddenFrom('md'),

                // Stack kanan: total + 2 badge status
                Stack::make([
                    CurrencyColumn::make('total_price')
                        ->searchable()
                        ->summarize(Sum::make()
                            ->numeric(thousandsSeparator: '.')
                            ->label('')
                            ->prefix('Rp ')),
                    Split::make([
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
                    ])->grow(false),
                ])->alignment('end'),
            ]),

            // =========================================================
            // PANEL COLLAPSIBLE: detail supplier lengkap + rincian produk
            // =========================================================
            Panel::make([
                Stack::make([
                    TextColumn::make('supplier_full_info')
                        ->label('Supplier')
                        ->html()
                        ->getStateUsing(function (InvoicePurchase $record): string {
                            $parts = collect([
                                $record->supplier?->name,
                                $record->supplier?->bank?->name ? 'Bank: ' . $record->supplier->bank->name : null,
                                $record->supplier?->bank_account_name ? 'Nama Rek: ' . $record->supplier->bank_account_name : null,
                                $record->supplier?->bank_account_no ? 'No Rek: ' . $record->supplier->bank_account_no : null,
                            ])->filter();

                            return $parts->isEmpty()
                                ? '<em>Tidak ada info supplier.</em>'
                                : $parts->map(fn ($line) => e($line))->implode(' · ');
                        }),
                    TextColumn::make('detail_products_full')
                        ->label('Rincian Produk')
                        ->html()
                        ->getStateUsing(function (InvoicePurchase $record): string {
                            if ($record->detailInvoices->isEmpty()) {
                                return '<em>Tidak ada rincian produk.</em>';
                            }

                            return $record->detailInvoices->map(function ($detail) {
                                $product = $detail->detailRequest?->product;
                                $qty = number_format($detail->quantity_product ?? 0, 0, ',', '.');
                                $unit = $product?->unit?->unit ?? '';
                                $subtotal = number_format($detail->subtotal_invoice ?? 0, 0, ',', '.');

                                return sprintf(
                                    '%s - %s %s - Rp %s',
                                    e($product?->name ?? '-'),
                                    $qty,
                                    e($unit),
                                    $subtotal,
                                );
                            })->implode('<br>');
                        }),
                ]),
            ])->collapsible(),

            // =========================================================
            // Kolom tambahan toggleable (hidden by default)
            // =========================================================
            TextColumn::make('paymentType.name')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('createdBy.name')
                ->hidden(fn () => Auth::user()?->hasRole('staff') ?? false)
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('payment_receipt_status')
                ->label('Payment Receipt')
                ->badge()
                ->state(fn (InvoicePurchase $record): string =>
                    $record->paymentReceipts->isNotEmpty() ? 'Sudah Dibayar' : 'Belum Dibayar'
                )
                ->color(fn (string $state): string => match ($state) {
                    'Sudah Dibayar' => 'success',
                    default => 'warning',
                }),
        ];
    }
}
