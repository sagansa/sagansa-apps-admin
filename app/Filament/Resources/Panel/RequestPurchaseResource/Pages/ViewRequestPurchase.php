<?php

namespace App\Filament\Resources\Panel\RequestPurchaseResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use App\Filament\Resources\Panel\RequestPurchaseResource;
use App\Filament\Resources\Panel\InvoicePurchaseResource;

class ViewRequestPurchase extends ViewRecord
{
    protected static string $resource = RequestPurchaseResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\EditAction::make(),
            Actions\Action::make('createInvoice')
                ->label('Buat Invoice')
                ->icon('heroicon-o-document-plus')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Buat Invoice Otomatis')
                ->modalDescription('Apakah Anda yakin ingin membuat Invoice dari semua item yang sudah disetujui?')
                ->visible(fn () => $this->record->detailRequests->where('status', '4')->isNotEmpty())
                ->action(function () {
                    $hasEmptyInvoice = \App\Models\InvoicePurchase::where('created_by_id', \Illuminate\Support\Facades\Auth::id())
                        ->whereDoesntHave('detailInvoices')
                        ->exists();

                    if ($hasEmptyInvoice) {
                        \Filament\Notifications\Notification::make()
                            ->title('Gagal Membuat Invoice')
                            ->body('Anda masih memiliki invoice kosong (tanpa detail item). Silakan lengkapi atau hapus invoice kosong tersebut terlebih dahulu.')
                            ->danger()
                            ->send();
                        return;
                    }

                    $approvedItems = $this->record->detailRequests->where('status', '4');
                    if ($approvedItems->isEmpty()) {
                        return;
                    }

                    \Illuminate\Support\Facades\DB::transaction(function () use ($approvedItems) {
                        $firstItem = $approvedItems->first();

                        $invoice = \App\Models\InvoicePurchase::create([
                            'store_id' => $this->record->store_id,
                            'date' => now()->toDateString(),
                            'payment_status' => '1',
                            'order_status' => '1',
                            'created_by_id' => \Illuminate\Support\Facades\Auth::id(),
                            'payment_type_id' => $firstItem->payment_type_id ?? 2,
                            'total_price' => 0,
                        ]);

                        foreach ($approvedItems as $item) {
                            \App\Models\DetailInvoice::create([
                                'invoice_purchase_id' => $invoice->id,
                                'detail_request_id' => $item->id,
                                'quantity_product' => $item->quantity_plan,
                                'subtotal_invoice' => 0,
                                'status' => '3',
                            ]);
                        }

                        $this->redirect(InvoicePurchaseResource::getUrl('edit', ['record' => $invoice]));
                    });
                })
                ->tooltip('Buat invoice draft dari request yang sudah diapprove'),
        ];
    }
}
