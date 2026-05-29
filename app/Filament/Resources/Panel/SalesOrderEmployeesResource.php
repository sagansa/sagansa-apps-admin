<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Sales;
use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\DeliveryAddressColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Columns\PaymentStatusColumn;
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

class SalesOrderEmployeesResource extends Resource
{
    protected static ?string $model = SalesOrderEmployee::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

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
        ->disabled(fn (?SalesOrderEmployee $record) => $record !== null && $record->payment_status == 2);
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

                CurrencyColumn::make('total_price')
                    ->label('Total Price')
                    ->summarize(Sum::make()
                        ->numeric(
                            thousandsSeparator: '.'
                        )
                        ->label('')
                        ->prefix('Rp ')),

                PaymentStatusColumn::make('payment_status')
                    ->label('Payment Status'),

                TextColumn::make('orderedBy.name')
                    ->label('Sales')
                    ->visible(fn ($record) => auth()->user()->hasRole('admin')),
            ])
            ->filters([
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\DeleteAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                    \Filament\Actions\RestoreAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                    \Filament\Actions\ForceDeleteAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                    \Filament\Actions\RestoreBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                    \Filament\Actions\ForceDeleteBulkAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
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
