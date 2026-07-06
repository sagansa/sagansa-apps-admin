<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Asset;
use App\Filament\Clusters\Movements;
use App\Filament\Forms\StoreSelect;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\MovementAssetResult;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use App\Filament\Resources\Panel\MovementAssetResultResource\Pages;
use App\Filament\Resources\Panel\MovementAssetResultResource\RelationManagers;

class MovementAssetResultResource extends Resource
{
    protected static ?string $model = MovementAssetResult::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Asset::class;


    public static function getModelLabel(): string
    {
        return __('crud.movementAssetResults.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.movementAssetResults.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.movementAssetResults.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    StoreSelect::make('store_id'),

                    DatePicker::make('date')
                        ->rules(['date'])
                        ->required(),

                    Select::make('status')
                        ->required()
                        ->searchable()
                        ->preload(),

                    Select::make('user_id')
                        ->required()
                        ->relationship('user', 'name')
                        ->searchable()
                        ->preload(),

                    RichEditor::make('notes')
                        ->nullable()
                        ->string()
                        ->fileAttachmentsVisibility('public'),
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

                TextColumn::make('user.name'),

                TextColumn::make('notes')->limit(255),
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
            'index' => Pages\ListMovementAssetResults::route('/'),
            'create' => Pages\CreateMovementAssetResult::route('/create'),
            'view' => Pages\ViewMovementAssetResult::route('/{record}'),
            'edit' => Pages\EditMovementAssetResult::route('/{record}/edit'),
        ];
    }
}
