<?php

namespace App\Filament\Tables;

use App\Enum\PaymentFor;
use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Columns\SupplierColumn;
use App\Support\PublicStorageUrl;
use Carbon\Carbon;
use Filament\Tables\Columns\TextColumn;

/**
 * Skema kolom tabel PaymentReceipt. Visibility sebagian kolom mengikuti
 * `activeTab` dari ListPaymentReceipts (key: 'invoice' / 'fuel service' /
 * 'daily salary') yang di-resolve via PaymentFor::fromTabKey().
 */
class PaymentReceiptTable
{
    public static function schema(): array
    {
        $isTab = static fn ($livewire, PaymentFor $for): bool =>
            PaymentFor::fromTabKey($livewire->activeTab ?? null) === $for;

        return [
            ImageOpenUrlColumn::make('image')
                ->label('Payment')
                ->disk('public')
                ->url(fn ($record) => PublicStorageUrl::from($record->image)),

            ImageOpenUrlColumn::make('image_adjust')
                ->label('Adjust')
                ->disk('public')
                ->url(fn ($record) => PublicStorageUrl::from($record->image_adjust)),

            SupplierColumn::make('Supplier')
                ->visible(fn ($livewire) => ! $isTab($livewire, PaymentFor::DailySalary)),

            TextColumn::make('user.name')
                ->label('Employee')
                ->visible(fn ($livewire) => $isTab($livewire, PaymentFor::DailySalary)),

            TextColumn::make('created_at')
                ->date(),

            CurrencyColumn::make('transfer_amount'),

            // Kolom khusus invoice
            TextColumn::make('invoicePurchases.date')
                ->label('Date')
                ->visible(fn ($livewire) => $isTab($livewire, PaymentFor::InvoicePurchase)),

            TextColumn::make('invoicePurchases.createdBy.name')
                ->label('Created By')
                ->visible(fn ($livewire) => $isTab($livewire, PaymentFor::InvoicePurchase)),

            // Kolom khusus fuel service
            TextColumn::make('fuelServices.vehicle.no_register')
                ->label('Fuel Service Invoice')
                ->visible(fn ($livewire) => $isTab($livewire, PaymentFor::FuelService)),

            // Kolom khusus daily salary
            TextColumn::make('dailySalaries.date')
                ->label('Salary Date')
                ->visible(fn ($livewire) => $isTab($livewire, PaymentFor::DailySalary))
                ->formatStateUsing(function ($state, $record) {
                    return $record->dailySalaries->pluck('date')
                        ->map(fn ($date) => Carbon::parse($date)->format('d/m/Y'))
                        ->join(', ');
                }),

            // Kolom detail lengkap per tab
            TextColumn::make('payment_details')
                ->html()
                ->label(function ($livewire) {
                    return match (PaymentFor::fromTabKey($livewire->activeTab ?? null)) {
                        PaymentFor::InvoicePurchase => 'Invoice Details',
                        PaymentFor::FuelService => 'Fuel Service Details',
                        PaymentFor::DailySalary => 'Salary Details',
                        default => 'Details',
                    };
                })
                ->formatStateUsing(function ($state, $record, $livewire) {
                    return match (PaymentFor::fromTabKey($livewire->activeTab ?? null)) {
                        PaymentFor::InvoicePurchase => $record->invoicePurchases->map(function ($invoice) {
                            return "Invoice: {$invoice->invoice_purchase_name}<br>" .
                                "Amount: Rp " . number_format($invoice->total_price, 0, ',', '.');
                        })->join('<br><br>'),

                        PaymentFor::FuelService => $record->fuelServices->map(function ($fs) {
                            $typeStr = $fs->fuel_service == 1 ? 'Fuel' : 'Service';
                            return "Vehicle: {$fs->vehicle?->no_register}<br>" .
                                "Type: {$typeStr}<br>" .
                                "Amount: Rp " . number_format($fs->amount, 0, ',', '.');
                        })->join('<br><br>'),

                        PaymentFor::DailySalary => $record->dailySalaries->map(function ($salary) {
                            return "Date: " . Carbon::parse($salary->date)->format('d/m/Y') . "<br>" .
                                "Amount: Rp " . number_format($salary->amount, 0, ',', '.');
                        })->join('<br><br>'),

                        default => 'Details',
                    };
                }),
        ];
    }
}
