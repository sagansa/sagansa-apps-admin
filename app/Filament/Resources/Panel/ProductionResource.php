<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Stock;
use App\Filament\Columns\StatusColumn;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\Notes;
use App\Filament\Forms\StatusSelectInput;
use App\Filament\Forms\StoreSelect;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\Production;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use App\Filament\Resources\Panel\ProductionResource\Pages;
use App\Filament\Resources\Panel\ProductionResource\RelationManagers;
use Filament\Actions\ActionGroup;

class ProductionResource extends Resource
{
    protected static ?string $model = Production::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Stock::class;


    public static function getModelLabel(): string
    {
        return __('crud.productions.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.productions.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.productions.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    StoreSelect::make('store_id'),

                    DateInput::make('date'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('store.nickname'),

                TextColumn::make('date'),

                StatusColumn::make('status'),

                TextColumn::make('createdBy.name'),

                TextColumn::make('approvedBy.name'),
            ])
            ->filters([])
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
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\ProductionMainFromsRelationManager::class,
            RelationManagers\ProductionSupportFromsRelationManager::class,
            RelationManagers\ProductionTosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProductions::route('/'),
            'create' => Pages\CreateProduction::route('/create'),
            'view' => Pages\ViewProduction::route('/{record}'),
            'edit' => Pages\EditProduction::route('/{record}/edit'),
        ];
    }
}
