<?php

namespace App\Filament\Resources\Panel;

use App\Models\DetailInvoice;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Actions\ViewAction;
use App\Filament\Resources\Panel\DetailInvoiceResource\Pages;
use App\Filament\Clusters\Purchases;

class DetailInvoiceResource extends Resource
{
    protected static ?string $model = DetailInvoice::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-document-magnifying-glass';

    protected static string|\UnitEnum|null $navigationGroup = 'Invoice';

    protected static ?int $navigationSort = 11;

    protected static ?string $cluster = Purchases::class;

    public static function getModelLabel(): string
    {
        return 'Detail Invoice';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Detail Invoices';
    }

    // Hanya admin yang boleh mengakses resource ini
    public static function canAccess(): bool
    {
        return auth()->user()->hasRole('admin');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            // Read-only auditing
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoicePurchase.id')
                    ->label('Invoice ID')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('detailRequest.product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('quantity_product')
                    ->label('Qty (Model)')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('quantity_invoice')
                    ->label('Qty (Invoice)')
                    ->numeric()
                    ->sortable(),

                TextColumn::make('subtotal_invoice')
                    ->label('Subtotal')
                    ->money('IDR')
                    ->sortable(),

                // Menghitung harga satuan secara virtual
                TextColumn::make('unit_price')
                    ->label('Unit Price')
                    ->state(fn ($record): float => $record->quantity_product > 0 ? (float)$record->subtotal_invoice / (float)$record->quantity_product : 0)
                    ->money('IDR')
                    ->sortable(),

                TextColumn::make('invoicePurchase.date')
                    ->label('Date')
                    ->date()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('invoice_purchase_id')
                    ->relationship('invoicePurchase', 'id')
                    ->searchable()
                    ->preload(),
            ])
            ->actions([
                ViewAction::make(),
            ])
            ->bulkActions([
                //
            ])
            ->defaultSort('id', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDetailInvoices::route('/'),
        ];
    }
}
