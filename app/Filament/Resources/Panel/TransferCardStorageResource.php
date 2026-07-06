<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Stock;
use App\Filament\Forms\TransferCardForm;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use App\Models\TransferCardStorage;
use App\Filament\Resources\Panel\TransferCardStorageResource\Pages;
use App\Filament\Tables\TransferCardTable;
use App\Filament\Tables\ValidAction;
use Illuminate\Support\Facades\Auth;

class TransferCardStorageResource extends Resource
{
    protected static ?string $model = TransferCardStorage::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = Stock::class;


    public static function getModelLabel(): string
    {
        return __('crud.transferCardStorages.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.transferCardStorages.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.transferCardStorages.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema(TransferCardForm::getTransferCardStorage(),);
    }

    public static function table(Table $table): Table
    {
        $transferCardStorage = TransferCardStorage::query();

        if (Auth::user()->hasRole('staff') || Auth::user()->hasRole('supervisor')) {
            $transferCardStorage->where(function($query) {
                $query->where('received_by_id', Auth::id())
                    ->orWhere('sent_by_id', Auth::id());
            });
        }

        $transferCardStorage->where('for', 'storage');

        return $table
            ->poll('60s')
            ->query($transferCardStorage)
            ->columns(
                TransferCardTable::schema(TransferCardStorage::class)
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
            'index' => Pages\ListTransferCardStorages::route('/'),
            'create' => Pages\CreateTransferCardStorage::route('/create'),
            'view' => Pages\ViewTransferCardStorage::route('/{record}'),
            'edit' => Pages\EditTransferCardStorage::route('/{record}/edit'),
        ];
    }
}
