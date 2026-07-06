<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Transaction\Settings;
use App\Filament\Forms\Notes;
use App\Filament\Forms\StatusSelectInput;
use App\Filament\Forms\StoreSelect;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\AccountCashless;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\Panel\AccountCashlessResource\Pages;
use Filament\Actions\ActionGroup;
use Filament\Tables\Filters\SelectFilter;

class AccountCashlessResource extends Resource
{
    protected static ?string $model = AccountCashless::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Settings::class;


    public static function getModelLabel(): string
    {
        return __('crud.accountCashlesses.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.accountCashlesses.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.accountCashlesses.collectionTitle');
    }

    public static function form(Schema $schema): Schema
    {
        return $schema->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Select::make('cashless_provider_id')
                        ->required()
                        ->relationship('cashlessProvider', 'name')
                        ->searchable()
                        ->preload(),

                    StoreSelect::make('store_id'),

                    Select::make('store_cashless_id')
                        ->required()
                        ->relationship('storeCashless', 'name')
                        ->searchable()
                        ->preload(),

                    TextInput::make('email')
                        ->nullable()
                        ->string()
                        ->email(),

                    TextInput::make('username')
                        ->nullable()
                        ->string(),

                    TextInput::make('password')
                        ->nullable()
                        ->string()
                        ->password(),

                    TextInput::make('no_telp')
                        ->nullable()
                        ->string(),

                    StatusSelectInput::make('status'),

                    Notes::make('notes'),
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
            ->filters([
                SelectFilter::make('store_id')
                    ->relationship('store', 'nickname')
            ])
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
            'index' => Pages\ListAccountCashlesses::route('/'),
            'create' => Pages\CreateAccountCashless::route('/create'),
            'view' => Pages\ViewAccountCashless::route('/{record}'),
            'edit' => Pages\EditAccountCashless::route('/{record}/edit'),
        ];
    }
}
