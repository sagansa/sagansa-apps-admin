<?php

namespace App\Filament\Resources\Panel;

use App\Enum\PaymentType;
use App\Filament\Clusters\Purchases;
use App\Filament\Filters\SelectPaymentTypeFilter;
use App\Filament\Forms\CurrencyInput;
use App\Filament\Forms\CurrencyRepeaterInput;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\Notes;
use App\Filament\Forms\StoreSelect;
use App\Filament\Forms\SupplierSelect;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\InvoicePurchase;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\InvoicePurchaseResource\Pages;
use App\Filament\Resources\Panel\PaymentReceiptResource;
use App\Filament\Tables\InvoicePurchaseTable;
use App\Models\DetailRequest;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\ActionGroup;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class InvoicePurchaseResource extends Resource
{
    protected static ?string $model = InvoicePurchase::class;

    protected static ?int $navigationSort = 30;


    protected static ?string $pluralLabel = 'Invoices';

    protected static ?string $cluster = Purchases::class;

    public static function getModelLabel(): string
    {
        return __('crud.invoicePurchases.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.invoicePurchases.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.invoicePurchases.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([

            Group::make()
                ->schema([
                    Section::make()
                        ->schema(static::getDetailsFormHeadSchema())
                        ->columns(2),

                    Section::make()
                        ->schema([static::getItemsRepeater()]),
                ])
                ->columnSpan(['lg' => 2]),

            Section::make()
                ->schema(static::getDetailsFormBottomSchema())
                ->columnSpan(['lg' => 1]),
        ])
        ->columns(3);
    }

    public static function table(Table $table): Table
    {
        $invoicePurchases = InvoicePurchase::query();

        if (!Auth::user()->hasAnyRole(['admin', 'super_admin'])) {
            $invoicePurchases->where('created_by_id', Auth::id());
        }

        return $table
            ->query($invoicePurchases->with(['paymentReceipts']))
            ->poll('60s')
            ->stackedOnMobile()
            ->columns(
                InvoicePurchaseTable::schema()
            )
            ->filters([
                SelectPaymentTypeFilter::make('payment_type_id'),

                SelectFilter::make('store_id')
                    ->label('Store')
                    ->searchable()
                    ->relationship('store', 'nickname'),

                SelectFilter::make('supplier_id')
                    ->label('Supplier')
                    ->searchable()
                    ->relationship('supplier', 'name'),

                SelectFilter::make('created_by_id')
                    ->label('Pembuat')
                    ->relationship('createdBy', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin'])),

                \Filament\Tables\Filters\TernaryFilter::make('is_empty')
                    ->label('Status Detail')
                    ->placeholder('Semua')
                    ->trueLabel('Invoice Kosong')
                    ->falseLabel('Ada Detail Item')
                    ->queries(
                        true: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDoesntHave('detailInvoices'),
                        false: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereHas('detailInvoices'),
                    )
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                    \Filament\Actions\Action::make('createPaymentReceipt')
                        ->label('Payment Receipt')
                        ->icon('heroicon-o-banknotes')
                        ->visible(fn (InvoicePurchase $record): bool =>
                            $record->payment_status === '1'
                            && $record->payment_type_id === PaymentType::Transfer->value
                            && $record->paymentReceipts()->doesntExist()
                        )
                        ->url(fn (InvoicePurchase $record): string =>
                            PaymentReceiptResource::getUrl('create', [
                                'invoice_id' => $record->id,
                            ])
                        ),
                    \Filament\Actions\Action::make('updateInvoiceStatus')
                        ->label('Ubah Status')
                        ->icon('heroicon-o-pencil-square')
                        ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin']) || Auth::user()->hasRole('staff'))
                        ->fillForm(fn (InvoicePurchase $record): array => [
                            'payment_status' => (string) $record->payment_status,
                            'order_status' => (string) $record->order_status,
                        ])
                        ->form(function () {
                            $fields = [];

                            if (Auth::user()->hasAnyRole(['admin', 'super_admin'])) {
                                $fields[] = Select::make('payment_status')
                                    ->label('Payment Status')
                                    ->required()
                                    ->options(static::getPaymentStatusOptions());
                            }

                            $fields[] = Select::make('order_status')
                                ->label('Order Status')
                                ->required()
                                ->options(static::getOrderStatusOptions());

                            return $fields;
                        })
                        ->action(function (InvoicePurchase $record, array $data): void {
                            $updateData = [];

                            if (Auth::user()->hasAnyRole(['admin', 'super_admin'])) {
                                $updateData['payment_status'] = $data['payment_status'];
                            }

                            $updateData['order_status'] = $data['order_status'];

                            $record->update($updateData);
                        }),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('setPaymentStatusToOne')
                        ->label('Set Payment Status to Belum Dibayar')
                        ->icon('heroicon-o-check')
                        ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin']))
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            InvoicePurchase::whereIn('id', $records->pluck('id'))->update(['payment_status' => 1]);
                        })
                        ->color('warning'),
                    \Filament\Actions\BulkAction::make('setOrderStatusToOne')
                        ->label('Set Order Status to Belum Diterima')
                        ->icon('heroicon-o-check')
                        ->visible(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin']))
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            InvoicePurchase::whereIn('id', $records->pluck('id'))->update(['order_status' => 1]);
                        })
                        ->color('warning'),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListInvoicePurchases::route('/'),
            'create' => Pages\CreateInvoicePurchase::route('/create'),
            'view' => Pages\ViewInvoicePurchase::route('/{record}'),
            'edit' => Pages\EditInvoicePurchase::route('/{record}/edit'),
        ];
    }

    public static function getDetailsFormHeadSchema(): array
    {


        return [
            ImageInput::make('image')
                ->directory('images/InvoicePurchase'),

            SupplierSelect::make('supplier_id'),

            StoreSelect::make('store_id'),

            Select::make('payment_type_id')
                ->required()
                ->reactive()
                ->relationship(
                    name: 'paymentType',
                    titleAttribute: 'name',
                    modifyQueryUsing: fn (Builder $query) => $query->where('status', '1'),
                )
                ->default(PaymentType::Cash->value)
                ->inlineLabel()
                ->preload(),
                // ->afterStateUpdated(function (Set $set) {
                    // $set('detailInvoices', null);
                // }),

            DateInput::make('date'),

            Select::make('payment_status')
                ->required(fn () => Auth::user()->hasAnyRole(['admin', 'super_admin']))
                ->disabled(fn () => !Auth::user()->hasAnyRole(['admin', 'super_admin']))
                ->hidden(fn ($operation) => $operation === 'create')
                ->preload()
                ->inlineLabel()
                ->options(static::getPaymentStatusOptions())
                ,

            Select::make('order_status')
                ->required()
                ->hidden(fn ($operation) => $operation === 'create')
                ->preload()
                ->inlineLabel()
                ->options(static::getOrderStatusOptions())
                ,
        ];
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('detailInvoices')
            ->hiddenLabel()
            ->minItems(1)
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, InvoicePurchase $record): array {
                $data['status'] = '3';

                return $data;
            })
            ->columns(['md' => 8])
            ->relationship()
            ->schema([
                Select::make('detail_request_id')
                    // ->label('Detail Order')
                    ->hiddenLabel()
                    ->placeholder('product')
                    ->relationship(
                        name: 'detailRequest',
                        modifyQueryUsing: function (Builder $query, callable $get) {
                            $paymentTypeId = $get('../../payment_type_id');
                            $storeId = $get('../../store_id');

                            $paymentTypeFilter = null;
                                if ($paymentTypeId == '2') {
                                    $paymentTypeFilter = '2';
                                }

                                    return $query
                                        ->where('store_id', $storeId)
                                        ->where('status', '4')
                                        ->when(filled($paymentTypeId), fn ($q) => $q->where('payment_type_id', $paymentTypeId))
                                        ->orderBy('id', 'desc');
                        }
                    )
                    ->getOptionLabelFromRecordUsing(fn (DetailRequest $record) => "{$record->detail_request_name}")

                    ->required()
                    ->preload()
                    ->searchable()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->columnSpan(['md' => 4]),

                TextInput::make('quantity_product')
                    ->hiddenLabel()
                    ->placeholder('quantity')
                    ->required()
                    ->live(debounce: 500)
                    ->minValue(1)
                    ->default(1)
                    ->suffix(function (Get $get) {
                        $detailRequest = DetailRequest::find($get('detail_request_id'));
                        return $detailRequest ? $detailRequest->product->unit->unit : '';
                    })
                    ->columnSpan(['md' => 2]),

                CurrencyRepeaterInput::make('subtotal_invoice')
                    ->placeholder('subtotal')
                    ->live(debounce: 500)
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateTotalPriceFromItem($get, $set);
                    })
                    ->columnSpan(['md' => 2]),
            ])
            ->live()
            ->afterStateUpdated(function (Get $get, Set $set) {
                self::updateTotalPrice($get, $set);
            });
    }

    public static function getDetailsFormBottomSchema(): array
    {
        return [
            CurrencyInput::make('taxes')
                ->live(debounce: 500)
                ->inlineLabel()
                ->afterStateUpdated(function (Get $get, Set $set) {
                    self::updateTotalPrice($get, $set);
                }),

            CurrencyInput::make('discounts')
                ->live(debounce: 500)
                ->inlineLabel()
                ->afterStateUpdated(function (Get $get, Set $set) {
                    self::updateTotalPrice($get, $set);
                }),

            CurrencyInput::make('total_price')
                ->inlineLabel()
                ->readOnly(),

            Notes::make('notes'),
        ];
    }

    protected static function getPaymentStatusOptions(): array
    {
        return [
            '1' => 'belum dibayar',
            '2' => 'sudah dibayar',
            '3' => 'tidak valid',
        ];
    }

    protected static function getOrderStatusOptions(): array
    {
        return [
            '1' => 'belum diterima',
            '2' => 'sudah diterima',
            '3' => 'dikembalikan',
        ];
    }

    public static function parseCurrency(mixed $val): int
    {
        if ($val === null || $val === '') {
            return 0;
        }
        if (is_int($val)) {
            return $val;
        }
        if (is_float($val)) {
            return (int) round($val);
        }
        $digits = preg_replace('/[^\d]/', '', (string) $val);
        return $digits !== '' ? (int) $digits : 0;
    }

    protected static function updateTotalPriceFromItem(Get $get, Set $set): void
    {
        $repeaterItems = $get('../../detailInvoices') ?? [];
        $taxes = static::parseCurrency($get('../../taxes'));
        $discounts = static::parseCurrency($get('../../discounts'));

        $subtotalPrice = 0;
        if (is_array($repeaterItems)) {
            foreach ($repeaterItems as $item) {
                $subtotalPrice += static::parseCurrency($item['subtotal_invoice'] ?? 0);
            }
        }

        $currentSubtotal = static::parseCurrency($get('subtotal_invoice'));
        if ($subtotalPrice < $currentSubtotal) {
            $subtotalPrice = $currentSubtotal;
        }

        $totalPrice = $subtotalPrice + $taxes - $discounts;

        $set('../../subtotal_price', $subtotalPrice);
        $set('../../total_price', $totalPrice);
    }

    protected static function updateTotalPrice(Get $get, Set $set): void
    {
        $repeaterItems = $get('detailInvoices') ?? [];

        $subtotalPrice = 0;
        $taxes = static::parseCurrency($get('taxes'));
        $discounts = static::parseCurrency($get('discounts'));

        if (is_array($repeaterItems)) {
            foreach ($repeaterItems as $item) {
                $subtotalPrice += static::parseCurrency($item['subtotal_invoice'] ?? 0);
            }
        }

        $totalPrice = $subtotalPrice + $taxes - $discounts;

        $set('subtotal_price', $subtotalPrice);
        $set('total_price', $totalPrice);
    }
}
