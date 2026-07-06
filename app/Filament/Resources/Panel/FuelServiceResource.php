<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Purchases;
use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\CurrencyInput;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\DecimalInput;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\NominalInput;
use App\Filament\Forms\Notes;
use App\Filament\Forms\PaymentStatusSelectInput;
use App\Filament\Forms\SupplierSelect;
use App\Filament\Forms\StoreSelect;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\FuelService;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\FuelServiceResource\Pages;
use App\Filament\Tables\FuelServiceTable;
use App\Models\PaymentType;
use App\Models\Vehicle;
use Filament\Forms\Components\Radio;
use Filament\Actions\ActionGroup;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;

class FuelServiceResource extends Resource
{
    protected static ?string $model = FuelService::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    protected static ?string $cluster = Purchases::class;

    // protected static string|\UnitEnum|null $navigationGroup = 'Purchase';

    public static function getModelLabel(): string
    {
        return __('crud.fuelServices.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.fuelServices.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.fuelServices.collectionTitle');
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        
        if (auth()->check() && auth()->user()->hasRole('staff')) {
            $query->where('created_by_id', auth()->id());
        }
        
        return $query;
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([

                Grid::make(['default' => 2])->schema([

                    ImageInput::make('image')
                        ->directory('images/FuelService'),

                    SupplierSelect::make('supplier_id'),

                    DateInput::make('date'),

                    Radio::make('fuel_service')
                        ->inline()
                        ->inlineLabel()
                        ->required()
                        ->options([
                            '1' => 'fuel',
                            '2' => 'service',
                        ])
                        ->reactive()
                        ->afterStateUpdated(function ($state, Set $set) {
                            if ($state == 1) {
                                $set('service_details', []);
                                $set('amount', 0);
                            }
                        }),

                    BaseSelect::make('vehicle_id')
                        ->required()
                        ->relationship(
                            name: 'vehicle',
                            modifyQueryUsing: fn (Builder $query) => $query->where('status', '1'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (Vehicle $record) => "{$record->no_register}")
                        ->searchable()
                        ->preload(),

                    StoreSelect::make('store_id')
                        ->label('Store Dibebankan')
                        ->nullable(),

                    BaseSelect::make('payment_type_id')
                        ->relationship(
                            name: 'paymentType',
                            modifyQueryUsing: fn (Builder $query) => $query->where('status', '1'),
                        )
                        ->getOptionLabelFromRecordUsing(fn (PaymentType $record) => "{$record->name}"),

                    NominalInput::make('km')
                        ->label('km')
                        ->suffix('km'),

                    DecimalInput::make('liter')
                        ->suffix('liter'),

                    CurrencyInput::make('amount')
                        ->readonly(fn (Get $get) => $get('fuel_service') == 2),

                    Repeater::make('service_details')
                        ->label('Detail Service')
                        ->schema([
                            TextInput::make('name')
                                ->label('Nama Service/Part')
                                ->required(),
                            CurrencyInput::make('price')
                                ->label('Biaya')
                                ->required()
                                ->reactive()
                                ->afterStateUpdated(function (Get $get, Set $set) {
                                    $set('../../amount', collect($get('../../service_details') ?? [])->sum('price'));
                                }),
                        ])
                        ->live()
                        ->afterStateUpdated(function (Get $get, Set $set) {
                            $set('amount', collect($get('service_details') ?? [])->sum('price'));
                        })
                        ->visible(fn (Get $get) => $get('fuel_service') == 2)
                        ->columnSpanFull(),

                    PaymentStatusSelectInput::make('status'),

                ]),

                Grid::make(['default' => 1])->schema([
                    Notes::make('notes'),
                ])
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $query = FuelService::query();

        if(!Auth::user()->hasRole('admin')) {
            $query->where('created_by_id', Auth::id());
        }

        return $table
            ->query($query)
            ->poll('60s')
            ->columns(
                FuelServiceTable::schema()
            )
            ->filters([
                SelectFilter::make('payment_type_id')
                    ->label('Payment Type')
                    ->options([
                        '1' => 'transfer',
                        '2' => 'tunai',
                    ]),

                SelectFilter::make('vehicle_id')
                    ->label('Vehicle')
                    ->relationship(
                        name: 'vehicle',
                        titleAttribute: 'no_register',
                        modifyQueryUsing: fn (Builder $query) => $query,
                    )
                    ->getOptionLabelFromRecordUsing(fn (Vehicle $record) => "{$record->vehicle_status}")
                    ->searchable()
                    ->preload()
                    ,

                SelectFilter::make('fuel_service')
                    ->label('Fuel Service')
                    ->options([
                        '1' => 'fuel',
                        '2' => 'service',
                    ]),
            ])
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFuelServices::route('/'),
            'create' => Pages\CreateFuelService::route('/create'),
            'view' => Pages\ViewFuelService::route('/{record}'),
            'edit' => Pages\EditFuelService::route('/{record}/edit'),
        ];
    }
}
