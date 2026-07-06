<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Stock;
use App\Filament\Forms\TransferCardForm;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\TransferCardStore;
use App\Filament\Resources\Panel\TransferCardStoreResource\Pages;
use App\Filament\Tables\TransferCardTable;
use App\Filament\Tables\ValidAction;
use Illuminate\Support\Facades\Auth;

class TransferCardStoreResource extends Resource
{
    protected static ?string $model = TransferCardStore::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Stock::class;


    public static function getModelLabel(): string
    {
        return __('crud.transferCardStores.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.transferCardStores.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.transferCardStores.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema(
            TransferCardForm::getTransferCardStore(),
        );
    }

    public static function table(Table $table): Table
    {

        $transferCardStore = TransferCardStore::query();

        if (Auth::user()->hasRole('staff') || Auth::user()->hasRole('supervisor')) {
            $transferCardStore->where(function($query) {
                $query->where('received_by_id', Auth::id())
                    ->orWhere('sent_by_id', Auth::id());
            });
        }

        $transferCardStore->where('for', 'store');

        return $table
            ->poll('60s')
            ->query($transferCardStore)
            ->columns(
                TransferCardTable::schema(TransferCardStore::class)
                )
            ->filters([])
            ->actions(ValidAction::getAction(self::$model)['actions'])
            ->bulkActions(ValidAction::getAction(self::$model)['bulkActions'])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTransferCardStores::route('/'),
            'create' => Pages\CreateTransferCardStore::route('/create'),
            'view' => Pages\ViewTransferCardStore::route('/{record}'),
            'edit' => Pages\EditTransferCardStore::route('/{record}/edit'),
        ];
    }
}
