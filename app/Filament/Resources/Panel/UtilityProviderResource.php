<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Utilities;
use App\Filament\Forms\BaseTextInput;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\UtilityProvider;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\UtilityProviderResource\Pages;
use App\Filament\Resources\Panel\UtilityProviderResource\RelationManagers;
use Filament\Actions\ActionGroup;

class UtilityProviderResource extends Resource
{
    protected static ?string $model = UtilityProvider::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 10;


    protected static ?string $cluster = Utilities::class;

    protected static ?string $pluralLabel = 'Utulity Providers';

    public static function getModelLabel(): string
    {
        return __('crud.utilityProviders.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.utilityProviders.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.utilityProviders.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    BaseTextInput::make('name'),

                    Select::make('category')
                        ->options([
                            '1' => 'listrik',
                            '2' => 'air',
                            '3' => 'internet'
                        ])
                        ->required()
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
                TextColumn::make('name')->searchable(),

                TextColumn::make('category')
                    ->formatStateUsing(
                        fn(string $state): string => match ($state) {
                            '1' => 'listrik',
                            '2' => 'air',
                            '3' => 'internet'
                        }
                    ),
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
            'index' => Pages\ListUtilityProviders::route('/'),
            'create' => Pages\CreateUtilityProvider::route('/create'),
            'view' => Pages\ViewUtilityProvider::route('/{record}'),
            'edit' => Pages\EditUtilityProvider::route('/{record}/edit'),
        ];
    }
}
