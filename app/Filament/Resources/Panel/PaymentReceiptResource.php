<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Purchases;
use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Columns\SupplierColumn;
use App\Filament\Forms\CurrencyInput;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\Notes;
use Filament\Tables;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\PaymentReceipt;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Panel\PaymentReceiptResource\Pages;
use App\Filament\Resources\Panel\PaymentReceiptResource\RelationManagers;
use App\Models\DailySalary;
use App\Models\FuelService;
use App\Models\InvoicePurchase;
use App\Models\Supplier;
use App\Support\PublicStorageUrl;
use Filament\Forms\Components\Radio;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;
use Illuminate\Support\HtmlString;

class PaymentReceiptResource extends Resource
{
    protected static ?string $model = PaymentReceipt::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Purchases::class;

    // protected static string|\UnitEnum|null $navigationGroup = 'Purchase';

    public static function getModelLabel(): string
    {
        return __('crud.paymentReceipts.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.paymentReceipts.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.paymentReceipts.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([

                    Radio::make('payment_for')
                        ->disabled(fn($operation) => $operation === 'edit')
                        ->options([
                            '1' => 'fuel/service',
                            '2' => 'daily salary',
                            '3' => 'invoice',
                        ])
                        ->inline()
                        ->reactive()
                        ->afterStateUpdated(function (callable $set) {
                            $set('total_amount', 0);
                            $set('transfer_amount', 0);
                        }),

                    Select::make('fuel_service_created_by')
                        ->label('Created By')
                        ->visible(fn($get) => $get('payment_for') == '1')
                        ->hidden(fn($operation) => $operation === 'edit' || $operation === 'view')
                        ->options(function () {
                            // ID user yang menjadi created_by (baik numeric id maupun uuid)
                            // dari fuel/service transfer yang belum dibayar.
                            $createdByIds = FuelService::query()
                                ->where('payment_type_id', '1')
                                ->where('status', '1')
                                ->pluck('created_by_id')
                                ->filter()
                                ->unique()
                                ->all();

                            // created_by_id bisa berupa numeric id ATAU uuid user.
                            // Kumpulkan keduanya lalu resolve ke user.
                            $numericIds = array_filter($createdByIds, 'is_numeric');
                            $uuids = array_filter($createdByIds, fn($v) => !is_numeric($v));

                            $users = collect();
                            if (!empty($numericIds)) {
                                $users = $users->merge(\App\Models\User::whereIn('id', $numericIds)->get(['id', 'name']));
                            }
                            if (!empty($uuids)) {
                                $users = $users->merge(\App\Models\User::whereIn('uuid', $uuids)->get(['id', 'name']));
                            }

                            return $users->sortBy('name')->pluck('name', 'id');
                        })
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->dehydrated(false)
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) {
                                $set('fuelServices', []);
                                $set('total_amount', 0);
                                $set('transfer_amount', 0);

                                return;
                            }

                            $targetIds = [$state];
                            try {
                                $user = \App\Models\User::find($state);
                                if ($user && array_key_exists('uuid', $user->getAttributes()) && $user->uuid) {
                                    $targetIds[] = $user->uuid;
                                }
                            } catch (\Exception $e) {
                            }

                            $fuelServices = FuelService::query()
                                ->whereIn('created_by_id', $targetIds)
                                ->where('payment_type_id', '1')
                                ->where('status', '1')
                                ->orderBy('date', 'desc')
                                ->get(['id', 'amount']);

                            $fuelServiceIds = $fuelServices->pluck('id')->toArray();
                            $totalAmount = $fuelServices->sum('amount');

                            $set('fuelServices', $fuelServiceIds);
                            $set('total_amount', $totalAmount);
                            $set('transfer_amount', $totalAmount);
                        }),

                    Select::make('fuelServices')
                        ->visible(fn($get) => $get('payment_for') == '1')
                        ->required(fn($get) => $get('payment_for') == '1' && fn($operation) => $operation === 'create')
                        ->hidden(fn($operation) => $operation === 'edit' || $operation === 'view')
                        ->multiple()
                        ->relationship(
                            name: 'fuelServices',
                            modifyQueryUsing: fn(Builder $query, callable $get) => $query
                                ->with([
                                    'vehicle',
                                    'createdBy' => fn($q) => $q->withTrashed()
                                ])
                                ->where('payment_type_id', '1')
                                ->where('status', '1')
                                ->when(
                                    $get('fuel_service_created_by'),
                                    function ($q, $createdById) {
                                        $targetIds = [$createdById];
                                        try {
                                            $user = \App\Models\User::find($createdById);
                                            if ($user && array_key_exists('uuid', $user->getAttributes()) && $user->uuid) {
                                                $targetIds[] = $user->uuid;
                                            }
                                        } catch (\Exception $e) {
                                        }

                                        $q->whereIn('created_by_id', $targetIds);
                                    }
                                )
                                ->orderBy('date', 'desc')
                        )
                        ->getOptionLabelFromRecordUsing(fn(FuelService $record) => "{$record->fuel_service_name}")
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function (?array $state, callable $set) {
                            $fuelServiceIds = $state ?? [];

                            if (empty($fuelServiceIds)) {
                                $set('total_amount', 0);
                                $set('transfer_amount', 0);

                                return;
                            }

                            $totalAmount = FuelService::whereIn('id', $fuelServiceIds)->sum('amount');

                            $set('total_amount', $totalAmount);
                            $set('transfer_amount', $totalAmount);
                        }),

                    Select::make('user_id')
                        ->label('Employee')
                        ->visible(fn($get) => $get('payment_for') == '2')
                        ->required(fn($get) => $get('payment_for') == '2')
                        ->relationship('user', 'name', fn(Builder $query) => $query
                            ->whereHas('roles', fn(Builder $q) => $q->whereIn('name', ['staff', 'supervisor']))
                            ->orderBy('name', 'asc'))
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function ($state, callable $set) {
                            if (!$state) {
                                $set('dailySalaries', []);
                                $set('total_amount', 0);
                                $set('transfer_amount', 0);
                                return;
                            }

                            $targetIds = [$state];
                            try {
                                $user = \App\Models\User::find($state);
                                if ($user && array_key_exists('uuid', $user->getAttributes()) && $user->uuid) {
                                    $targetIds[] = $user->uuid;
                                }
                            } catch (\Exception $e) {
                            }

                            $salaries = \App\Models\DailySalary::whereIn('created_by_id', $targetIds)
                                ->where('payment_type_id', '1')
                                ->where('status', '3')
                                ->get(['id', 'amount']);

                            $salaryIds = $salaries->pluck('id')->toArray();
                            $totalAmount = $salaries->sum('amount');

                            $set('dailySalaries', $salaryIds);
                            $set('total_amount', $totalAmount);
                            $set('transfer_amount', $totalAmount);
                        }),

                    Select::make('dailySalaries')
                        ->visible(fn($get) => $get('payment_for') == '2')
                        ->required(fn($get) => $get('payment_for') == '2' && fn($operation) => $operation === 'create')
                        ->hidden(fn($operation) => $operation === 'edit' || $operation === 'view')
                        ->multiple()
                        ->relationship(
                            name: 'dailySalaries',
                            modifyQueryUsing: fn(Builder $query, callable $get) => $query
                                ->with([
                                    'createdBy' => fn($q) => $q->withTrashed(),
                                    'store'
                                ])
                                ->where('payment_type_id', '1')
                                ->where('status', '3')
                                ->when($get('user_id'), function ($q, $uid) {
                                    $targetIds = [$uid];
                                    try {
                                        $user = \App\Models\User::find($uid);
                                        if ($user && array_key_exists('uuid', $user->getAttributes()) && $user->uuid) {
                                            $targetIds[] = $user->uuid;
                                        }
                                    } catch (\Exception $e) {
                                    }

                                    $q->whereIn('created_by_id', $targetIds);
                                })
                                ->orderBy('date', 'desc')
                        )
                        ->getOptionLabelFromRecordUsing(fn(DailySalary $record) => "{$record->daily_salary_name}")
                        ->searchable()
                        ->preload()
                        ->reactive()
                        ->afterStateUpdated(function (?array $state, callable $set, callable $get) {
                            if (empty($state)) {
                                $set('total_amount', 0);
                                $set('transfer_amount', 0);

                                return;
                            }

                            $totalAmount = DailySalary::whereIn('id', $state)->sum('amount');
                            $set('total_amount', $totalAmount);
                            $set('transfer_amount', $totalAmount);
                        }),

                    Select::make('invoicePurchases')
                        ->visible(fn($get) => $get('payment_for') == '3')
                        ->required(fn($get) => $get('payment_for') == '3' && fn($operation) => $operation === 'create')
                        ->hidden(fn($operation) => $operation === 'edit' || $operation === 'view')
                        ->multiple()
                        ->relationship(
                            name: 'invoicePurchases',
                            modifyQueryUsing: fn(Builder $query) => $query
                                ->with([
                                    'supplier',
                                    'store'
                                ])
                                ->where('payment_type_id', '1')
                                ->where('payment_status', '1')
                                ->orderBy('date', 'desc')
                        )
                        // ->relationship(
                        //     name: 'invoicePurchases',
                        //     modifyQueryUsing: fn(Builder $query) => $query
                        //         ->where('payment_type_id', '1')
                        //         ->where(function ($query) {
                        //             $query->where('payment_status', '1')
                        //                 ->orWhere(function ($subQuery) {
                        //                     $subQuery->where('payment_status', '2')
                        //                         ->whereExists(function ($existsQuery) {
                        //                             $existsQuery->selectRaw(1)
                        //                                 ->from('invoice_purchase_payment_receipt')
                        //                                 ->whereColumn('invoice_purchase_payment_receipt.invoice_purchase_id', '=', 'invoice_purchases.id');
                        //                         });
                        //                 });
                        //         })
                        //         ->orderBy('date', 'desc')
                        // )
                        ->getOptionLabelFromRecordUsing(fn(InvoicePurchase $record) => "{$record->invoice_purchase_name}")
                        ->preload()
                        ->searchable()
                        ->reactive()
                        ->afterStateUpdated(function (?array $state, callable $set, callable $get) {
                            $invoiceIds = $state ?? [];

                            if (empty($invoiceIds)) {
                                $set('total_amount', 0);
                                $set('transfer_amount', 0);
                                $set('supplier_id', null);

                                return;
                            }

                            $totalAmount = InvoicePurchase::whereIn('id', $invoiceIds)->sum('total_price');
                            $set('total_amount', $totalAmount);

                            $invoices = InvoicePurchase::with('supplier')->whereIn('id', $invoiceIds)->get();
                            if ($invoices->isNotEmpty() && $invoices->pluck('supplier_id')->unique()->count() === 1) {
                                $set('supplier_id', $invoices->first()->supplier_id);
                            }

                            $set('transfer_amount', $totalAmount);
                        }),

                    Select::make('supplier_id')
                        ->label(__('crud.suppliers.itemTitle'))
                        ->visible(fn($get) => $get('payment_for') == '3')
                        ->required(fn($get) => $get('payment_for') == '3')
                        ->relationship(
                            name: 'supplier',
                            modifyQueryUsing: fn(Builder $query) => $query
                                ->where('status', '<>', '3')
                                ->orderBy('name', 'asc'),
                        )
                        ->getOptionLabelFromRecordUsing(fn(Supplier $record) => "{$record->supplier_name}")
                        ->searchable()
                        ->preload()
                        ->reactive(),

                    Placeholder::make('supplier_bank_information')
                        ->label('Rekening Tujuan')
                        ->visible(fn(callable $get) => filled($get('supplier_id')))
                        ->content(function (callable $get) {
                            $supplierId = $get('supplier_id');

                            if (!$supplierId) {
                                return new HtmlString('-');
                            }

                            $supplier = Supplier::with('bank')->find($supplierId);

                            if (!$supplier) {
                                return new HtmlString('-');
                            }

                            $bankName = $supplier->bank?->name ? 'Bank: ' . $supplier->bank->name : null;

                            $lines = collect([
                                $supplier->name,
                                $bankName,
                                $supplier->bank_account_name ? 'Nama Rekening: ' . $supplier->bank_account_name : null,
                                $supplier->bank_account_no ? 'No. Rekening: ' . $supplier->bank_account_no : null,
                            ])->filter()->map(fn($line) => e($line));

                            return new HtmlString($lines->implode('<br>'));
                        })
                        ->columnSpanFull(),

                    Placeholder::make('invoice_details_preview')
                        ->label('Detail Invoice')
                        ->visible(fn(callable $get) => $get('payment_for') == '3' && filled($get('invoicePurchases')))
                        ->content(function (callable $get) {
                            $invoiceIds = $get('invoicePurchases');

                            if (!$invoiceIds) {
                                return new HtmlString('-');
                            }

                            $invoices = InvoicePurchase::with([
                                'detailInvoices.detailRequest.product.unit',
                                'store',
                            ])->whereIn('id', $invoiceIds)->get();

                            if ($invoices->isEmpty()) {
                                return new HtmlString('-');
                            }

                            $content = $invoices->map(function (InvoicePurchase $invoice) {
                                $details = $invoice->detailInvoices->map(function ($detail) {
                                    $detailRequest = $detail->detailRequest;
                                    $product = $detailRequest?->product;
                                    $unit = $product?->unit?->unit;
                                    $quantity = $detail->quantity_product ?? null;
                                    $subtotal = $detail->subtotal_invoice ?? null;

                                    $unitPriceStr = null;
                                    if ($quantity && $subtotal) {
                                        $unitPrice = $subtotal / $quantity;
                                        $unitPriceStr = '@ Rp ' . number_format($unitPrice, 0, ',', '.');
                                    }

                                    $notes = $detailRequest?->notes;

                                    $history = $product ? $product->getLatestPrices(5) : collect();
                                    $historyHtml = '';
                                    if ($history->isNotEmpty()) {
                                        $historyItems = $history->map(function ($h) {
                                            return 'Rp ' . number_format($h['price'], 0, ',', '.') . ' (' . Carbon::parse($h['date'])->format('d/m/y') . ')';
                                        })->join(', ');
                                        $historyHtml = '<div style="font-size: 0.75rem; color: #6b7280; margin-top: 2px;">' .
                                            '<strong>5 Transaksi Terakhir:</strong> ' . $historyItems .
                                            '</div>';
                                    }

                                    $parts = collect([
                                        $product?->name,
                                        $quantity && $unit ? $quantity . ' ' . $unit : ($quantity ?? null),
                                        $unitPriceStr,
                                        $notes,
                                    ])->filter();

                                    return ($parts->isNotEmpty() ? $parts->implode(' - ') : '') . $historyHtml;
                                })->filter()->values();

                                $detailHtml = $details->isNotEmpty()
                                    ? '<ul style="margin: 0; padding-left: 18px;">' . $details->map(fn($line) => '<li>' . $line . '</li>')->implode('') . '</ul>'
                                    : '<em>Tidak ada rincian produk.</em>';

                                return '<div>'
                                    . '<strong>Tanggal:</strong> ' . Carbon::parse($invoice->date)->format('d/m/Y') . '<br>'
                                    . '<strong>Toko:</strong> ' . e($invoice->store?->nickname ?? '-') . '<br>'
                                    . '<strong>Total:</strong> Rp ' . number_format($invoice->total_price, 0, ',', '.')
                                    . '<div style="margin-top: 6px;">' . $detailHtml . '</div>'
                                    . '</div>';
                            })->implode('<hr style="margin: 12px 0;">');

                            return new HtmlString($content);
                        })
                        ->columnSpanFull(),
                ]),
            ]),

            Section::make()->schema([
                Grid::make(['default' => 1])->schema([

                    Hidden::make('total_amount')->default(0),

                    Placeholder::make('total_amount_display')
                        ->label('Total Pembayaran')
                        ->content(fn(callable $get) => 'Rp ' . number_format($get('total_amount') ?? 0, 0, ',', '.')),

                    CurrencyInput::make('transfer_amount'),

                    ImageInput::make('image')
                        ->directory('images/PaymentReceipt'),

                    ImageInput::make('image_adjust')
                        ->directory('images/PaymentReceipt')
                        ->hidden(fn($operation) => $operation === 'create'),

                    Notes::make('notes')
                        ->hidden(fn($operation) => $operation === 'create'),

                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                ImageOpenUrlColumn::make('image')
                    ->label('Payment')
                    ->disk('public') // Paksa gunakan disk public
                    ->url(fn($record) => PublicStorageUrl::from($record->image)),
                ImageOpenUrlColumn::make('image_adjust')
                    ->label('Adjust')
                    ->disk('public') // Paksa gunakan disk public
                    ->url(fn($record) => PublicStorageUrl::from($record->image_adjust)),

                SupplierColumn::make('Supplier')
                    ->visible(fn($livewire) => $livewire->activeTab !== 'daily salary'),

                TextColumn::make('user.name')
                    ->label('Employee')
                    ->visible(fn($livewire) => $livewire->activeTab === 'daily salary'),

                TextColumn::make('created_at')
                    ->date(),

                CurrencyColumn::make('transfer_amount'),

                // Kolom untuk Invoice (payment_for = 3)
                TextColumn::make('invoicePurchases.date')
                    ->label('Date')
                    ->visible(fn($livewire) => $livewire->activeTab === 'invoice'),

                TextColumn::make('invoicePurchases.createdBy.name')
                    ->label('Created By')
                    ->visible(fn($livewire) => $livewire->activeTab === 'invoice'),

                // Kolom untuk Fuel Service (payment_for = 1)
                TextColumn::make('fuelServices.vehicle.no_register')
                    ->label('Fuel Service Invoice')
                    ->visible(fn($livewire) => $livewire->activeTab === 'fuel service'),

                // Kolom untuk Daily Salary (payment_for = 2)
                TextColumn::make('dailySalaries.date')
                    ->label('Salary Date')
                    ->visible(fn($livewire) => $livewire->activeTab === 'daily salary')
                    ->formatStateUsing(function ($state, $record) {
                        return $record->dailySalaries->pluck('date')
                            ->map(fn($date) => Carbon::parse($date)->format('d/m/Y'))
                            ->join(', ');
                    }),

                // Kolom detail yang lebih lengkap per tab
                TextColumn::make('payment_details')
                    ->html()
                    ->label(function ($livewire) {
                        return match ($livewire->activeTab) {
                            'invoice' => 'Invoice Details',
                            'fuel service' => 'Fuel Service Details',
                            'daily salary' => 'Salary Details',
                            default => 'Details'
                        };
                    })
                    ->formatStateUsing(function ($state, $record, $livewire) {
                        return match ($livewire->activeTab) {
                            'invoice' => $record->invoicePurchases->map(function ($invoice) {
                                    return "Invoice: {$invoice->invoice_purchase_name}<br>" .
                                    "Amount: Rp " . number_format($invoice->total_price, 0, ',', '.');
                                })->join('<br><br>'),

                            'fuel service' => $record->fuelServices->map(function ($fs) {
                                    $typeStr = $fs->fuel_service == 1 ? 'Fuel' : 'Service';
                                    return "Vehicle: {$fs->vehicle?->no_register}<br>" .
                                    "Type: {$typeStr}<br>" .
                                    "Amount: Rp " . number_format($fs->amount, 0, ',', '.');
                                })->join('<br><br>'),

                            'daily salary' => $record->dailySalaries->map(function ($salary) {
                                    return "Date: " . Carbon::parse($salary->date)->format('d/m/Y') . "<br>" .
                                    "Amount: Rp " . number_format($salary->amount, 0, ',', '.');
                                })->join('<br><br>'),

                            default => 'Details'
                        };
                    }),
            ])

            ->filters([])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationGroup::make('', [
                RelationManagers\FuelServicesRelationManager::class,
                RelationManagers\DailySalariesRelationManager::class,
                RelationManagers\InvoicePurchasesRelationManager::class,
            ])
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentReceipts::route('/'),
            'create' => Pages\CreatePaymentReceipt::route('/create'),
            'view' => Pages\ViewPaymentReceipt::route('/{record}'),
            'edit' => Pages\EditPaymentReceipt::route('/{record}/edit'),
        ];
    }
}
