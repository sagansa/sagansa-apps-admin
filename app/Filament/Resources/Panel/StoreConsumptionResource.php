<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Bulks\ValidBulkAction;
use App\Filament\Clusters\Stock;
use App\Filament\Columns\StatusColumn;
use App\Filament\Filters\SelectStoreFilter;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\StockCardForm;
use App\Filament\Forms\StockRepeaterForm;
use App\Filament\Forms\StoreSelect;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\StoreConsumption;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use App\Filament\Resources\Panel\StoreConsumptionResource\Pages;
use App\Filament\Resources\Panel\StoreConsumptionResource\RelationManagers;
use App\Filament\Tables\StockCardTable;
use App\Filament\Tables\ValidAction;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class StoreConsumptionResource extends Resource
{
    protected static ?string $model = StoreConsumption::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Stock::class;


    public static function getModelLabel(): string
    {
        return __('crud.storeConsumptions.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.storeConsumptions.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.storeConsumptions.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema(
            StockCardForm::getStockCardStorage(),
        );
    }

    public static function table(Table $table): Table
    {
        $query = StoreConsumption::query();

        if (Auth::user()->hasRole('staff')) {
            $query->where('user_id', Auth::id());
        }

        $query->where('for', 'store_consumption');

        return $table
            ->poll('60s')
            ->query($query)
            ->columns(
                StockCardTable::schema(StoreConsumption::class)
            )
            ->filters([
                SelectStoreFilter::make('store_id')
            ])
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
            'index' => Pages\ListStoreConsumptions::route('/'),
            'create' => Pages\CreateStoreConsumption::route('/create'),
            'view' => Pages\ViewStoreConsumption::route('/{record}'),
            'edit' => Pages\EditStoreConsumption::route('/{record}/edit'),
        ];
    }
}
