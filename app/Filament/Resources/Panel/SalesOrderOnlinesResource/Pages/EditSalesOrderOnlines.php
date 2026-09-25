<?php

namespace App\Filament\Resources\Panel\SalesOrderOnlinesResource\Pages;

use App\Filament\Resources\Panel\SalesOrderOnlinesResource;
use App\Models\SalesOrderOnline;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Auth;

class EditSalesOrderOnlines extends EditRecord
{
    protected static string $resource = SalesOrderOnlinesResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeSave(array $data): array
    {
        if (Auth::user()->hasRole('storage-staff')) {
            $data['assigned_by_id'] = Auth::id();
        }

        if (isset($data['detailSalesOrders']) && is_array($data['detailSalesOrders'])) {
            $subtotal = 0;
            foreach ($data['detailSalesOrders'] as $item) {
                $qty = (int) ($item['quantity'] ?? 1);
                $price = (int) preg_replace('/[^\d]/', '', (string) ($item['unit_price'] ?? 0));
                $subtotal += ($qty * $price);
            }
            $shipping = (int) preg_replace('/[^\d]/', '', (string) ($data['shipping_cost'] ?? 0));
            $data['total_price'] = $subtotal + $shipping;
        }

        return $data;
    }

    protected function afterSave(): void
    {
        \App\Support\SalesTotalPrice::recalculate($this->record);
    }
}
