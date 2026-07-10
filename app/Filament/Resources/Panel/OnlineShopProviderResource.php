<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Transaction\Settings;
use App\Filament\Forms\BaseTextInput;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\OnlineShopProvider;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\OnlineShopProviderResource\Pages;
use App\Filament\Resources\Panel\OnlineShopProviderResource\RelationManagers;
use Filament\Actions\ActionGroup;

class OnlineShopProviderResource extends Resource
{
    protected static ?string $model = OnlineShopProvider::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 60;


    protected static ?string $cluster = Settings::class;

    protected static ?string $pluralLabel = 'Online Shop Providers';

    public static function getModelLabel(): string
    {
        return __('crud.onlineShopProviders.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.onlineShopProviders.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.onlineShopProviders.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    BaseTextInput::make('name'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([TextColumn::make('name')->searchable()])
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
            'index' => Pages\ListOnlineShopProviders::route('/'),
            'create' => Pages\CreateOnlineShopProvider::route('/create'),
            'view' => Pages\ViewOnlineShopProvider::route('/{record}'),
            'edit' => Pages\EditOnlineShopProvider::route('/{record}/edit'),
        ];
    }
}
