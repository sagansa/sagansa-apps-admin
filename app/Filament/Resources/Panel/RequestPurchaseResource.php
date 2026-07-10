<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Purchases;
use App\Filament\Filters\SelectStoreFilter;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\StoreSelect;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\RequestPurchase;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Panel\RequestPurchaseResource\Pages;
use App\Models\Product;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Actions\ActionGroup;
use App\Filament\Resources\Panel\RequestPurchaseResource\RelationManagers\DetailRequestsRelationManager;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Utilities\Get;

class RequestPurchaseResource extends Resource
{
    protected static ?string $model = RequestPurchase::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 10;


    protected static ?string $cluster = Purchases::class;

    protected static ?string $pluralLabel = 'Invoice';

    public static function getModelLabel(): string
    {
        return __('crud.requestPurchases.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.requestPurchases.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.requestPurchases.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                Grid::make(3)
                    ->schema([
                        // Left Column: Items List
                        Section::make('Daftar Item Pembelian')
                            ->description('Pilih produk, tentukan jumlah, dan metode pembayaran.')
                            ->schema([
                                self::getItemsRepeater(),
                            ])
                            ->columnSpan(['lg' => 2]),

                        // Right Column: Main Info
                        Section::make('Informasi Utama')
                            ->description('Pilih toko dan tanggal transaksi.')
                            ->schema([
                                StoreSelect::make('store_id')
                                    ->required(),
                                DateInput::make('date')
                                    ->required(),
                            ])
                            ->columnSpan(['lg' => 1]),
                    ]),
            ])
            ->columns(1);
    }

    public static function table(Table $table): Table
    {
        $requestPurchases = RequestPurchase::query();

        if (!Auth::user()->hasRole('admin')) {
            $requestPurchases->where('user_id', Auth::id());
        }

        return $table
            ->query($requestPurchases)
            ->poll('60s')
            ->columns([
                TextColumn::make('store.nickname'),

                TextColumn::make('date'),

                TextColumn::make('orders_summary')
                    ->label('Orders')
                    ->state(function (RequestPurchase $record): string {
                        return $record->detailRequests->map(function ($item) {
                            $statusLabels = [
                                '1' => '<span style="background:#f59e0b;color:#fff;padding:1px 6px;border-radius:4px;font-size:0.75rem;">⏳ process</span>',
                                '2' => '<span style="background:#10b981;color:#fff;padding:1px 6px;border-radius:4px;font-size:0.75rem;">✅ done</span>',
                                '3' => '<span style="background:#ef4444;color:#fff;padding:1px 6px;border-radius:4px;font-size:0.75rem;">❌ reject</span>',
                                '4' => '<span style="background:#3b82f6;color:#fff;padding:1px 6px;border-radius:4px;font-size:0.75rem;">✔ approved</span>',
                                '5' => '<span style="background:#6b7280;color:#fff;padding:1px 6px;border-radius:4px;font-size:0.75rem;">not valid</span>',
                                '6' => '<span style="background:#9ca3af;color:#fff;padding:1px 6px;border-radius:4px;font-size:0.75rem;">not used</span>',
                            ];

                            $status = $statusLabels[$item->status] ?? '<span style="background:#d1d5db;padding:1px 6px;border-radius:4px;font-size:0.75rem;">unknown</span>';

                            // Tampilkan tanda perlu approval jika statusnya masih process (1)
                            $approvalNote = ($item->status == 1)
                                ? ' <span style="color:#ef4444;font-size:0.7rem;">(perlu approval)</span>'
                                : ' <span style="color:#10b981;font-size:0.7rem;">(langsung)</span>';

                            return "{$item->product->name} = {$item->quantity_plan} {$item->product->unit->unit} {$status}{$approvalNote}";
                        })->implode('<br>');
                    })
                    ->html()
                    ->extraAttributes(['class' => 'whitespace-pre-wrap']),

                TextColumn::make('user.name')->hidden(fn() => !Auth::user()->hasRole('admin')),

                // StatusColumn::make('status'),
            ])
            ->filters([
                SelectStoreFilter::make('store_id'),

                \Filament\Tables\Filters\SelectFilter::make('user_id')
                    ->label('Pembuat')
                    ->relationship('user', 'name')
                    ->searchable()
                    ->preload()
                    ->visible(fn () => Auth::user()->hasRole('admin')),

                \Filament\Tables\Filters\TernaryFilter::make('is_empty')
                    ->label('Status Detail')
                    ->placeholder('Semua')
                    ->trueLabel('Invoice Kosong')
                    ->falseLabel('Ada Detail Item')
                    ->queries(
                        true: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereDoesntHave('detailRequests'),
                        false: fn (\Illuminate\Database\Eloquent\Builder $query) => $query->whereHas('detailRequests'),
                    )
            ])
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
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            DetailRequestsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRequestPurchases::route('/'),
            'create' => Pages\CreateRequestPurchase::route('/create'),
            'view' => Pages\ViewRequestPurchase::route('/{record}'),
            'edit' => Pages\EditRequestPurchase::route('/{record}/edit'),
        ];
    }

    public static function getItemsRepeater(): Repeater
    {

        return Repeater::make('detailRequests')
            ->relationship()
            ->minItems(1)
            ->mutateRelationshipDataBeforeCreateUsing(function (array $data, RequestPurchase $record): array {
                $data['store_id'] = $record->store_id;
                $product = Product::find($data['product_id']);
                $productDefault = $product->payment_type_id ?? 1;
                $plannedPayment = $data['payment_type_id'] ?? $productDefault;
                
                // Produk default Transfer (1) tetapi berencana membayar Tunai (2) -> butuh approval (1)
                // Selain itu -> langsung approved (4)
                $data['status'] = ($productDefault == 1 && $plannedPayment == 2) ? '1' : '4';

                return $data;
            })
            ->schema([
                Select::make('product_id')
                    ->relationship('product', 'name', modifyQueryUsing: fn ($query) => $query->where('payment_type_id', '!=', '3'))
                    ->hiddenLabel()
                    ->placeholder('Product')
                    ->required()
                    ->preload()
                    ->searchable()
                    ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                    ->reactive()
                    ->afterStateUpdated(fn (Set $set, $state) => 
                        $set('payment_type_id', optional(Product::find($state))->payment_type_id ?? 1)
                    )
                    ->columnSpan(fn ($operation) => $operation === 'create' ? 4 : 3),

                TextInput::make('quantity_plan')
                    ->required()
                    ->hiddenLabel()
                    ->placeholder('Quantity Plan')
                    ->minValue(1)
                    ->numeric()
                    ->suffix(function (Get $get) {
                        $product = Product::find($get('product_id'));
                        return $product ? $product->unit->unit : '';
                    })
                    ->columnSpan(fn ($operation) => $operation === 'create' ? 2 : 1),

                Select::make('payment_type_id')
                    ->hiddenLabel()
                    ->placeholder('Payment Type')
                    ->options([
                        '1' => 'Transfer',
                        '2' => 'Tunai',
                    ])
                    ->required()
                    ->reactive()
                    ->columnSpan(2),

                Select::make('status')
                    ->hiddenLabel()
                    ->options([
                        '1' => 'process',
                        '2' => 'done',
                        '3' => 'reject',
                        '4' => 'approved',
                        '5' => 'not valid',
                        '6' => 'not used',
                    ])
                    ->default(1)
                    // Hanya admin yang bisa ubah status; saat create dikontrol otomatis
                    ->hidden(fn($operation) => $operation === 'create')
                    ->disabled(fn() => !Auth::user()->hasRole('admin'))
                    ->columnSpan(2),

            ])
            ->columns([
                'md' => 8,
            ])
            ->defaultItems(1);
    }
}
