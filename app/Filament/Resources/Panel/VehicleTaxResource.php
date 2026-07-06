<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Asset;
use App\Filament\Clusters\Vehicles;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\CurrencyInput;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\Notes;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\VehicleTax;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use App\Filament\Resources\Panel\VehicleTaxResource\Pages;
use App\Filament\Resources\Panel\VehicleTaxResource\RelationManagers;
use Filament\Actions\ActionGroup;

class VehicleTaxResource extends Resource
{
    protected static ?string $model = VehicleTax::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Asset::class;


    public static function getModelLabel(): string
    {
        return __('crud.vehicleTaxes.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.vehicleTaxes.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.vehicleTaxes.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    ImageInput::make('image')
                        ->directory('images/VehicleTax'),

                    BaseSelect::make('vehicle_id')
                        ->relationship('vehicle', 'no_register')
                        ->searchable(),

                    CurrencyInput::make('amount_tax'),

                    DateInput::make('expired_date'),

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
                ImageOpenUrlColumn::make('image')->visibility('public'),

                TextColumn::make('amount_tax'),

                TextColumn::make('vehicle.image'),

                TextColumn::make('expired_date')->since(),

                TextColumn::make('user.name'),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListVehicleTaxes::route('/'),
            'create' => Pages\CreateVehicleTax::route('/create'),
            'view' => Pages\ViewVehicleTax::route('/{record}'),
            'edit' => Pages\EditVehicleTax::route('/{record}/edit'),
        ];
    }
}
