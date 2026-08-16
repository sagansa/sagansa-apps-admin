<?php

namespace App\Filament\Resources\Panel\PaymentReceiptResource\RelationManagers;

use App\Filament\Forms\DateInput;
use App\Filament\Tables\DailySalaryTable;
use App\Filament\Forms\StatusInput;
use App\Filament\Forms\StoreSelect;
use Filament\Forms;
use Filament\Tables;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Resources\RelationManagers\RelationManager;
use App\Filament\Resources\Panel\PaymentReceiptResource;
use Filament\Actions\CreateAction;
use Filament\Tables\Columns\Summarizers\Sum;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use Filament\Actions\AttachAction;

class DailySalariesRelationManager extends RelationManager
{
    protected static string $relationship = 'dailySalaries';

    protected static ?string $recordTitleAttribute = 'date';

    public function form(Schema $form): Schema
    {
        return $form->schema([
            Grid::make(['default' => 1])->schema([
                StoreSelect::make('store_id')
                    ->required(),

                Select::make('shift_store_id')
                    ->required()
                    ->relationship('shiftStore', 'name')
                    ->preload(),

                DateInput::make('date'),

                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->step(1),

                Select::make('payment_type_id')
                    ->required()
                    ->relationship('paymentType', 'name')
                    ->preload(),

                Select::make('status')
                    ->options([
                        '1' => 'belum dibayar',
                        '2' => 'sudah dibayar',
                        '3' => 'siap dibayar',
                        '4' => 'perbaiki'
                    ])
                    ->required()
                    ->preload(),

                Select::make('created_by_id')
                    ->relationship('createdBy', 'name')
                    ->nullable()
                    ->searchable()
                    ->preload(),
            ]),
        ]);
    }

    public function table(Table $table): Table
    {
        return $table

            ->columns(
                DailySalaryTable::schema()
            )

            ->filters([])
            ->headerActions([
                \Filament\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->multiple()
                    ->recordSelectSearchColumns(['createdBy.name', 'date', 'amount'])
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->forPaymentType(1))
                    ->recordTitle(function ($record) {
                        return "{$record->paymentType->name} | {$record->createdBy->name} | {$record->date} | Rp " . number_format($record->amount, 0, ',', '.');
                    }),
            ])
            ->actions([
                // \Filament\Actions\EditAction::make(),
                // \Filament\Actions\DeleteAction::make(),
                \Filament\Actions\DetachAction::make()
                    ->action(function ($record) {
                        $record->pivot->delete();
                        $record->update(['status' => 3]);
                    }),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    // \Filament\Actions\DeleteBulkAction::make(),

                    \Filament\Actions\DetachBulkAction::make()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                $record->paymentReceipts()->detach();
                                $record->update(['status' => 3]);
                            }
                        }),
                ]),
            ]);
    }

    public static function canViewForRecord(Model $ownerRecord, string $pageClass): bool
    {
        return $ownerRecord->payment_for === 2;
    }
}
