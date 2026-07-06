<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Asset;
use App\Filament\Clusters\Movements;
use App\Filament\Columns\ActiveColumn;
use App\Filament\Forms\ActiveStatusSelect;
use App\Filament\Forms\Notes;
use App\Filament\Forms\StoreSelect;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\StoreAsset;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\RichEditor;
use App\Filament\Resources\Panel\StoreAssetResource\Pages;
use App\Filament\Resources\Panel\StoreAssetResource\RelationManagers;
use Filament\Actions\ActionGroup;

class StoreAssetResource extends Resource
{
    protected static ?string $model = StoreAsset::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Asset::class;


    public static function getModelLabel(): string
    {
        return __('crud.storeAssets.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.storeAssets.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.storeAssets.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    StoreSelect::make('store_id'),

                    Notes::make('notes'),

                    ActiveStatusSelect::make('status'),
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
            'index' => Pages\ListStoreAssets::route('/'),
            'create' => Pages\CreateStoreAsset::route('/create'),
            'view' => Pages\ViewStoreAsset::route('/{record}'),
            'edit' => Pages\EditStoreAsset::route('/{record}/edit'),
        ];
    }
}
