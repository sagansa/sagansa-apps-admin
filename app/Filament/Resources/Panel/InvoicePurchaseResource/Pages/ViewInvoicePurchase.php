<?php

namespace App\Filament\Resources\Panel\InvoicePurchaseResource\Pages;

use App\Enum\PaymentType;
use App\Filament\Resources\Panel\InvoicePurchaseResource;
use App\Filament\Resources\Panel\PaymentReceiptResource;
use App\Models\InvoicePurchase;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewInvoicePurchase extends ViewRecord
{
    protected static string $resource = InvoicePurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('createPaymentReceipt')
                ->label('Payment Receipt')
                ->icon('heroicon-o-banknotes')
                ->visible(fn (InvoicePurchase $record): bool =>
                    $record->payment_status === '1'
                    && $record->payment_type_id === PaymentType::Transfer->value
                    && $record->paymentReceipts()->doesntExist()
                )
                ->url(fn (InvoicePurchase $record): string =>
                    PaymentReceiptResource::getUrl('create', [
                        'invoice_id' => $record->id,
                    ])
                ),
            Actions\EditAction::make(),
        ];
    }
}
