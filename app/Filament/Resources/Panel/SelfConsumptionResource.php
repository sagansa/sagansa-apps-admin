<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Stock;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\Notes;
use App\Filament\Forms\StoreSelect;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\SelfConsumption;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use App\Filament\Resources\Panel\SelfConsumptionResource\Pages;
use App\Filament\Resources\Panel\SelfConsumptionResource\RelationManagers;

class SelfConsumptionResource extends Resource
{
    protected static ?string $model = SelfConsumption::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Stock::class;


    public static function getModelLabel(): string
    {
        return __('crud.selfConsumptions.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.selfConsumptions.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.selfConsumptions.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    StoreSelect::make('store_id'),

                    DateInput::make('date'),

                    Select::make('status')
                        ->required()
                        ->default('1')
                        ->searchable()
                        ->preload()
                        ->options([
                            '1' => 'belum diperiksa',
                            '2' => 'valid',
                            '3' => 'diperbaiki',
                            '4' => 'periksa ulang',
                        ]),

                    Notes::make('notes'),

                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('store.name'),

                TextColumn::make('date')->since(),

                TextColumn::make('status'),

                TextColumn::make('created_by_id'),

                TextColumn::make('createdBy.name'),
            ])
            ->filters([])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\ViewAction::make(),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSelfConsumptions::route('/'),
            'create' => Pages\CreateSelfConsumption::route('/create'),
            'view' => Pages\ViewSelfConsumption::route('/{record}'),
            'edit' => Pages\EditSelfConsumption::route('/{record}/edit'),
        ];
    }
}
