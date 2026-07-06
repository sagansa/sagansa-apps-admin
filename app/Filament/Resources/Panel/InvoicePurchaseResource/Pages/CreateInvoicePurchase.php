<?php

namespace App\Filament\Resources\Panel\InvoicePurchaseResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use App\Filament\Resources\Panel\InvoicePurchaseResource;
use Illuminate\Support\Facades\Auth;

class CreateInvoicePurchase extends CreateRecord
{
    protected static string $resource = InvoicePurchaseResource::class;

    protected function beforeCreate(): void
    {
        $hasEmptyInvoice = \App\Models\InvoicePurchase::where('created_by_id', Auth::id())
            ->whereDoesntHave('detailInvoices')
            ->exists();

        if ($hasEmptyInvoice) {
            \Filament\Notifications\Notification::make()
                ->title('Gagal Membuat Invoice')
                ->body('Anda masih memiliki invoice kosong (tanpa detail item). Silakan lengkapi atau hapus invoice kosong tersebut terlebih dahulu.')
                ->danger()
                ->send();

            $this->halt();
        }
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by_id'] = Auth::id();
        $data['payment_status'] = '1';
        $data['order_status'] = '1';

        return $data;
    }
}
