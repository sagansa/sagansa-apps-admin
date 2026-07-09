<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Transaction\Settings;
use App\Filament\Columns\ActiveColumn;
use App\Filament\Forms\ActiveStatusSelect;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\Notes;
use Filament\Tables;
use App\Models\Product;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Group;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Filament\Resources\Panel\ProductResource\Pages;
use App\Filament\Resources\Panel\ProductResource\RelationManagers;
use App\Models\MaterialGroup;
use App\Models\OnlineCategory;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\ActionGroup;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Str;
use Filament\Tables\Columns\TextInputColumn;

class ProductResource extends Resource
{
    protected static ?string $model = Product::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Settings::class;


    public static function getModelLabel(): string
    {
        return __('crud.products.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.products.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.products.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(['default' => 1, 'lg' => 3])
            ->schema([
                Section::make('Media Produk')
                    ->icon('heroicon-o-photo')
                    ->schema([
                        Repeater::make('images')
                            ->label('')
                            ->relationship('images')
                            ->schema([
                                ImageInput::make('image_url')
                                    ->label('')
                                    ->directory('images/Product')
                                    ->hiddenLabel()
                                    ->imagePreviewHeight('200px'),
                            ])
                            ->orderColumn('order')
                            ->reorderable()
                            ->addActionLabel('Tambah Foto')
                            ->defaultItems(0)
                            ->collapsed(false),
                    ])
                    ->columnSpanFull(),

                // Main content column
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
                                        ->unique(Product::class, 'slug', ignoreRecord: true),

                                    Select::make('unit_id')
                                        ->required()
                                        ->relationship('unit', 'unit')
                                        ->preload()
                                        ->searchable(),
                                ]),

                            Notes::make('description'),
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
                ])
                ->columnSpan(['lg' => 2]),

                    // Sidebar column
                    Group::make([
                        Section::make('Status & Visibilitas')
                            ->icon('heroicon-o-check-circle')
                            ->schema([
                                ActiveStatusSelect::make('request')
                                    ->label('Status Request')
                                    ->default('2'),

                                ActiveStatusSelect::make('remaining')
                                    ->label('Status Stok')
                                    ->default('2'),
                            ])
                            ->collapsible(),

                        Section::make('Aset')
                            ->icon('heroicon-o-cube')
                            ->schema([
                                Toggle::make('is_asset')
                                    ->label('Produk ini adalah aset')
                                    ->helperText('Bila aktif, produk akan muncul di daftar product-picker modul Manajemen Aset dan otomatis dibuatkan instance aset saat dibeli via procurement.')
                                    ->default(false)
                                    ->live(),

                                Select::make('asset_category_id')
                                    ->label('Kategori Aset')
                                    ->relationship('assetCategory', 'name')
                                    ->preload()
                                    ->searchable()
                                    ->required(fn (callable $get) => (bool) $get('is_asset'))
                                    ->hidden(fn (callable $get) => !$get('is_asset'))
                                    ->helperText('Menentukan frekuensi pemeriksaan & checklist baku.'),
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

                                Select::make('material_group_id')
                                    ->required()
                                    ->relationship('materialGroup', 'name')
                                    ->preload()
                                    ->searchable(),

                                Select::make('payment_type_id')
                                    ->required()
                                    ->relationship('paymentType', 'name')
                                    ->preload()
                                    ->searchable(),
                            ])
                            ->collapsible(),

                        Section::make('Identifikasi')
                            ->icon('heroicon-o-qr-code')
                            ->schema([
                                TextInput::make('sku')
                                    ->label('SKU')
                                    ->nullable()
                                    ->string(),

                                TextInput::make('barcode')
                                    ->label('Barcode')
                                    ->nullable()
                                    ->string(),
                            ])
                            ->collapsible()
                            ->collapsed(),
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

                TextColumn::make('unit.unit'),

                ActiveColumn::make('request'),

                ActiveColumn::make('remaining'),

                TextColumn::make('paymentType.name'),

                SelectColumn::make('material_group_id')
                    ->label('Material Group')
                    ->options(MaterialGroup::query()->pluck('name', 'id')),

                SelectColumn::make('online_category_id')
                    ->label('Online Category')
                    ->options(OnlineCategory::query()->pluck('name', 'id')),

                TextInputColumn::make('online_price')
                    ->label('Online Price')
                    ->type('number')
                    ->alignEnd()
                    ->sortable()
                    ->visible(fn() => auth()->user()->hasRole(['super_admin', 'admin'])),

                TextColumn::make('user.name'),
            ])
            ->filters([

                SelectFilter::make('material_group_id')
                    ->multiple()
                    ->preload()
                    ->label('Material Group')
                    ->relationship('materialGroup', 'name'),

                SelectFilter::make('online_category_id')
                    ->multiple()
                    ->preload()
                    ->label('Online Category')
                    ->relationship('onlineCategory', 'name'),

                SelectFilter::make('remaining')
                    ->options([
                        '1' => 'active',
                        '2' => 'inactive',
                    ]),

                SelectFilter::make('request')
                    ->options([
                        '1' => 'active',
                        '2' => 'inactive',
                    ]),

                SelectFilter::make('payment_type_id')
                    ->label('Payment Type')
                    ->options([
                        '1' => 'transfer',
                        '2' => 'tunai',
                        '3' => 'non',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    // \Filament\Actions\ViewAction::make(),
                ])
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

    // public static function getRelations(): array
    // {
    //     return [
    //         RelationManagers\ImagesRelationManager::class,
    //     ];
    // }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProducts::route('/'),
            'create' => Pages\CreateProduct::route('/create'),
            // 'view' => Pages\ViewProduct::route('/{record}'),
            'edit' => Pages\EditProduct::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes([
            SoftDeletingScope::class,
        ]);
    }
}
