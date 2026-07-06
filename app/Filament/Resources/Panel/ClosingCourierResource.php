<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Closings;
use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\StatusColumn;
use App\Filament\Forms\CurrencyInput;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\Notes;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\ClosingCourier;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\ClosingCourierResource\Pages;
use App\Models\ClosingStore;
use Filament\Actions\ActionGroup;
use Illuminate\Support\Facades\Auth;

class ClosingCourierResource extends Resource
{
    protected static ?string $model = ClosingCourier::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;


    protected static ?string $pluralLabel = 'Courier';

    protected static ?string $cluster = Closings::class;

    public static function getModelLabel(): string
    {
        return __('crud.closingCouriers.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.closingCouriers.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.closingCouriers.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    ImageInput::make('image')

                        ->directory('images/ClosingCourier'),

                    Select::make('bank_id')
                        ->required()
                        ->relationship('bank', 'name')
                        ->preload(),

                    CurrencyInput::make('total_cash_to_transfer')
                        ->label('Total Cash to Transfer'),
                ]),
            ]),

            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Select::make('closingStores')
                        ->multiple()
                        ->relationship(
                            name: 'closingStores',
                            modifyQueryUsing: fn(Builder $query, $get) => $query
                                ->where('transfer_by_id', Auth::id())
                            // ->where('status', '1')
                            // ->when($get('store_id'), fn ($query, $storeId) => $query->where('store_id', $storeId)) // Menggunakan store_id yang dipilih
                        )
                        ->getOptionLabelFromRecordUsing(fn(ClosingStore $record) => "{$record->closing_store_name}")
                        ->preload()
                        ->reactive(),
                    // ->afterStateUpdated(function ($state, $set) {
                    //     $totalAmount = 0;
                    //     foreach ($state as $fuelServiceId) {
                    //         $fuelService = ClosingStore::find($fuelServiceId);
                    //         if ($fuelService) {
                    //             $fuelService->status = 2;
                    //             $fuelService->save();
                    //             $totalAmount += $fuelService->amount;
                    //         }
                    //     }
                    //     $set('total_amount', $totalAmount);
                    // }),
                ])
            ]),

            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Select::make('status')
                        ->required()
                        ->required(fn() => Auth::user()->hasRole('admin'))
                        ->hidden(fn($operation) => $operation === 'create')
                        ->disabled(fn() => Auth::user()->hasRole('staff'))
                        ->preload()
                        ->options([
                            '1' => 'belum diperiksa',
                            '2' => 'valid',
                            '3' => 'diperbaiki',
                            '4' => 'periksa ulang',
                        ]),

                    Notes::make('notes'),
                ])
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $query = ClosingCourier::query();

        if (!Auth::user()->hasRole('admin') && !Auth::user()->hasRole('super_admin')) {
            $query->where('created_by_id', Auth::id());
        }

        return $table
            ->query($query)
            ->poll('60s')
            ->columns([
                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('created_at'),

                TextColumn::make('bank.name'),

                CurrencyColumn::make('total_cash_to_transfer'),

                TextColumn::make('createdBy.name'),

                StatusColumn::make('status'),
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
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListClosingCouriers::route('/'),
            'create' => Pages\CreateClosingCourier::route('/create'),
            'view' => Pages\ViewClosingCourier::route('/{record}'),
            'edit' => Pages\EditClosingCourier::route('/{record}/edit'),
        ];
    }
}
