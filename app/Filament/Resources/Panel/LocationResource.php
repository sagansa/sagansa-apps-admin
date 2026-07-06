<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Store;
use App\Filament\Forms\StoreSelect;
use Filament\Schemas\Schema;
use App\Models\Location;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\Panel\LocationResource\Pages;

class LocationResource extends Resource
{
    protected static ?string $model = Location::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Store::class;


    public static function getModelLabel(): string
    {
        return __('crud.locations.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.locations.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.locations.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    TextInput::make('name')
                        ->required()
                        ->string()
                        ->autofocus(),

                    StoreSelect::make('store_id'),

                    TextInput::make('contact_person_name')
                        ->required()
                        ->string(),

                    TextInput::make('contact_person_number')
                        ->required()
                        ->string(),

                    TextInput::make('address')
                        ->required()
                        ->string(),

                    Select::make('province_id')
                        ->required()
                        ->relationship('province', 'name')
                        ->searchable()
                        ->preload()
                        ,

                    Select::make('city_id')
                        ->required()
                        ->relationship('city', 'name')
                        ->searchable()
                        ->preload()
                        ,

                    Select::make('district_id')
                        ->required()
                        ->relationship('district', 'name')
                        ->searchable()
                        ->preload()
                        ,

                    Select::make('subdistrict_id')
                        ->required()
                        ->relationship('subdistrict', 'name')
                        ->searchable()
                        ->preload()
                        ,

                    Select::make('postal_code_id')
                        ->required()
                        ->relationship('postalCode', 'id')
                        ->searchable()
                        ->preload()
                        ,
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('name'),

                TextColumn::make('store.name'),

                TextColumn::make('contact_person_name'),

                TextColumn::make('contact_person_number'),

                TextColumn::make('address'),

                TextColumn::make('province.name'),

                TextColumn::make('city.name'),

                TextColumn::make('district.name'),

                TextColumn::make('subdistrict.name'),

                TextColumn::make('postalCode.id'),
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
            'index' => Pages\ListLocations::route('/'),
            'create' => Pages\CreateLocation::route('/create'),
            'view' => Pages\ViewLocation::route('/{record}'),
            'edit' => Pages\EditLocation::route('/{record}/edit'),
        ];
    }
}
