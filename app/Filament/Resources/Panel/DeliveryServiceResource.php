<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Transaction\Settings;
use App\Filament\Columns\ActiveColumn;
use App\Filament\Forms\ActiveStatusSelect;
use App\Filament\Forms\BaseTextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\DeliveryService;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Panel\DeliveryServiceResource\Pages;
use Filament\Actions\ActionGroup;

class DeliveryServiceResource extends Resource
{
    protected static ?string $model = DeliveryService::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 70;


    protected static ?string $pluralLabel = 'Delivery Services';

    protected static ?string $cluster = Settings::class;

    public static function getModelLabel(): string
    {
        return __('crud.deliveryServices.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.deliveryServices.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.deliveryServices.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    BaseTextInput::make('name'),

                    ActiveStatusSelect::make('status'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([TextColumn::make('name')->searchable(), ActiveColumn::make('status')])
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
            'index' => Pages\ListDeliveryServices::route('/'),
            'create' => Pages\CreateDeliveryService::route('/create'),
            'view' => Pages\ViewDeliveryService::route('/{record}'),
            'edit' => Pages\EditDeliveryService::route('/{record}/edit'),
        ];
    }
}
