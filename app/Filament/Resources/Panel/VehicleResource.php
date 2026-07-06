<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Asset;
use App\Filament\Clusters\Vehicles;
use App\Filament\Columns\ActiveColumn;
use App\Filament\Forms\ActiveStatusSelect;
use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\BaseTextInput;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\Notes;
use App\Filament\Forms\StoreSelect;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use App\Models\Vehicle;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\Panel\VehicleResource\Pages;
use App\Filament\Resources\Panel\VehicleResource\RelationManagers;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Auth;
use Filament\Schemas\Components\Group;

class VehicleResource extends Resource
{
    protected static ?string $model = Vehicle::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Asset::class;


    public static function getModelLabel(): string
    {
        return __('crud.vehicles.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.vehicles.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.vehicles.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form
            ->columns(['default' => 1, 'lg' => 3])
            ->schema([
                Group::make([
                    Section::make('Informasi Kendaraan')
                        ->icon('heroicon-o-truck')
                        ->description('Detail spesifikasi dan registrasi kendaraan')
                        ->schema([
                            Grid::make(2)->schema([
                                BaseTextInput::make('no_register')
                                    ->label('Nomor Polisi / Register')
                                    ->required(),
                                    
                                BaseSelect::make('type')
                                    ->label('Tipe Kendaraan')
                                    ->options([
                                        '1' => 'Motor',
                                        '2' => 'Mobil',
                                        '3' => 'Truk',
                                    ])
                                    ->required(),
                            ]),

                            StoreSelect::make('store_id')
                                ->label('Lokasi (Store)'),

                            Notes::make('notes')
                                ->label('Catatan'),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Group::make([
                    Section::make('Media')
                        ->icon('heroicon-o-photo')
                        ->schema([
                            ImageInput::make('image')
                                ->label('Foto Kendaraan')
                                ->directory('images/Vehicle'),
                        ])
                        ->collapsible(),

                    Section::make('Status')
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            ActiveStatusSelect::make('status')
                                ->label('Status Operasional')
                                ->default('1'),
                        ])
                        ->collapsible(),
                ])->columnSpan(['lg' => 1]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('no_register'),

                TextColumn::make('store.nickname'),

                ActiveColumn::make('status'),
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
            'index' => Pages\ListVehicles::route('/'),
            'create' => Pages\CreateVehicle::route('/create'),
            'view' => Pages\ViewVehicle::route('/{record}'),
            'edit' => Pages\EditVehicle::route('/{record}/edit'),
        ];
    }
}
