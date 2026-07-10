<?php

namespace App\Filament\Resources\Panel;

use App\Enum\PaymentFor;
use App\Enum\PaymentType;
use App\Filament\Clusters\Cash;
use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Columns\SupplierColumn;
use App\Filament\Filters\DateFilter;
use App\Filament\Forms\CurrencyInput;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\Notes;
use App\Filament\Resources\Panel\PaymentReceiptResource\Pages;
use App\Filament\Resources\Panel\PaymentReceiptResource\RelationManagers;
use App\Filament\Tables\PaymentReceiptTable;
use App\Models\DailySalary;
use App\Models\FuelService;
use App\Models\InvoicePurchase;
use App\Models\PaymentReceipt;
use App\Models\Supplier;
use App\Models\User;
use App\Support\PublicStorageUrl;
use App\Support\ResolvesCreatedBy;
use Carbon\Carbon;
use Filament\Actions\ActionGroup;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Radio;
use Filament\Forms\Components\Select;
use Filament\Resources\RelationManagers\RelationGroup;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

class PaymentReceiptResource extends Resource
{
    use ResolvesCreatedBy;

    protected static ?string $model = PaymentReceipt::class;

    protected static ?int $navigationSort = 50;

    protected static ?string $cluster = Cash::class;

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

    // =========================================================================
    // FORM
    // =========================================================================

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema(fn (): array => static::getSelectionSchema()),
            Section::make()->schema(fn (): array => static::getPaymentMetaSchema()),
        ]);
    }

    /**
     * Schema bagian pilihan jenis pembayaran + relasi terkait (fuel/salary/invoice).
     */
    private static function getSelectionSchema(): array
    {
        return [
            Grid::make(['default' => 1])->schema([
                Radio::make('payment_for')
                    ->disabled(fn ($operation) => $operation === 'edit')
                    ->options(PaymentFor::options())
                    ->inline()
                    ->reactive()
                    ->afterStateUpdated(function (Set $set) {
                        $set('total_amount', 0);
                        $set('transfer_amount', 0);
                    }),

                static::fuelServiceCreatedBySelect(),
                static::fuelServicesSelect(),
                static::employeeSelect(),
                static::dailySalariesSelect(),
                static::invoicePurchasesSelect(),
                static::supplierSelect(),
                static::supplierBankInformationPlaceholder(),
                static::invoiceDetailsPreviewPlaceholder(),
            ]),
        ];
    }

    /**
     * Schema bagian meta pembayaran (total, transfer, image, notes).
     */
    private static function getPaymentMetaSchema(): array
    {
        return [
            Grid::make(['default' => 1])->schema([
                Hidden::make('total_amount')->default(0),

                Placeholder::make('total_amount_display')
                    ->label('Total Pembayaran')
                    ->content(fn (Get $get) => 'Rp ' . number_format($get('total_amount') ?? 0, 0, ',', '.')),

                CurrencyInput::make('transfer_amount'),

                ImageInput::make('image')
                    ->directory('images/PaymentReceipt'),

                ImageInput::make('image_adjust')
                    ->directory('images/PaymentReceipt')
                    ->hidden(fn ($operation) => $operation === 'create'),

                Notes::make('notes')
                    ->hidden(fn ($operation) => $operation === 'create'),
            ]),
        ];
    }

    // -------------------------------------------------------------------------
    // Field builders — Fuel/Service (payment_for = 1)
    // -------------------------------------------------------------------------

    private static function fuelServiceCreatedBySelect(): Select
    {
        return Select::make('fuel_service_created_by')
            ->label('Created By')
            ->visible(fn (Get $get) => $get('payment_for') == PaymentFor::FuelService->value)
            ->hidden(fn ($operation) => $operation === 'edit' || $operation === 'view')
            ->options(function () {
                // created_by_id bisa berupa numeric id ATAU uuid user; kumpulkan
                // keduanya lalu resolve ke user.
                $createdByIds = FuelService::query()
                    ->where('payment_type_id', PaymentType::Transfer->value)
                    ->where('status', '1')
                    ->pluck('created_by_id')
                    ->filter()
                    ->unique()
                    ->all();

                $numericIds = array_filter($createdByIds, 'is_numeric');
                $uuids = array_filter($createdByIds, fn ($v) => ! is_numeric($v));

                $users = collect();
                if (! empty($numericIds)) {
                    $users = $users->merge(User::whereIn('id', $numericIds)->get(['id', 'name']));
                }
                if (! empty($uuids)) {
                    $users = $users->merge(User::whereIn('uuid', $uuids)->get(['id', 'name']));
                }

                return $users->sortBy('name')->pluck('name', 'id');
            })
            ->searchable()
            ->preload()
            ->reactive()
            ->dehydrated(false)
            ->afterStateUpdated(function ($state, Set $set) {
                if (! $state) {
                    $set('fuelServices', []);
                    $set('total_amount', 0);
                    $set('transfer_amount', 0);

                    return;
                }

                $targetIds = self::resolveUserIdentifier($state);

                $fuelServices = FuelService::query()
                    ->whereIn('created_by_id', $targetIds)
                    ->where('payment_type_id', PaymentType::Transfer->value)
                    ->where('status', '1')
                    ->orderBy('date', 'desc')
                    ->get(['id', 'amount']);

                $set('fuelServices', $fuelServices->pluck('id')->toArray());
                $set('total_amount', $fuelServices->sum('amount'));
                $set('transfer_amount', $fuelServices->sum('amount'));
            });
    }

    private static function fuelServicesSelect(): Select
    {
        return Select::make('fuelServices')
            ->visible(fn (Get $get) => $get('payment_for') == PaymentFor::FuelService->value)
            ->required(fn (Get $get, $operation) => $get('payment_for') == PaymentFor::FuelService->value && $operation === 'create')
            ->hidden(fn ($operation) => $operation === 'edit' || $operation === 'view')
            ->multiple()
            ->relationship(
                name: 'fuelServices',
                modifyQueryUsing: fn (Builder $query, Get $get) => $query
                    ->with([
                        'vehicle',
                        'createdBy' => fn ($q) => $q->withTrashed(),
                    ])
                    ->where('payment_type_id', PaymentType::Transfer->value)
                    ->where('status', '1')
                    ->when(
                        $get('fuel_service_created_by'),
                        fn ($q, $createdById) => $q->whereIn('created_by_id', self::resolveUserIdentifier($createdById))
                    )
                    ->orderBy('date', 'desc')
            )
            ->getOptionLabelFromRecordUsing(fn (FuelService $record) => "{$record->fuel_service_name}")
            ->preload()
            ->reactive()
            ->afterStateUpdated(function (?array $state, Set $set) {
                $fuelServiceIds = $state ?? [];

                if (empty($fuelServiceIds)) {
                    $set('total_amount', 0);
                    $set('transfer_amount', 0);

                    return;
                }

                $totalAmount = FuelService::whereIn('id', $fuelServiceIds)->sum('amount');
                $set('total_amount', $totalAmount);
                $set('transfer_amount', $totalAmount);
            });
    }

    // -------------------------------------------------------------------------
    // Field builders — Daily Salary (payment_for = 2)
    // -------------------------------------------------------------------------

    private static function employeeSelect(): Select
    {
        return Select::make('user_id')
            ->label('Employee')
            ->visible(fn (Get $get) => $get('payment_for') == PaymentFor::DailySalary->value)
            ->required(fn (Get $get) => $get('payment_for') == PaymentFor::DailySalary->value)
            ->relationship('user', 'name', fn (Builder $query) => $query
                ->whereHas('roles', fn (Builder $q) => $q->whereIn('name', ['staff', 'supervisor']))
                ->orderBy('name', 'asc'))
            ->searchable()
            ->preload()
            ->reactive()
            ->afterStateUpdated(function ($state, Set $set) {
                if (! $state) {
                    $set('dailySalaries', []);
                    $set('total_amount', 0);
                    $set('transfer_amount', 0);

                    return;
                }

                $targetIds = self::resolveUserIdentifier($state);

                $salaries = DailySalary::whereIn('created_by_id', $targetIds)
                    ->where('payment_type_id', PaymentType::Transfer->value)
                    ->where('status', '3')
                    ->get(['id', 'amount']);

                $set('dailySalaries', $salaries->pluck('id')->toArray());
                $set('total_amount', $salaries->sum('amount'));
                $set('transfer_amount', $salaries->sum('amount'));
            });
    }

    private static function dailySalariesSelect(): Select
    {
        return Select::make('dailySalaries')
            ->visible(fn (Get $get) => $get('payment_for') == PaymentFor::DailySalary->value)
            ->required(fn (Get $get, $operation) => $get('payment_for') == PaymentFor::DailySalary->value && $operation === 'create')
            ->hidden(fn ($operation) => $operation === 'edit' || $operation === 'view')
            ->multiple()
            ->relationship(
                name: 'dailySalaries',
                modifyQueryUsing: fn (Builder $query, Get $get) => $query
                    ->with([
                        'createdBy' => fn ($q) => $q->withTrashed(),
                        'store',
                    ])
                    ->where('payment_type_id', PaymentType::Transfer->value)
                    ->where('status', '3')
                    ->when(
                        $get('user_id'),
                        fn ($q, $uid) => $q->whereIn('created_by_id', self::resolveUserIdentifier($uid))
                    )
                    ->orderBy('date', 'desc')
            )
            ->getOptionLabelFromRecordUsing(fn (DailySalary $record) => "{$record->daily_salary_name}")
            ->searchable()
            ->preload()
            ->reactive()
            ->afterStateUpdated(function (?array $state, Set $set) {
                if (empty($state)) {
                    $set('total_amount', 0);
                    $set('transfer_amount', 0);

                    return;
                }

                $totalAmount = DailySalary::whereIn('id', $state)->sum('amount');
                $set('total_amount', $totalAmount);
                $set('transfer_amount', $totalAmount);
            });
    }

    // -------------------------------------------------------------------------
    // Field builders — Invoice (payment_for = 3)
    // -------------------------------------------------------------------------

    private static function invoicePurchasesSelect(): Select
    {
        return Select::make('invoicePurchases')
            ->visible(fn (Get $get) => $get('payment_for') == PaymentFor::InvoicePurchase->value)
            ->required(fn (Get $get, $operation) => $get('payment_for') == PaymentFor::InvoicePurchase->value && $operation === 'create')
            ->hidden(fn ($operation) => $operation === 'edit' || $operation === 'view')
            ->multiple()
            ->relationship(
                name: 'invoicePurchases',
                modifyQueryUsing: fn (Builder $query) => $query
                    ->with([
                        'supplier',
                        'store',
                    ])
                    ->where('payment_type_id', PaymentType::Transfer->value)
                    ->where('payment_status', '1')
                    ->orderBy('date', 'desc')
            )
            ->getOptionLabelFromRecordUsing(fn (InvoicePurchase $record) => "{$record->invoice_purchase_name}")
            ->preload()
            ->searchable()
            ->reactive()
            ->afterStateUpdated(function (?array $state, Set $set) {
                $invoiceIds = $state ?? [];

                if (empty($invoiceIds)) {
                    $set('total_amount', 0);
                    $set('transfer_amount', 0);
                    $set('supplier_id', null);

                    return;
                }

                $invoices = InvoicePurchase::with('supplier')->whereIn('id', $invoiceIds)->get();
                $totalAmount = $invoices->sum('total_price');
                $set('total_amount', $totalAmount);

                // Auto-set supplier hanya jika semua invoice dari supplier yang sama.
                if ($invoices->isNotEmpty() && $invoices->pluck('supplier_id')->unique()->count() === 1) {
                    $set('supplier_id', $invoices->first()->supplier_id);
                }

                $set('transfer_amount', $totalAmount);
            });
    }

    private static function supplierSelect(): Select
    {
        return Select::make('supplier_id')
            ->label(__('crud.suppliers.itemTitle'))
            ->visible(fn (Get $get) => $get('payment_for') == PaymentFor::InvoicePurchase->value)
            ->required(fn (Get $get) => $get('payment_for') == PaymentFor::InvoicePurchase->value)
            ->relationship(
                name: 'supplier',
                modifyQueryUsing: fn (Builder $query) => $query
                    ->where('status', '<>', '3')
                    ->orderBy('name', 'asc'),
            )
            ->getOptionLabelFromRecordUsing(fn (Supplier $record) => "{$record->supplier_name}")
            ->searchable()
            ->preload()
            ->reactive();
    }

    private static function supplierBankInformationPlaceholder(): Placeholder
    {
        return Placeholder::make('supplier_bank_information')
            ->label('Rekening Tujuan')
            ->visible(fn (Get $get) => filled($get('supplier_id')))
            ->content(function (Get $get) {
                $supplierId = $get('supplier_id');

                if (! $supplierId) {
                    return new HtmlString('-');
                }

                $supplier = Supplier::with('bank')->find($supplierId);

                if (! $supplier) {
                    return new HtmlString('-');
                }

                $bankName = $supplier->bank?->name ? 'Bank: ' . $supplier->bank->name : null;

                $lines = collect([
                    $supplier->name,
                    $bankName,
                    $supplier->bank_account_name ? 'Nama Rekening: ' . $supplier->bank_account_name : null,
                    $supplier->bank_account_no ? 'No. Rekening: ' . $supplier->bank_account_no : null,
                ])->filter()->map(fn ($line) => e($line));

                return new HtmlString($lines->implode('<br>'));
            })
            ->columnSpanFull();
    }

    /**
     * Preview detail invoice + 5 transaksi terakhir per produk.
     *
     * Optimisasi N+1: sebelumnya getLatestPrices() dipanggil per produk dalam
     * loop nested. Sekarang semua product_id dikumpulkan dulu lalu di-load
     * lewat Product::latestPricesForProducts() dalam 1 query.
     */
    private static function invoiceDetailsPreviewPlaceholder(): Placeholder
    {
        return Placeholder::make('invoice_details_preview')
            ->label('Detail Invoice')
            ->visible(fn (Get $get) => $get('payment_for') == PaymentFor::InvoicePurchase->value && filled($get('invoicePurchases')))
            ->content(function (Get $get) {
                $invoiceIds = $get('invoicePurchases');

                if (! $invoiceIds) {
                    return new HtmlString('-');
                }

                $invoices = InvoicePurchase::with([
                    'detailInvoices.detailRequest.product.unit',
                    'store',
                ])->whereIn('id', $invoiceIds)->get();

                if ($invoices->isEmpty()) {
                    return new HtmlString('-');
                }

                // Kumpulkan semua product_id unik untuk 1 query batch history harga.
                $productIds = $invoices
                    ->flatMap(fn (InvoicePurchase $invoice) => $invoice->detailInvoices
                        ->map(fn ($detail) => $detail->detailRequest?->product_id))
                    ->filter()
                    ->unique()
                    ->values()
                    ->all();

                $priceHistory = \App\Models\Product::latestPricesForProducts($productIds, 5);

                $content = $invoices->map(function (InvoicePurchase $invoice) use ($priceHistory) {
                    $details = $invoice->detailInvoices->map(function ($detail) use ($priceHistory) {
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

                        $history = $product ? ($priceHistory[$product->id] ?? collect()) : collect();
                        $historyHtml = '';
                        if ($history->isNotEmpty()) {
                            $historyItems = $history->map(fn ($h) =>
                                'Rp ' . number_format($h['price'], 0, ',', '.') . ' (' . Carbon::parse($h['date'])->format('d/m/y') . ')'
                            )->join(', ');
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
                        ? '<ul style="margin: 0; padding-left: 18px;">' . $details->map(fn ($line) => '<li>' . $line . '</li>')->implode('') . '</ul>'
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
            ->columnSpanFull();
    }

    // =========================================================================
    // TABLE
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns(PaymentReceiptTable::schema())
            ->filters([
                DateFilter::make('created_at')->label('Dibuat Pada'),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc')
            ->poll('60s');
    }

    // =========================================================================
    // RELATIONS & PAGES
    // =========================================================================

    public static function getRelations(): array
    {
        return [
            RelationGroup::make('', [
                RelationManagers\FuelServicesRelationManager::class,
                RelationManagers\DailySalariesRelationManager::class,
                RelationManagers\InvoicePurchasesRelationManager::class,
            ]),
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
