<?php

namespace App\Filament\Resources\Panel\SalesOrderOnlinesResource\Pages;

use App\Filament\Resources\Panel\SalesOrderOnlinesResource;
use App\Models\SalesOrderOnline;
use Filament\Resources\Pages\ViewRecord;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PrintPaymentProofSalesOrderOnlines extends ViewRecord
{
    protected static string $resource = SalesOrderOnlinesResource::class;

    public SalesOrderOnline $record;

    protected function getTableQuery(): ?\Illuminate\Database\Query\Builder
    {
        return null;
    }

    public function mount(int|string $record): void
    {
        parent::mount($record);

        if (Schema::hasColumn('sales_orders', 'payment_proof_printed_at')) {
            $this->record->update([
                'payment_proof_printed_at' => now(),
                'payment_proof_print_count' => DB::raw('COALESCE(payment_proof_print_count, 0) + 1'),
            ]);
        }
    }

    public function getLayout(): string
    {
        return 'apps.admin.resources.views.filament.resources.sales-order-onlines.print-payment-proof';
    }
}