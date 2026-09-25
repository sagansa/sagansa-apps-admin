<?php

namespace App\Filament\Resources\Panel\SalesOrderDirectsResource\Pages;

use App\Filament\Resources\Panel\SalesOrderDirectsResource;
use Filament\Notifications\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Support\Facades\Auth;
use App\Models\User;
use Illuminate\Support\Facades\Notification as LaravelNotification;
use App\Notifications\NewDataCreatedNotification;

class CreateSalesOrderDirects extends CreateRecord
{
    protected static string $resource = SalesOrderDirectsResource::class;

    protected function getRedirectUrl(): string
    {
        return $this->getResource()::getUrl('index');
    }

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['for'] = '1';
        $data['payment_status'] = '1';
        $data['delivery_status'] = '1';
        $data['ordered_by_id'] = $data['ordered_by_id'] ?? Auth::id();

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
        $order = $this->record;
        \App\Support\SalesTotalPrice::recalculate($order);
        
        try {
            // 1. Kirim Notifikasi ke Admin
            \Illuminate\Support\Facades\Mail::to('asapanganbangsa@gmail.com')
                ->send(new \App\Mail\NewSalesOrderDirectMail($order));
            
            // 2. Kirim Tanda Terima ke Customer
            $customerEmail = $order->orderedBy?->email;
            if ($customerEmail) {
                \Illuminate\Support\Facades\Mail::to($customerEmail)
                    ->send(new \App\Mail\SalesOrderDirectCustomerMail($order));
            }
            
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Failed to send Sales Order Direct email: ' . $e->getMessage());
        }
    }
}
