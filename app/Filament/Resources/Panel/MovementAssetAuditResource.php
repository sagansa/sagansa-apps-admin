<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Asset;
use App\Filament\Forms\ImageInput;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\MovementAssetAudit;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\Panel\MovementAssetAuditResource\Pages;

class MovementAssetAuditResource extends Resource
{
    protected static ?string $model = MovementAssetAudit::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Asset::class;


    public static function getModelLabel(): string
    {
        return __('crud.movementAssetAudits.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.movementAssetAudits.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.movementAssetAudits.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    ImageInput::make('image')

                        ->directory('images/MovementAssetAudit'),

                    Select::make('movement_asset_id')
                        ->required()
                        ->relationship('movementAsset', 'image')
                        ->searchable()
                        ->preload(),

                    TextInput::make('good_cond_qty')
                        ->required()
                        ->numeric()
                        ->step(1),

                    TextInput::make('bad_cond_qty')
                        ->required()
                        ->numeric()
                        ->step(1),

                    Select::make('movement_asset_result_id')
                        ->required()
                        ->relationship('movementAssetResult', 'date')
                        ->searchable()
                        ->preload(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('movementAsset.image'),

                TextColumn::make('good_cond_qty'),

                TextColumn::make('bad_cond_qty'),

                TextColumn::make('movementAssetResult.date'),
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
            'index' => Pages\ListMovementAssetAudits::route('/'),
            'create' => Pages\CreateMovementAssetAudit::route('/create'),
            'view' => Pages\ViewMovementAssetAudit::route('/{record}'),
            'edit' => Pages\EditMovementAssetAudit::route('/{record}/edit'),
        ];
    }
}
