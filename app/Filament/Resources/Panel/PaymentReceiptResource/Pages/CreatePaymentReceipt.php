<?php

namespace App\Filament\Resources\Panel\PaymentReceiptResource\Pages;

use App\Enum\PaymentFor;
use App\Enum\PaymentType;
use App\Filament\Resources\Panel\PaymentReceiptResource;
use App\Models\InvoicePurchase;
use App\Models\PaymentReceipt;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\DB;

class CreatePaymentReceipt extends CreateRecord
{
    protected static string $resource = PaymentReceiptResource::class;

    /**
     * Override mount untuk mendukung prefill dari invoice via query string.
     *
     * Bila URL mengandung ?invoice_id=X DAN invoice eligible (belum dibayar,
     * tipe transfer, belum punya payment receipt), form di-fill dengan:
     *   payment_for=InvoicePurchase, invoicePurchases=[X],
     *   total_amount/transfer_amount=invoice.total_price,
     *   supplier_id=invoice.supplier_id.
     *
     * Bila tidak ada query atau invoice tidak eligible: flow normal (form kosong).
     */
    public function mount(): void
    {
        parent::mount();

        $invoiceId = request()->query('invoice_id');
        if (! $invoiceId) {
            return;
        }

        $invoice = InvoicePurchase::query()
            ->whereKey($invoiceId)
            ->where('payment_status', '1')
            ->where('payment_type_id', PaymentType::Transfer->value)
            ->first();

        if (! $invoice) {
            Notification::make()
                ->title('Invoice tidak dapat dibayar')
                ->body('Invoice sudah dibayar, tipe pembayaran bukan transfer, atau tidak ditemukan.')
                ->warning()
                ->send();

            return;
        }

        $this->form->fill([
            'payment_for' => PaymentFor::InvoicePurchase->value,
            'invoicePurchases' => [$invoice->id],
            'total_amount' => $invoice->total_price,
            'transfer_amount' => $invoice->total_price,
            'supplier_id' => $invoice->supplier_id,
        ]);
    }

    /**
     * Update status record terkait (invoice/salary/fuel) menjadi "sudah dibayar"
     * dalam satu DB transaction.
     *
     * Anti race-condition: sebelum update, re-verify tiap record masih berstatus
     * "siap dibayar". Jika ada yang sudah berubah (kemungkinan dibayar oleh
     * transaksi paralel), rollback seluruh payment receipt dan beri notifikasi.
     */
    protected function afterCreate(): void
    {
        $record = $this->getRecord();

        try {
            DB::transaction(function () use ($record): void {
                $paymentFor = PaymentFor::tryFrom((string) $record->payment_for)
                    ?? throw new \RuntimeException('Invalid payment_for value.');

                match ($paymentFor) {
                    PaymentFor::InvoicePurchase => $this->markInvoicePurchasesPaid($record),
                    PaymentFor::DailySalary => $this->markDailySalariesPaid($record),
                    PaymentFor::FuelService => $this->markFuelServicesPaid($record),
                };
            });
        } catch (\Throwable $e) {
            // Rollback pivot + record payment_receipt agar tidak ada data separuh.
            DB::transaction(function () use ($record): void {
                $record->invoicePurchases()->detach();
                $record->dailySalaries()->detach();
                $record->fuelServices()->detach();
                $record->forceDelete();
            });

            Notification::make()
                ->title('Gagal menyimpan payment receipt')
                ->body($e->getMessage() ?: 'Salah satu item sudah tidak siap dibayar. Silakan refresh dan coba lagi.')
                ->danger()
                ->persistent()
                ->send();

            $this->redirect(static::getResource()::getUrl('create'));
        }
    }

    /**
     * Tandai invoice sebagai sudah dibayar hanya jika saat ini masih unpaid.
     */
    private function markInvoicePurchasesPaid(PaymentReceipt $record): void
    {
        $unpaidStatus = '1'; // belum dibayar
        $paidStatus = '2';   // sudah dibayar

        foreach ($record->invoicePurchases as $invoicePurchase) {
            $updated = InvoicePurchase::query()
                ->whereKey($invoicePurchase->id)
                ->where('payment_status', $unpaidStatus)
                ->update(['payment_status' => $paidStatus]);

            if ($updated === 0) {
                throw new \RuntimeException(
                    "Invoice #{$invoicePurchase->id} sudah tidak berstatus 'belum dibayar'."
                );
            }
        }
    }

    /**
     * Tandai daily salary sebagai sudah dibayar hanya jika saat ini masih "siap dibayar" (status=3).
     */
    private function markDailySalariesPaid(PaymentReceipt $record): void
    {
        $readyStatus = '3'; // siap dibayar
        $paidStatus = '2';  // sudah dibayar

        foreach ($record->dailySalaries as $dailySalary) {
            $updated = \App\Models\DailySalary::query()
                ->whereKey($dailySalary->id)
                ->where('status', $readyStatus)
                ->update(['status' => $paidStatus]);

            if ($updated === 0) {
                throw new \RuntimeException(
                    "Daily salary #{$dailySalary->id} sudah tidak berstatus 'siap dibayar'."
                );
            }
        }
    }

    /**
     * Tandai fuel/service sebagai sudah dibayar hanya jika saat ini masih "siap dibayar" (status=1).
     */
    private function markFuelServicesPaid(PaymentReceipt $record): void
    {
        $readyStatus = '1'; // belum dibayar / siap dibayar untuk FuelService
        $paidStatus = '2';  // sudah dibayar

        foreach ($record->fuelServices as $fuelService) {
            $updated = \App\Models\FuelService::query()
                ->whereKey($fuelService->id)
                ->where('status', $readyStatus)
                ->update(['status' => $paidStatus]);

            if ($updated === 0) {
                throw new \RuntimeException(
                    "Fuel/Service #{$fuelService->id} sudah tidak berstatus 'siap dibayar'."
                );
            }
        }
    }
}
