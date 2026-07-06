<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Cashlesses;
use App\Filament\Clusters\Transaction\Settings;
use App\Filament\Forms\BaseTextInput;
use App\Filament\Forms\StoreSelect;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\UserCashless;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\UserCashlessResource\Pages;
use App\Filament\Resources\Panel\UserCashlessResource\RelationManagers;
use Filament\Actions\ActionGroup;

class UserCashlessResource extends Resource
{
    protected static ?string $model = UserCashless::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Settings::class;


    public static function getModelLabel(): string
    {
        return __('crud.userCashlesses.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.userCashlesses.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.userCashlesses.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Select::make('cashless_provider_id')
                        ->required()
                        ->relationship('cashlessProvider', 'name')
                        ->searchable()
                        ->preload(),

                    StoreSelect::make('store_id')
                        ->required(),

                    Select::make('store_cashless_id')
                        ->required()
                        ->relationship('storeCashless', 'name')
                        ->searchable()
                        ->preload(),

                    BaseTextInput::make('email')
                        ->email(),

                    BaseTextInput::make('username'),

                    TextInput::make('password')
                        ->required(
                            fn(string $context): bool => $context === 'create'
                        )
                        ->dehydrated(fn($state) => filled($state))
                        ->string()
                        ->minLength(6)
                        ->password(),

                    BaseTextInput::make('no_telp'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('cashlessProvider.name'),

                TextColumn::make('store.nickname'),

                TextColumn::make('storeCashless.name'),

                TextColumn::make('email'),

                TextColumn::make('username'),

                TextColumn::make('password'),

                TextColumn::make('no_telp'),
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
            'index' => Pages\ListUserCashlesses::route('/'),
            'create' => Pages\CreateUserCashless::route('/create'),
            'view' => Pages\ViewUserCashless::route('/{record}'),
            'edit' => Pages\EditUserCashless::route('/{record}/edit'),
        ];
    }
}
