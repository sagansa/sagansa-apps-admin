<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Sales;
use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\DeliveryAddressColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Columns\PaymentStatusColumn;
use App\Filament\Filters\TotalPriceFilter;
use App\Filament\Forms\BottomTotalPriceForm;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\DeliveryAddressForm;
use App\Filament\Forms\SalesProductForm;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\StoreSelect;
use App\Filament\Resources\Panel\SalesOrderEmployeesResource\Pages;
use App\Models\DeliveryAddress;
use App\Models\TransferToAccount;
use App\Models\SalesOrderEmployee;
use App\Support\PublicStorageUrl;
use App\Support\SalesTotalPrice;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

class SalesOrderEmployeesResource extends Resource
{
    protected static ?string $model = SalesOrderEmployee::class;


    protected static ?int $navigationSort = 2;

    protected static ?string $pluralLabel = 'Employee';

    protected static ?string $cluster = Sales::class;

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Group::make()->schema([
                Section::make()
                    ->schema(static::getDetailsFormHeadSchema())
                    ->columns(2),

                Section::make('Detail Order')->schema([
                    SalesProductForm::getItemsRepeater()
                ]),
            ])
            ->columnSpan(['lg' => 2]),

            Section::make()
                ->schema(BottomTotalPriceForm::schema())
                ->columnSpan(['lg' => 1]),
        ])
        ->columns(3)
        ->disabled(fn (?SalesOrderEmployee $record) => $record !== null
            && $record->payment_status == 2
            && !Auth::user()->hasRole('super_admin'));
    }

    public static function table(Table $table): Table
    {
        $query = SalesOrderEmployee::query();

        if (Auth::user()->hasRole('sales')) {
            $query->where('ordered_by_id', Auth::id());
        }

        $query->where('for', 2);

        return $table
            ->query($query)
            ->columns([
                TextColumn::make('image_payment')
                    ->label('Transfer')
                    ->formatStateUsing(fn ($state) => $state ? 'Lihat' : '-')
                    ->icon(fn ($state) => $state ? 'heroicon-o-photo' : null)
                    ->color('info')
                    ->url(fn($record) => PublicStorageUrl::from($record->image_payment))
                    ->openUrlInNewTab(),

                TextColumn::make('delivery_date')
                    ->label('Date'),

                CurrencyColumn::make('detailSalesOrders.subtotal_price')
                    ->label('Subtotal Produk')
                    ->state(fn (SalesOrderEmployee $record) => $record->detailSalesOrders->sum('subtotal_price'))
                    ->description(fn (?SalesOrderEmployee $record): string => $record ? $record->detailSalesOrders->count() . ' item' : '')
                    ->summarize(Sum::make()
                        ->numeric(
                            thousandsSeparator: '.'
                        )
                        ->label('')
                        ->prefix('Rp ')),

                CurrencyColumn::make('shipping_cost')
                    ->label('Ongkir')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->summarize(Sum::make()
                        ->numeric(
                            thousandsSeparator: '.'
                        )
                        ->label('')
                        ->prefix('Rp ')),

                CurrencyColumn::make('total_price')
                    ->label('Total Price')
                    ->weight(FontWeight::Bold)
                    ->sortable()
                    ->color(fn (?SalesOrderEmployee $record): ?string => $record && SalesTotalPrice::isMismatched($record) ? 'danger' : null)
                    ->icon(fn (?SalesOrderEmployee $record): ?string => $record && SalesTotalPrice::isMismatched($record) ? 'heroicon-m-exclamation-triangle' : null)
                    ->iconPosition('after')
                    ->description(function (?SalesOrderEmployee $record): string {
                        if ($record === null) {
                            return '';
                        }

                        $rupiah = fn (int $n): string => number_format($n, 0, ',', '.');

                        return SalesTotalPrice::isMismatched($record)
                            ? "seharusnya Rp {$rupiah(SalesTotalPrice::expectedTotal($record))}"
                            : "produk Rp {$rupiah($record->detailSalesOrders->sum('subtotal_price'))} + ongkir Rp {$rupiah((int) ($record->shipping_cost ?? 0))}";
                    })
                    ->summarize(Sum::make()
                        ->numeric(
                            thousandsSeparator: '.'
                        )
                        ->label('')
                        ->prefix('Rp ')),

                DeliveryAddressColumn::make('deliveryAddress')
                    ->label('Customer'),

                TextColumn::make('transferToAccount.transfer_account_name')
                    ->label('Transfer to Account'),

                TextColumn::make('orders_list')
                    ->label('Orders')
                    ->html()
                    ->state(function (SalesOrderEmployee $record) {
                        return implode('<br>', $record->detailSalesOrders->map(function ($item) {
                            return "{$item->product->name} ({$item->quantity} {$item->product->unit->unit})";
                        })->toArray());
                    })
                    ->extraAttributes(['class' => 'whitespace-pre-wrap']),

                PaymentStatusColumn::make('payment_status')
                    ->label('Payment Status'),

                TextColumn::make('orderedBy.name')
                    ->label('Sales')
                    ->visible(fn ($record) => auth()->user()->hasRole('admin') || auth()->user()->hasRole('super_admin')),
            ])
            ->filters([
                TotalPriceFilter::make('total_price'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                    Action::make('recalculateTotalPrice')
                        ->label('Hitung Ulang Total')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin']))
                        ->requiresConfirmation()
                        ->modalHeading('Hitung ulang total price')
                        ->modalDescription(fn (SalesOrderEmployee $record): string => 'Total akan disamakan dengan Σ subtotal produk + ongkir '
                            . '(Rp ' . number_format(SalesTotalPrice::expectedTotal($record), 0, ',', '.') . '). '
                            . 'Total tersimpan saat ini: Rp ' . number_format((int) ($record->total_price ?? 0), 0, ',', '.') . '.')
                        ->action(function (SalesOrderEmployee $record): void {
                            $total = SalesTotalPrice::recalculate($record);

                            Notification::make()
                                ->title('Total price diperbarui')
                                ->body('Total baru: Rp ' . number_format($total, 0, ',', '.'))
                                ->success()
                                ->send();
                        }),
                    \Filament\Actions\DeleteAction::make()
                        ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin'])),
                    \Filament\Actions\RestoreAction::make()
                        ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin'])),
                    \Filament\Actions\ForceDeleteAction::make()
                        ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin'])),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin'])),
                    \Filament\Actions\RestoreBulkAction::make()
                        ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin'])),
                    \Filament\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin'])),
                    BulkAction::make('recalculateTotalPrice')
                        ->label('Hitung Ulang Total')
                        ->icon('heroicon-m-arrow-path')
                        ->color('warning')
                        ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin']))
                        ->requiresConfirmation()
                        ->modalHeading('Hitung ulang total price')
                        ->modalDescription('Total semua order terpilih akan disamakan dengan Σ subtotal produk + ongkir.')
                        ->action(function (Collection $records): void {
                            foreach ($records as $record) {
                                SalesTotalPrice::recalculate($record);
                            }

                            Notification::make()
                                ->title('Total price diperbarui')
                                ->body(number_format($records->count()) . ' order disamakan dengan rumus subtotal produk + ongkir.')
                                ->success()
                                ->send();
                        }),
                ]),
            ])
            ->defaultSort('delivery_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    // public static function getWidgets(): array
    // {
    //     return [
    //         SalesOrderEmployeesStat::class,
    //     ];
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrderEmployees::route('/'),
            'create' => Pages\CreateSalesOrderEmployees::route('/create'),
            // 'view' => Pages\ViewSalesOrderEmployees::route('/{record}'),
            'edit' => Pages\EditSalesOrderEmployees::route('/{record}/edit'),
        ];
    }

    public static function getDetailsFormHeadSchema(): array
    {
        return [
            ImageInput::make('image_payment')
                ->label('Transfer')

                ->directory('images/Employee'),

            StoreSelect::make('store_id'),

            DateInput::make('delivery_date'),

            Select::make('delivery_address_id')
                ->label('Delivery Address')
                ->inlineLabel()
                ->required()
                ->relationship(
                    name: 'deliveryAddress',
                    modifyQueryUsing: fn (Builder $query) =>
                        $query->where('user_id', Auth::id())
                )
                ->getOptionLabelFromRecordUsing(fn (DeliveryAddress $record) => "{$record->delivery_address_name}")
                ->searchable()
                ->preload()
                ->createOptionForm(
                    DeliveryAddressForm::schema()
                ),

            Select::make('transfer_to_account_id')
                ->label('Transfer To Account')
                ->inlineLabel()
                ->required()
                ->relationship('transferToAccount', 'name')
                ->options(TransferToAccount::where('status', 1)
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->id => $item->transfer_name];
                    })),

            Select::make('payment_status')
                ->required()
                ->inlineLabel()
                ->options([
                    '1' => 'Belum Diperiksa',
                    '2' => 'Valid / Sudah Dibayar',
                    '3' => 'Tidak Valid',
                    '4' => 'Menunggu Pembayaran',
                ])
                ->visible(fn ($record) => auth()->user()->hasRole('admin')),
        ];
    }
}
