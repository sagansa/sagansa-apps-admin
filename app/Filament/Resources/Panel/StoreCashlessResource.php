<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Cashless;
use App\Filament\Columns\ActiveColumn;
use App\Filament\Forms\ActiveStatusSelect;
use App\Filament\Forms\BaseTextInput;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\StoreCashless;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\StoreCashlessResource\Pages;
use App\Filament\Resources\Panel\StoreCashlessResource\RelationManagers;
use Filament\Actions\ActionGroup;

class StoreCashlessResource extends Resource
{
    protected static ?string $model = StoreCashless::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 40;

    protected static ?string $cluster = Cashless::class;


    public static function getModelLabel(): string
    {
        return __('crud.storeCashlesses.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.storeCashlesses.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.storeCashlesses.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
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
            'index' => Pages\ListStoreCashlesses::route('/'),
            'create' => Pages\CreateStoreCashless::route('/create'),
            'view' => Pages\ViewStoreCashless::route('/{record}'),
            'edit' => Pages\EditStoreCashless::route('/{record}/edit'),
        ];
    }
}
