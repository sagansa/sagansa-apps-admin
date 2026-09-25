<?php

namespace App\Filament\Resources\Panel\SalesOrderDirectsResource\Pages;

use App\Filament\Resources\Panel\SalesOrderDirectsResource;
use App\Models\SalesOrderDirect;
use App\Support\SalesTotalPrice;
use Filament\Actions;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Resources\Pages\ListRecords;

class ListSalesOrderDirects extends ListRecords
{
    protected static string $resource = SalesOrderDirectsResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return SalesOrderDirectsResource::getWidgets();
    }

    public function getTabs(): array
    {
        return [
            null => Tab::make('All'),
            'belum dikirim' => Tab::make()->query(fn ($query) => $query->where('delivery_status', '1')),
            'valid' => Tab::make()->query(fn ($query) => $query->where('delivery_status', '2')),
            'sudah dikirim' => Tab::make()->query(fn ($query) => $query->where('delivery_status', '3')),
            'siap dikirim' => Tab::make()->query(fn ($query) => $query->where('delivery_status', '4')),
            'perbaiki' => Tab::make()->query(fn ($query) => $query->where('delivery_status', '5')),
            'dikembalikan' => Tab::make()->query(fn ($query) => $query->where('delivery_status', '6')),
            'anomali total' => Tab::make()
                ->label('Anomali Total')
                ->icon('heroicon-m-exclamation-triangle')
                ->badge(fn (): int => SalesTotalPrice::applyMismatchScope(
                    SalesOrderDirect::query()->where('for', 1)
                )->count())
                ->badgeColor('danger')
                ->query(fn ($query) => SalesTotalPrice::applyMismatchScope($query)),
        ];
    }
}
