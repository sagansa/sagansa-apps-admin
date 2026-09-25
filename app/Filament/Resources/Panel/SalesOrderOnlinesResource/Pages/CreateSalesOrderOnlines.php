<?php

namespace App\Filament\Resources\Panel\SalesOrderOnlinesResource\Pages;

use App\Filament\Resources\Panel\SalesOrderOnlinesResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;

class CreateSalesOrderOnlines extends CreateRecord
{
    protected static string $resource = SalesOrderOnlinesResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['for'] = "3";
        $data['delivery_status'] = "1";
        $data['payment_status'] = "2";
        $data['ordered_by_id'] = $data['ordered_by_id'] ?? Auth::id();
        $data['shipping_cost'] = $data['shipping_cost'] ?? "0";

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

    protected function afterCreate(): void
    {
        \App\Support\SalesTotalPrice::recalculate($this->record);
    }
}
