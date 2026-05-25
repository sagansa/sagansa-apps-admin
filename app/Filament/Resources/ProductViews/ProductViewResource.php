<?php

namespace App\Filament\Resources\ProductViews;

use App\Filament\Resources\ProductViews\Pages\ManageProductViews;
use App\Models\ProductView;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use UnitEnum;
use BackedEnum;
use Filament\Schemas\Schema;

class ProductViewResource extends Resource
{
    protected static ?string $model = ProductView::class;

    protected static string|BackedEnum|null $navigationIcon = 'heroicon-o-eye';
    protected static string|UnitEnum|null $navigationGroup = 'Master Data';
    protected static ?string $modelLabel = 'Product View';
    protected static ?string $pluralModelLabel = 'Product Views';

    public static function form(Schema $form): Schema
    {
        return $form
            ->schema([
                // Read-only
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('user.name')
                    ->label('User')
                    ->searchable()
                    ->sortable()
                    ->default('Guest'),
                TextColumn::make('ip_address')
                    ->label('IP Address')
                    ->searchable(),
                TextColumn::make('user_agent')
                    ->label('User Agent')
                    ->limit(30),
                TextColumn::make('created_at')
                    ->label('Viewed At')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->recordActions([
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function canViewAny(): bool
    {
        return auth()->user()?->can('menu_master_data') ?? false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ManageProductViews::route('/'),
        ];
    }
}
