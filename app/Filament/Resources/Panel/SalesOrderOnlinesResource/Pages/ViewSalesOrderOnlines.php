<?php

namespace App\Filament\Resources\Panel\SalesOrderOnlinesResource\Pages;

use App\Filament\Entries\DeliveryStatusEntry;
use App\Filament\Resources\Panel\SalesOrderOnlinesResource;
use Filament\Actions;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Group;
use Filament\Infolists\Components\ImageEntry;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Schemas\Components\Section;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Resources\Pages\ViewRecord;
use Icetalker\FilamentTableRepeatableEntry\Infolists\Components\TableRepeatableEntry;

class ViewSalesOrderOnlines extends ViewRecord
{
    protected static string $resource = SalesOrderOnlinesResource::class;

    public function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                Section::make('Order')
                    ->schema([
                        Grid::make(2)
                            ->schema([
                                Group::make([
                                    TextEntry::make('store.nickname'),
                                    TextEntry::make('delivery_date'),
                                    TextEntry::make('onlineShopProvider.name'),
                                    TextEntry::make('deliveryService.name'),
                                    TextEntry::make('deliveryAddress.name'),
                                    ImageEntry::make('image_payment')
                                        ->size(40)
                                        ->square(),
                                ]),
                                Group::make([
                                    TextEntry::make('receipt_no'),
                                    DeliveryStatusEntry::make('delivery_status'),
                                    TextEntry::make('orderedBy.name'),
                                    TextEntry::make('assignedBy.name'),
                                    TextEntry::make('total_price')
                                        ->prefix('Rp ')
                                        ->numeric(
                                            thousandsSeparator: '.'
                                        ),
                                    ImageEntry::make('image_delivery'),
                                ]),
                            ]),
                    ]),


                Section::make('Detail Order')
                    ->schema([
                        RepeatableEntry::make('detailSalesOrders')
                            ->schema([
                                TextEntry::make('product.product_name'),
                                TextEntry::make('quantity'),
                                TextEntry::make('unit_price')
                                    ->numeric(
                                        thousandsSeparator: '.'
                                    ),
                                TextEntry::make('subtotal_price')
                                    ->prefix('Rp ')
                                    ->numeric(
                                        thousandsSeparator: '.'
                                    ),
                            ])
                            ->columns(4),
                        ]),
                ]);
    }
}
