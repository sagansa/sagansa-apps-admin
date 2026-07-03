<?php

namespace App\Filament\Resources\Panel\MonthlySalaryResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class DailySalariesRelationManager extends RelationManager
{
    protected static string $relationship = 'dailySalaries';

    protected static ?string $title = 'Rincian Gaji Harian (Aktual)';

    public function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date('d M Y')
                    ->sortable(),

                TextColumn::make('store.nickname')
                    ->label('Toko')
                    ->default('-'),

                TextColumn::make('shiftStore.name')
                    ->label('Shift')
                    ->default('-'),

                TextColumn::make('amount')
                    ->label('Nominal Gaji Harian')
                    ->money('idr')
                    ->sortable()
                    ->summarize(\Filament\Tables\Columns\Summarizers\Sum::make()->money('idr')->label('Total')),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => match ((int) $record->status) {
                        1 => 'Pending',
                        2 => 'Approved',
                        3 => 'Paid',
                        default => 'Draft'
                    })
                    ->color(fn ($state) => match ($state) {
                        'Approved' => 'success',
                        'Paid'     => 'primary',
                        default    => 'warning',
                    }),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([])
            ->defaultSort('date', 'asc');
    }
}
