<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Sales;
use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\DeliveryStatusColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Filters\SelectStoreFilter;
use App\Filament\Forms\CurrencyInput;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\Notes;
use App\Filament\Forms\StoreSelect;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\SalesOrderRetur;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\FileUpload;
use App\Filament\Resources\Panel\SalesOrderReturResource\Pages;
use App\Filament\Resources\Panel\SalesOrderReturResource\RelationManagers;
use Filament\Forms\Components\Placeholder;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Support\Facades\Auth;

class SalesOrderReturResource extends Resource
{
    protected static ?string $model = SalesOrderRetur::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 4;


    protected static ?string $pluralLabel = 'Retur';

    protected static ?string $cluster = Sales::class;

    // public static function getModelLabel(): string
    // {
    //     return __('crud.salesOrderReturs.itemTitle');
    // }

    // public static function getPluralModelLabel(): string
    // {
    //     return __('crud.salesOrderReturs.collectionTitle');
    // }

    // public static function getNavigationLabel(): string
    // {
    //     return __('crud.salesOrderReturs.collectionTitle');
    // }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->inlineLabel()->schema([
                    ImageInput::make('image_delivery'),
                ]),

                Grid::make(['default' => 2])->inlineLabel()->schema([

                    Placeholder::make('store_id')
                        ->label('Store')
                        ->content(fn(SalesOrderRetur $record): string => optional($record->store)->nickname ?? '-'),

                    Placeholder::make('delivery_date')
                        ->label('Delivery Date')
                        ->content(fn(SalesOrderRetur $record): string => $record->delivery_date ?? '-'),

                    Placeholder::make('online_shop_provider_id')
                        ->label('Online Shop')
                        ->content(fn(SalesOrderRetur $record): string => optional($record->onlineShopProvider)->name ?? '-'),

                    Placeholder::make('delivery_service_id')
                        ->label('Delivery Service')
                        ->content(fn(SalesOrderRetur $record): string => optional($record->deliveryService)->name ?? '-'),

                    Placeholder::make('receipt_no')
                        ->content(fn(SalesOrderRetur $record): string => $record->receipt_no ?? '-'),

                    Placeholder::make('Total Price')
                        ->content(fn(SalesOrderRetur $record): string => 'Rp ' . number_format($record->total_price, 0, ',', '.') . '')
                        ->visible(fn() => Auth::user()->hasRole('admin')),

                ]),
                Grid::make(['default' => 1])->schema([
                    Select::make('delivery_status')
                        ->required()
                        ->inlineLabel()
                        ->hidden(fn($operation) => $operation === 'create')
                        ->options([
                            '3' => 'sudah dikirim',
                            '6' => 'dikembalikan'
                        ]),

                    Notes::make('notes')
                        ->required(fn ($get) => $get('delivery_status') == '6')
                        ->placeholder('Silakan tulis alasan barang dikembalikan (misal: Alamat tidak ditemukan, pelanggan menolak bayar COD, dll.)'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->query(SalesOrderRetur::query()->where('for', 3)->where('delivery_status', ['3', '6']))
            ->columns([
                // ImageOpenUrlColumn::make('image_payment')
                //     ->disabled(fn() => Auth::user()->hasRole('staff') || Auth::user()->hasRole('storage-staff'))
                //     ->label('Payment')
                //     ->url(fn($record) => asset('storage/' . $record->image_payment)),

                // ImageOpenUrlColumn::make('image_delivery')
                //     ->label('Delivery')
                //     ->url(fn($record) => asset('storage/' . $record->image_delivery)),

                TextColumn::make('delivery_date'),

                TextColumn::make('store.nickname'),

                TextColumn::make('receipt_no')
                    ->searchable(),

                TextColumn::make('onlineShopProvider.name'),

                TextColumn::make('deliveryService.name'),

                TextColumn::make('deliveryAddress.name'),

                TextColumn::make('orderedBy.name'),

                TextColumn::make('assignedBy.name'),

                CurrencyColumn::make('total_price')
                    ->label('Total Price')
                    ->visible(fn() => Auth::user()->hasRole('admin'))
                    ->summarize(Sum::make()
                        ->numeric(
                            thousandsSeparator: '.'
                        )
                        ->label('')
                        ->prefix('Rp ')),

                TextColumn::make('orders_list')
                    ->label('Orders')
                    ->html()
                    ->state(function (SalesOrderRetur $record) {
                        return implode('<br>', $record->detailSalesOrders->map(function ($item) {
                            return "{$item->product->name} ({$item->quantity} {$item->product->unit->unit})";
                        })->toArray());
                    })
                    ->extraAttributes(['class' => 'whitespace-pre-wrap']),

                DeliveryStatusColumn::make('delivery_status')
                    ->label('Status'),

            ])
            ->filters([
                SelectStoreFilter::make('store_id'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make()
                        ->visible(fn(SalesOrderRetur $record) => !in_array($record->delivery_status, [2]))->slideOver(),
                    \Filament\Actions\ViewAction::make()
                        ->visible(fn(SalesOrderRetur $record) => in_array($record->delivery_status, [2])),
                    Tables\Actions\DeleteAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                    Tables\Actions\RestoreAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                    Tables\Actions\ForceDeleteAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                    Tables\Actions\RestoreBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                    Tables\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                ]),
            ])
            ->defaultSort('delivery_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrderReturs::route('/'),
            'create' => Pages\CreateSalesOrderRetur::route('/create'),
            // 'view' => Pages\ViewSalesOrderRetur::route('/{record}'),
            // 'edit' => Pages\EditSalesOrderRetur::route('/{record}/edit'),
        ];
    }
}
