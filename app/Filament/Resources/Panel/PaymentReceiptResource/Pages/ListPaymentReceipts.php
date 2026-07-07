<?php

namespace App\Filament\Resources\Panel\PaymentReceiptResource\Pages;

use App\Enum\PaymentFor;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\PaymentReceiptResource;
use Filament\Schemas\Components\Tabs\Tab;

class ListPaymentReceipts extends ListRecords
{
    protected static string $resource = PaymentReceiptResource::class;

    protected function getHeaderActions(): array
    {
        return [Actions\CreateAction::make()];
    }

    public function getTabs(): array
    {
        $tabs = [];

        // Urutan tampil mengikuti default tab (invoice dulu), lalu fuel service, daily salary.
        foreach ([
            PaymentFor::InvoicePurchase,
            PaymentFor::FuelService,
            PaymentFor::DailySalary,
        ] as $case) {
            $tabs[$case->tabKey()] = Tab::make()
                ->query(fn ($query) => $query->where('payment_for', $case->value));
        }

        return $tabs;
    }

    protected function getDefaultTab(): ?string
    {
        return PaymentFor::InvoicePurchase->tabKey();
    }
}
