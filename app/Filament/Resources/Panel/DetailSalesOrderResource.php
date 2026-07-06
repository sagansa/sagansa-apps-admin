<?php

namespace App\Filament\Resources\Panel;

use App\Models\DetailSalesOrder;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use App\Filament\Resources\Panel\DetailSalesOrderResource\Pages;

use App\Filament\Clusters\Sales;

class DetailSalesOrderResource extends Resource
{
    protected static ?string $model = DetailSalesOrder::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-list-bullet';


    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = Sales::class;

    public static function getModelLabel(): string
    {
        return 'Detail Sales Order';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Detail Sales Orders';
    }

    // Hanya admin yang boleh mengakses resource ini
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            // Biasanya detail sales order bersifat read-only jika dilihat secara terpisah
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('sales_order_id')
                    ->label('Order ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quantity')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('subtotal_price')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('product_id')
                    ->relationship('product', 'name')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                // Biasanya tidak ada bulk action untuk auditing detail
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDetailSalesOrders::route('/'),
        ];
    }
}
