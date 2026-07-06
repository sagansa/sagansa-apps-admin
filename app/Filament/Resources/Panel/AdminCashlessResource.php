<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Transaction\Settings;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\AdminCashless;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use App\Filament\Resources\Panel\AdminCashlessResource\Pages;
use Filament\Actions\ActionGroup;
use Filament\Schemas\Components\Grid;

class AdminCashlessResource extends Resource
{
    protected static ?string $model = AdminCashless::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Settings::class;


    public static function getModelLabel(): string
    {
        return __('crud.adminCashlesses.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.adminCashlesses.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.adminCashlesses.collectionTitle');
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

                    TextInput::make('username')
                        ->nullable()
                        ->string(),

                    TextInput::make('email')
                        ->nullable()
                        ->string()
                        ->email(),

                    TextInput::make('no_telp')
                        ->nullable()
                        ->string(),

                    TextInput::make('password')
                        ->nullable()
                        ->string()
                        ->minLength(6)
                        ->password(),
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

                TextColumn::make('username'),

                TextColumn::make('email'),

                TextColumn::make('no_telp'),

                TextColumn::make('password'),
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
            'index' => Pages\ListAdminCashlesses::route('/'),
            'create' => Pages\CreateAdminCashless::route('/create'),
            'view' => Pages\ViewAdminCashless::route('/{record}'),
            'edit' => Pages\EditAdminCashless::route('/{record}/edit'),
        ];
    }
}
