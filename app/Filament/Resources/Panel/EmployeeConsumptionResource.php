<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Stock;
use App\Filament\Filters\SelectStoreFilter;
use App\Filament\Forms\StockCardForm;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\EmployeeConsumption;
use App\Filament\Resources\Panel\EmployeeConsumptionResource\Pages;
use App\Filament\Tables\StockCardTable;
use App\Filament\Tables\ValidAction;

use Illuminate\Support\Facades\Auth;

class EmployeeConsumptionResource extends Resource
{
    protected static ?string $model = EmployeeConsumption::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Stock::class;


    public static function getModelLabel(): string
    {
        return __('crud.employeeConsumptions.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.employeeConsumptions.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.employeeConsumptions.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema(
            StockCardForm::getStockCardRemaining(),
        );
    }

    public static function table(Table $table): Table
    {
        $query = EmployeeConsumption::query();

        if (Auth::user()->hasRole('staff')) {
            $query->where('user_id', Auth::id());
        }

        $query->where('for', 'employee_consumption');

        return $table
            ->query($query)
            ->poll('60s')
            ->columns(
                StockCardTable::schema(EmployeeConsumption::class)
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
            'index' => Pages\ListEmployeeConsumptions::route('/'),
            'create' => Pages\CreateEmployeeConsumption::route('/create'),
            'view' => Pages\ViewEmployeeConsumption::route('/{record}'),
            'edit' => Pages\EditEmployeeConsumption::route('/{record}/edit'),
        ];
    }
}
