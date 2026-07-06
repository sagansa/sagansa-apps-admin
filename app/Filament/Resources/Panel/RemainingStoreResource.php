<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Stock;
use App\Filament\Filters\SelectStoreFilter;
use App\Filament\Forms\StockCardForm;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\RemainingStore;
use Filament\Resources\Resource;
use App\Filament\Resources\Panel\RemainingStoreResource\Pages;
use App\Filament\Resources\Panel\RemainingStoreResource\RelationManagers;
use App\Filament\Tables\StockCardTable;
use App\Filament\Tables\ValidAction;
use Illuminate\Support\Facades\Auth;

class RemainingStoreResource extends Resource
{
    protected static ?string $model = RemainingStore::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Stock::class;


    public static function getModelLabel(): string
    {
        return __('crud.remainingStores.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.remainingStores.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.remainingStores.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema(
            StockCardForm::getStockCardRemaining(),
        );
    }

    public static function table(Table $table): Table
    {
        $query = RemainingStore::query();

        if (Auth::user()->hasRole('staff')) {
            $query->where('user_id', Auth::id());
        }

        $query->where('for', 'remaining_store');

        return $table
            ->poll('60s')
            ->query($query)
            ->columns(
                StockCardTable::schema(RemainingStore::class),
            )
            ->filters([SelectStoreFilter::make('store_id')])
            ->actions(ValidAction::getAction(self::$model)['actions'])
            ->bulkActions(ValidAction::getAction(self::$model)['bulkActions'])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListRemainingStores::route('/'),
            'create' => Pages\CreateRemainingStore::route('/create'),
            'view' => Pages\ViewRemainingStore::route('/{record}'),
            'edit' => Pages\EditRemainingStore::route('/{record}/edit'),
        ];
    }
}
