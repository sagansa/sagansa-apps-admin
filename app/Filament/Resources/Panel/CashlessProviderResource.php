<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Cashless;
use App\Filament\Forms\BaseTextInput;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\CashlessProvider;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Panel\CashlessProviderResource\Pages;
use Filament\Actions\ActionGroup;

class CashlessProviderResource extends Resource
{
    protected static ?string $model = CashlessProvider::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 10;

    protected static ?string $cluster = Cashless::class;


    public static function getModelLabel(): string
    {
        return __('crud.cashlessProviders.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.cashlessProviders.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.cashlessProviders.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    BaseTextInput::make('name')
                        ->string()
                        ->unique(
                            'cashless_providers',
                            'name',
                            ignoreRecord: true
                        )
                        ->autofocus(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([TextColumn::make('name')])
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
            'index' => Pages\ListCashlessProviders::route('/'),
            'create' => Pages\CreateCashlessProvider::route('/create'),
            'view' => Pages\ViewCashlessProvider::route('/{record}'),
            'edit' => Pages\EditCashlessProvider::route('/{record}/edit'),
        ];
    }
}
