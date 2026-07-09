<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Transaction\Settings;
use App\Filament\Forms\ImageInput;
use Filament\Tables;
use App\Models\ProductOnlineGroup;
use App\Models\Product;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Filament\Tables\Columns\IconColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\Panel\ProductOnlineGroupResource\Pages;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class ProductOnlineGroupResource extends Resource
{
    protected static ?string $model = ProductOnlineGroup::class;

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Settings::class;

    public static function getModelLabel(): string
    {
        return __('crud.productOnlineGroups.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.productOnlineGroups.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.productOnlineGroups.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(['default' => 1, 'lg' => 3])
            ->schema([
                Section::make('Media Produk')
                    ->icon('heroicon-o-photo')
                    ->description('Pilih gambar dari produk yang tergabung dalam group ini')
                    ->schema([
                        Repeater::make('images')
                            ->label('')
                            ->relationship('images')
                            ->schema([
                                Select::make('product_image_id')
                                    ->label('Gambar')
                                    ->options(function (Get $get, $record) {
                                        $id = $record?->id ?? request()->route('record');
                                        if (!$id) return ['—' => 'Simpan group terlebih dahulu'];

                                        $group = ProductOnlineGroup::with('items.product.images')->find($id);
                                        if (!$group || $group->items->isEmpty())
                                            return ['—' => 'Tidak ada produk dalam group'];

                                        $options = [];
                                        foreach ($group->items as $item) {
                                            foreach ($item->product->images as $img) {
                                                $filename = basename($img->getRawOriginal('image_url') ?? '');
                                                $options[$img->id] = "#{$img->id} {$item->product->name}" . ($filename ? " ({$filename})" : '');
                                            }
                                        }
                                        return $options ?: ['—' => 'Tidak ada gambar tersedia'];
                                    })
                                    ->searchable()
                                    ->required(),
                            ])
                            ->orderColumn('order')
                            ->reorderable()
                            ->addActionLabel('Tambah Foto')
                            ->defaultItems(0)
                            ->collapsed(false),
                    ])
                    ->columnSpanFull(),

                Group::make([
                    Section::make('Informasi Dasar')
                        ->icon('heroicon-o-information-circle')
                        ->schema([
                            TextInput::make('name')
                                ->required()
                                ->string()
                                ->autofocus()
                                ->live(onBlur: true)
                                ->afterStateUpdated(fn(Set $set, ?string $state) => $set('slug', Str::slug($state))),

                            Grid::make(2)
                                ->schema([
                                    TextInput::make('slug')
                                        ->required()
                                        ->string()
                                        ->unique(ProductOnlineGroup::class, 'slug', ignoreRecord: true),

                                    Select::make('unit_id')
                                        ->required()
                                        ->relationship('unit', 'unit')
                                        ->preload()
                                        ->searchable(),
                                ]),

                            \App\Filament\Forms\Notes::make('description'),
                        ])
                        ->collapsible(),

                    Section::make('Harga & Grosir')
                        ->icon('heroicon-o-currency-dollar')
                        ->description('Kelola harga online dan tier harga grosir')
                        ->schema([
                            TextInput::make('online_price')
                                ->label('Harga Online Standar')
                                ->numeric()
                                ->step(1)
                                ->prefix('Rp')
                                ->helperText('Harga ini akan digunakan jika jumlah pembelian tidak masuk ke dalam tier grosir apa pun.')
                                ->visible(fn() => auth()->user()->hasRole(['super_admin', 'admin'])),

                            Repeater::make('priceTiers')
                                ->label('Tier Harga Grosir')
                                ->relationship('priceTiers')
                                ->schema([
                                    Grid::make(['default' => 1, 'sm' => 2, 'xl' => 4])
                                        ->schema([
                                            TextInput::make('min_quantity')
                                                ->label('Min Qty')
                                                ->numeric()
                                                ->required()
                                                ->minValue(1)
                                                ->default(1),

                                            TextInput::make('max_quantity')
                                                ->label('Max Qty')
                                                ->numeric()
                                                ->minValue(1)
                                                ->nullable()
                                                ->placeholder('∞')
                                                ->helperText('Kosongkan untuk tak terhingga'),

                                            TextInput::make('price')
                                                ->label('Harga Tier')
                                                ->numeric()
                                                ->required()
                                                ->prefix('Rp'),

                                            TextInput::make('label')
                                                ->label('Label')
                                                ->string()
                                                ->nullable()
                                                ->placeholder('Contoh: Grosir A'),
                                        ]),
                                ])
                                ->defaultItems(0)
                                ->addActionLabel('Tambah Tier Harga')
                                ->collapsible()
                                ->itemLabel(fn(array $state): ?string => isset($state['min_quantity']) ? "Tier: Min {$state['min_quantity']}" : null),
                        ])
                        ->collapsible(),

                    Section::make('Produk Anggota')
                        ->icon('heroicon-o-cube')
                        ->description('Pilih produk fisik yang tergabung dalam grup ini')
                        ->schema([
                            Repeater::make('items')
                                ->label('Produk Fisik')
                                ->relationship('items')
                                ->schema([
                                    Select::make('product_id')
                                        ->label('Produk')
                                        ->required()
                                        ->options(Product::query()->pluck('name', 'id'))
                                        ->searchable()
                                        ->preload()
                                        ->native(false)
                                        ->distinct()
                                        ->rules([
                                            function () {
                                                return function (string $attribute, $value, \Closure $fail) {
                                                    if (!$value) return;

                                                    $existing = \App\Models\ProductOnlineGroupItem::where('product_id', $value)
                                                        ->whereHas('group', fn($q) => $q->whereNull('deleted_at'));

                                                    $recordId = request()?->route('record');
                                                    if ($recordId) {
                                                        $existing->where('product_online_group_id', '!=', $recordId);
                                                    }

                                                    if ($existing->exists()) {
                                                        $product = Product::find($value);
                                                        $fail("Produk \"{$product?->name}\" sudah menjadi anggota grup lain.");
                                                    }
                                                };
                                            },
                                        ]),
                                ])
                                ->defaultItems(0)
                                ->addActionLabel('Tambah Produk')
                                ->collapsible()
                                ->itemLabel(fn(array $state): ?string => isset($state['product_id']) ? Product::find($state['product_id'])?->name : null),
                        ])
                        ->collapsible(),
                ])
                ->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Status & Visibilitas')
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            \Filament\Forms\Components\Toggle::make('is_active')
                                ->label('Aktif')
                                ->default(true),
                        ])
                        ->collapsible(),

                    Section::make('Pengelompokan')
                        ->icon('heroicon-o-tag')
                        ->schema([
                            Select::make('online_category_id')
                                ->required()
                                ->relationship('onlineCategory', 'name')
                                ->preload()
                                ->searchable(),
                        ])
                        ->collapsible(),
                ])
                ->columnSpan(['lg' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('name')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('onlineCategory.name')
                    ->label('Kategori'),

                TextColumn::make('combined_stock')
                    ->label('Stok Gabungan')
                    ->sortable()
                    ->color(fn($state) => $state !== null && $state > 0 ? 'success' : 'danger'),

                TextColumn::make('items_count')
                    ->label('Anggota')
                    ->counts('items')
                    ->formatStateUsing(fn($state) => $state . ' produk'),

                IconColumn::make('is_active')
                    ->boolean()
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Pembuat'),
            ])
            ->filters([])
            ->actions([
                \Filament\Actions\ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                ]),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\ForceDeleteBulkAction::make(),
                    \Filament\Actions\RestoreBulkAction::make(),
                ]),
            ])
            ->defaultSort('name', 'asc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductOnlineGroups::route('/'),
            'create' => Pages\CreateProductOnlineGroup::route('/create'),
            'edit' => Pages\EditProductOnlineGroup::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
