<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Cash;
use App\Filament\Columns\ActiveColumn;
use App\Filament\Forms\ActiveStatusSelect;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\PaymentType;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use App\Filament\Resources\Panel\PaymentTypeResource\Pages;
use App\Filament\Resources\Panel\PaymentTypeResource\RelationManagers;
use Filament\Actions\ActionGroup;

class PaymentTypeResource extends Resource
{
    protected static ?string $model = PaymentType::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 20;


    protected static ?string $cluster = Cash::class;

    protected static ?string $pluralLabel = 'Payment Types';

    public static function getModelLabel(): string
    {
        return __('crud.paymentTypes.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.paymentTypes.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.paymentTypes.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    TextInput::make('name')
                        ->required()
                        ->inlineLabel()
                        ->string()
                        ->autofocus(),

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
            'index' => Pages\ListPaymentTypes::route('/'),
            'create' => Pages\CreatePaymentType::route('/create'),
            'view' => Pages\ViewPaymentType::route('/{record}'),
            'edit' => Pages\EditPaymentType::route('/{record}/edit'),
        ];
    }
}
