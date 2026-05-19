<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Sales;
use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\DeliveryAddressColumn;
use App\Filament\Columns\DeliveryStatusColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Columns\PaymentStatusColumn;
use App\Filament\Filters\SelectStoreFilter;
use Filament\Tables\Filters\SelectFilter;
use App\Filament\Forms\BottomTotalPriceForm;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\DeliveryAddressForm;
use App\Filament\Forms\ImageInput;
use App\Filament\Resources\Panel\SalesOrderDirectsResource\Pages;
use App\Models\SalesOrderDirect;
use Filament\Schemas\Components\Group;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Auth;
use App\Filament\Forms\SalesProductForm;
use App\Filament\Forms\StoreSelect;
use App\Models\DeliveryAddress;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Components\ImageEntry;
use Filament\Schemas\Components\Section as InfoSection;
use Filament\Schemas\Components\Grid as InfoGrid;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Support\Enums\FontWeight;
use App\Models\TransferToAccount;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\Placeholder;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Support\HtmlString;
use Filament\Actions\DeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Actions\ForceDeleteAction;

class SalesOrderDirectsResource extends Resource
{
    protected static ?string $model = SalesOrderDirect::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Sales';

    protected static ?string $pluralLabel = 'Order';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Sales::class;

    public static function form(Schema $form): Schema
    {
        return $form->schema([

            Group::make()
                ->schema([
                    Section::make()
                        ->schema(static::getDetailsFormHeadSchema())
                        ->columns(2),

                    Section::make('Detail Order')->schema([
                        SalesProductForm::getItemsRepeater()
                        ]),
            ])
            ->columnSpan(['lg' => 2]),
            // ->disabled(fn (SalesOrderDirect $record) => $record->payment_status === 2),

            Section::make()
                 ->schema(BottomTotalPriceForm::schema())
                 ->columnSpan(['lg' => 1]),
        ])
        ->columns(3);
        // ->disabled(fn (?SalesOrderDirect $record) => $record !== null && $record->payment_status == 2 && $record->delivery_status == 2);
    }

    public static function table(Table $table): Table
    {
        $query = SalesOrderDirect::query();

        if (Auth::user()->hasRole('customer')) {
            $query->where('ordered_by_id', Auth::id());
        } elseif (Auth::user()->hasRole('storage-staff')) {
            $query->where('payment_status', 2);
        }

        $query->where('for', 1);

        return $table
            ->query($query)
            ->columns([

                TextColumn::make('image_payment')
                    ->label('Payment')
                    ->formatStateUsing(fn ($state) => $state ? 'Lihat' : '-')
                    ->icon(fn ($state) => $state ? 'heroicon-o-photo' : null)
                    ->color('info')
                    ->url(fn($record) => $record->image_payment ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->image_payment) : null)
                    ->openUrlInNewTab()
                    ->visible(fn () => Auth::user()->hasRole('admin') || Auth::user()->hasRole('customer')),

                TextColumn::make('image_delivery')
                    ->label('delivery')
                    ->formatStateUsing(fn ($state) => $state ? 'Lihat' : '-')
                    ->icon(fn ($state) => $state ? 'heroicon-o-photo' : null)
                    ->color('info')
                    ->url(fn($record) => $record->image_delivery ? \Illuminate\Support\Facades\Storage::disk('public')->url($record->image_delivery) : null)
                    ->openUrlInNewTab(),

                TextColumn::make('orderedBy.name')
                    ->searchable()
                    ->visible(fn () => Auth::user()->hasRole('admin') || Auth::user()->hasRole('storage-staff')),

                TextColumn::make('store.nickname')
                    ->hidden(fn () => Auth::user()->hasRole('customer')),

                TextColumn::make('delivery_date')
                    ->label('Date'),

                TextColumn::make('deliveryService.name'),

                DeliveryAddressColumn::make('deliveryAddress'),

                TextColumn::make('receipt_no')->searchable(),

                TextColumn::make('orders_list')
                    ->label('Orders')
                    ->html()
                    ->state(function (SalesOrderDirect $record) {
                        return implode('<br>', $record->detailSalesOrders->map(function ($item) {
                            $productName = $item->product?->name ?? 'Unknown Product';
                            $unit = $item->product?->unit?->unit ?? '';
                            return "{$productName} ({$item->quantity} {$unit})";
                        })->toArray());
                    })
                    ->extraAttributes(['class' => 'whitespace-pre-wrap']),

                TextColumn::make('transferToAccount.transfer_account_name')
                    ->hidden(fn () => Auth::user()->hasRole('storage-staff')),

                PaymentStatusColumn::make('payment_status')
                    ->hidden(fn () => Auth::user()->hasRole('storage-staff')),

                DeliveryStatusColumn::make('delivery_status'),
                
                TextColumn::make('payment_method')
                    ->label('Payment Method')
                    ->badge()
                    ->color(fn (string $state): string => match (true) {
                        str_contains($state, 'midtrans') => 'success',
                        str_contains($state, 'manual') => 'info',
                        default => 'gray',
                    })
                    ->toggleable(),

                TextColumn::make('midtrans_status')
                    ->label('Midtrans Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'settlement'  => 'success',
                        'capture'     => 'success',
                        'pending'     => 'warning',
                        'deny'        => 'danger',
                        'cancel'      => 'danger',
                        'expire'      => 'gray',
                        default       => 'gray',
                    })
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('status')
                    ->label('Order Status')
                    ->badge()
                    ->color(fn (?string $state): string => match ($state) {
                        'paid'      => 'success',
                        'pending'   => 'warning',
                        'cancelled' => 'danger',
                        default     => 'gray',
                    })
                    ->placeholder('-')
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('admin_fee')
                    ->label('Admin Fee')
                    ->money('IDR')
                    ->toggleable(),

                TextColumn::make('shipping_cost')
                    ->label('Shipping Cost')
                    ->formatStateUsing(fn (SalesOrderDirect $record) => 'Rp ' . number_format($record->shipping_cost, 0, ',', '.'))
                    ->summarize(Sum::make()
                        ->numeric(
                            thousandsSeparator: '.'
                        )
                        ->label('')
                        ->prefix('Rp '))
                    ->toggleable(isToggledHiddenByDefault: false),

                CurrencyColumn::make('total_price')
                    ->visible(fn ($record) => auth()->user()->hasRole('admin') || auth()->user()->hasRole('customer'))
                    ->summarize(Sum::make()
                        ->numeric(
                            thousandsSeparator: '.'
                        )
                        ->label('')
                        ->prefix('Rp ')),

                TextColumn::make('received_by')
                    ->toggleable(isToggledHiddenByDefault: false),

                TextColumn::make('assignedBy.name')
                    ->visible(fn () => Auth::user()->hasRole('admin'))
                    ->toggleable(isToggledHiddenByDefault: false),

            ])
            ->filters([
                SelectStoreFilter::make('store_id'),
                // DateFilter::make('delivery_date'),
                SelectFilter::make('transfer_to_account_id'),
                    // ->relationship('transferToAccount', 'transfer_account_name'),
                Tables\Filters\TrashedFilter::make(),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                    Action::make('Update Payment Status To Valid')
                        ->visible(fn ($record) => Auth::user()->hasRole('admin') && $record->payment_status != 2 && $record->deleted_at === null)
                        ->icon('heroicon-o-pencil-square')
                        ->action(function ($record) {
                            $record->update(['payment_status' => 2]);
                        })
                        ->requiresConfirmation(),
                    DeleteAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                    RestoreAction::make()
                        ->visible(fn () => Auth::user()->hasRole('admin')),
                    ForceDeleteAction::make()
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
            ->defaultSort('delivery_date', 'desc');;
    }

    public static function getRelations(): array
    {
        return [
            // ProductsRelationManager::class,
        ];
    }

    // public static function getWidgets(): array
    // {
    //     return [
    //         SalesOrderDirectsStat::class,
    //     ];
    // }

    public static function infolist(Schema $infolist): Schema
    {
        return $infolist
            ->schema([
                InfoSection::make('Order Information')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('orderedBy.name')
                                    ->label('Ordered By')
                                    ->weight(FontWeight::Bold),
                                TextEntry::make('store.nickname')
                                    ->label('Store'),
                                TextEntry::make('delivery_date')
                                    ->label('Delivery Date')
                                    ->date(),
                                TextEntry::make('deliveryService.name')
                                    ->label('Delivery Service'),
                                TextEntry::make('deliveryAddress.delivery_address_name')
                                    ->label('Delivery Address'),
                                TextEntry::make('receipt_no')
                                    ->label('Receipt No'),
                            ]),
                    ]),

                InfoSection::make('Status & Payment')
                    ->schema([
                        InfoGrid::make(3)
                            ->schema([
                                TextEntry::make('payment_status')
                                    ->label('Payment Status')
                                    ->formatStateUsing(fn (string $state): string => match ($state) {
                                        '1' => 'Belum diperiksa',
                                        '2' => 'Valid / Sudah Dibayar',
                                        '4' => 'Menunggu Pembayaran',
                                        default => 'Unknown',
                                    })
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        '1' => 'warning',
                                        '2' => 'success',
                                        '4' => 'info',
                                        default => 'gray',
                                    }),
                                TextEntry::make('delivery_status')
                                    ->label('Delivery Status')
                                    ->formatStateUsing(fn (int $state): string => match ($state) {
                                        1 => 'Belum dikirim',
                                        3 => 'Sudah dikirim',
                                        4 => 'Siap dikirim',
                                        5 => 'Perbaiki',
                                        6 => 'Dikembalikan',
                                        default => 'Unknown',
                                    })
                                    ->badge()
                                    ->color(fn (int $state): string => match ($state) {
                                        1 => 'warning',
                                        3 => 'success',
                                        4 => 'info',
                                        5 => 'danger',
                                        6 => 'secondary',
                                        default => 'gray',
                                    }),
                                TextEntry::make('payment_method')
                                    ->label('Payment Method')
                                    ->badge()
                                    ->color(fn (?string $state): string => match (true) {
                                        str_contains($state ?? '', 'midtrans') => 'success',
                                        str_contains($state ?? '', 'manual')   => 'info',
                                        default => 'gray',
                                    }),
                                TextEntry::make('midtrans_status')
                                    ->label('Midtrans Status')
                                    ->badge()
                                    ->placeholder('-')
                                    ->color(fn (?string $state): string => match ($state) {
                                        'settlement', 'capture' => 'success',
                                        'pending'               => 'warning',
                                        'deny', 'cancel'        => 'danger',
                                        'expire'                => 'gray',
                                        default                 => 'gray',
                                    }),
                                TextEntry::make('status')
                                    ->label('Order Status')
                                    ->badge()
                                    ->placeholder('-')
                                    ->color(fn (?string $state): string => match ($state) {
                                        'paid'      => 'success',
                                        'pending'   => 'warning',
                                        'cancelled' => 'danger',
                                        default     => 'gray',
                                    }),
                                TextEntry::make('admin_fee')
                                    ->label('Admin Fee')
                                    ->money('IDR'),
                                TextEntry::make('total_price')
                                    ->label('Total Price')
                                    ->money('IDR')
                                    ->weight(FontWeight::Bold),
                            ]),
                    ]),

                InfoSection::make('Order Details')
                    ->schema([
                        RepeatableEntry::make('detailSalesOrders')
                            ->schema([
                                TextEntry::make('product.name')
                                    ->label('Product'),
                                TextEntry::make('quantity')
                                    ->label('Quantity')
                                    ->formatStateUsing(fn ($state, $record) => "{$state} " . ($record->product?->unit?->unit ?? '')),
                                TextEntry::make('unit_price')
                                    ->label('Unit Price')
                                    ->money('IDR'),
                                TextEntry::make('total_price')
                                    ->label('Total Price')
                                    ->state(fn ($record): float => $record->quantity * $record->unit_price)
                                    ->money('IDR'),
                            ])
                            ->columns(4),
                    ]),

                InfoSection::make('Images')
                    ->schema([
                        InfoGrid::make(2)
                            ->schema([
                                ImageEntry::make('image_payment')
                                    ->label('Payment Proof')
                                    ->disk('public'),
                                ImageEntry::make('image_delivery')
                                    ->label('Delivery Proof')
                                    ->disk('public'),
                            ]),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalesOrderDirects::route('/'),
            'create' => Pages\CreateSalesOrderDirects::route('/create'),
            'view' => Pages\ViewSalesOrderDirects::route('/{record}'),
            'edit' => Pages\EditSalesOrderDirects::route('/{record}/edit'),
        ];
    }

    public static function getDetailsFormHeadSchema(): array
    {
        $options = [
            '1' => 'belum dikirim',
            '3' => 'sudah dikirim',
            '4' => 'siap dikirim',
            '5' => 'perbaiki',
            '6' => 'dikembalikan'
        ];

        if (Auth::user()->hasRole('admin')) {
            $options['2'] = 'valid';
        }

        return [
            Group::make()
                ->schema([
                    ImageInput::make('image_payment')
                        ->label('Payment')
                        ->disk('public')
                        ->openable()
                        ->downloadable()
                        ->imageEditor(false)
                        
                        ->visible(fn (?SalesOrderDirect $record) => 
                            $record === null || // Always visible during Create
                            Auth::user()->hasRole('admin') || 
                            Auth::user()->hasRole('storage-staff') ||
                            ($record && Auth::user()->hasRole('customer') && $record->payment_status == 2)
                        )
                        ->required()
                        ->directory('images/Direct/Payment'),

                    Placeholder::make('image_payment_preview')
                        ->label('Payment')
                        ->visible(fn (?SalesOrderDirect $record) =>
                            Auth::user()->hasRole('admin') ||
                            Auth::user()->hasRole('storage-staff') ||
                            ($record && Auth::user()->hasRole('customer') && $record->payment_status == 2)
                        )
                        ->content(function ($record) {
                            if (!$record || !$record->image_payment) return '-';
                            $url = \Illuminate\Support\Facades\Storage::disk('public')->url($record->image_payment);
                            return new HtmlString("<a href='{$url}' target='_blank'><img src='{$url}' style='max-width: 100%; height: auto; border-radius: 0.5rem; border: 1px solid #e5e7eb;' /></a>");
                        }),
                ]),

            Group::make()
                ->schema([
                    ImageInput::make('image_delivery')
                        ->disk('public')
                        ->openable()
                        ->downloadable()
                        ->imageEditor(false)
                        
                        ->hidden(fn (?SalesOrderDirect $record) =>
                            Auth::user()->hasRole('customer') ||
                            Auth::user()->hasRole('admin')
                        )
                        ->label('Delivered')
                        ->directory('images/Direct/Delivery'),

                    Placeholder::make('image_delivery_preview')
                        ->label('Delivered')
                        ->visible(fn (?SalesOrderDirect $record) =>
                            Auth::user()->hasRole('admin') && !Auth::user()->hasRole('customer')
                        )
                        ->content(function ($record) {
                            if (!$record || !$record->image_delivery) return '-';
                            $url = \Illuminate\Support\Facades\Storage::disk('public')->url($record->image_delivery);
                            return new HtmlString("<a href='{$url}' target='_blank'><img src='{$url}' style='max-width: 100%; height: auto; border-radius: 0.5rem; border: 1px solid #e5e7eb;' /></a>");
                        }),
                ]),

            StoreSelect::make('store_id')
                ->required(fn () => Auth::user()->hasRole('admin'))
                ->hidden(fn () => Auth::user()->hasRole('customer'))
                ->disabled(fn () => Auth::user()->hasRole('storage-staff')),

            DateInput::make('delivery_date')
                ->label('Delivery Date')
                ->disabled(fn (?SalesOrderDirect $record) => Auth::user()->hasRole('customer') && $record?->payment_status == 2 || Auth::user()->hasRole('storage-staff')),

            Select::make('delivery_service_id')
                ->required()
                ->inlineLabel()
                ->label('Delivery Service')
                ->disabled(fn (?SalesOrderDirect $record) => Auth::user()->hasRole('customer') && $record?->payment_status == 2 || Auth::user()->hasRole('storage-staff'))
                ->relationship('deliveryService', 'name')
                ->searchable()
                ->preload(),

            Select::make('delivery_address_id')
                ->label('Delivery Address')
                ->inlineLabel()
                ->required(fn (?SalesOrderDirect $record) => $record?->delivery_service_id != 33)
                ->relationship(
                    name: 'deliveryAddress',
                    modifyQueryUsing: function (Builder $query) {
                        if (Auth::user()->hasRole('admin')) {
                            $query->get();
                        } elseif (Auth::user()->hasRole('storage-staff')) {
                            $query->get();
                        } elseif (Auth::user()->hasRole('customer')) {
                            $query->where('user_id', Auth::id());
                        }
                    }
                )
                ->getOptionLabelFromRecordUsing(fn (DeliveryAddress $record) => "{$record->delivery_address_name}")
                ->searchable()
                ->hidden(fn (?SalesOrderDirect $record) => !Auth::user()->hasRole('customer') || $record?->delivery_service_id == 33)
                ->disabled(fn (?SalesOrderDirect $record) =>
                    Auth::user()->hasRole('customer') && $record?->payment_status == 2)
                ->preload()
                ->createOptionForm(
                    DeliveryAddressForm::schema()
                ),

            Select::make('transfer_to_account_id')
                ->required()
                ->inlineLabel()
                ->label('Transfer To Account')
                ->hidden(fn () => Auth::user()->hasRole('storage-staff'))
                ->disabled(fn (?SalesOrderDirect $record) => Auth::user()->hasRole('customer') && $record?->payment_status == 1)
                ->relationship('transferToAccount', 'name')
                ->options(TransferToAccount::where('status', 1)
                    ->get()
                    ->mapWithKeys(function ($item) {
                        return [$item->id => $item->transfer_name];
                    })),

            TextInput::make('receipt_no')
                ->disabled(fn () => Auth::user()->hasRole('customer') || Auth::user()->hasRole('admin'))
                ->inlineLabel()
                ->hidden(fn ($operation, ?SalesOrderDirect $record) => $operation === 'create' && $record?->delivery_status == 3)
                ->required(fn (?SalesOrderDirect $record) => Auth::user()->hasRole('storage-staff') && $record?->delivery_status == 3),

            Select::make('payment_status')
                ->required(fn () => Auth::user()->hasRole('admin'))
                ->hidden(fn ($operation) => $operation === 'create' || Auth::user()->hasRole('storage-staff'))
                ->disabled(fn () => Auth::user()->hasRole('customer'))
                ->reactive()
                ->inlineLabel()
                ->options([
                    '1' => 'Belum diperiksa',
                    '2' => 'Valid / Sudah Dibayar',
                    '4' => 'Menunggu Pembayaran (Midtrans)',
                ]),

            Select::make('delivery_status')
                ->hidden(fn ($operation) => $operation === 'create' || !Auth::user()->hasRole('storage-staff'))
                ->required()
                ->inlineLabel()
                ->options([
                    '1' => 'belum dikirim',
                    '3' => 'sudah dikirim',
                    '4' => 'siap dikirim',
                    '5' => 'perbaiki',
                    '6' => 'dikembalikan'
                ]),

            TextInput::make('received_by')
                ->hidden(fn ($operation) => $operation === 'create')
                ->inlineLabel()
                ->disabled(fn () => Auth::user()->hasRole('customer')),

            Placeholder::make('delivery_status')
                ->hidden(fn ($operation) => $operation === 'create' || Auth::user()->hasRole('storage-staff'))
                ->label('Delivery Status')
                ->inlineLabel()
                ->content(fn (SalesOrderDirect $record): HtmlString => new HtmlString(match ($record->delivery_status) {
                    1 => '<span class="badge badge-warning">belum dikirim</span>',
                    3 => '<span class="badge badge-success">sudah dikirim</span>',
                    4 => '<span class="badge badge-info">siap dikirim</span>',
                    5 => '<span class="badge badge-danger">perbaiki</span>',
                    6 => '<span class="badge badge-secondary">dikembalikan</span>',
                    // default => '<span class="badge badge-dark">unknown</span>',
                })),

            Placeholder::make('delivery_address')
                ->hidden(fn ($operation) => $operation === 'create' || Auth::user()->hasRole('customer'))
                ->content(fn (SalesOrderDirect $record): ?string => $record->deliveryAddress?->delivery_address_name ?? '-'),

            Select::make('ordered_by_id')
                ->label('Ordered By (Customer)')
                ->relationship('orderedBy', 'name')
                ->searchable()
                ->preload()
                ->visible(fn () => Auth::user()->hasRole('super_admin'))
                ->required()
                ->default(fn () => Auth::id()),

        ];
    }
}
