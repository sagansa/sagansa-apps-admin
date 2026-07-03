<?php

namespace App\Filament\Resources\Panel\MonthlySalaryResource\RelationManagers;

use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;

class PresencesRelationManager extends RelationManager
{
    protected static string $relationship = 'presences';

    protected static ?string $recordTitleAttribute = 'check_in';

    public function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('check_in')
                    ->label('Tanggal')
                    ->dateTime('d M Y')
                    ->sortable(),

                TextColumn::make('shiftStore.name')
                    ->label('Shift'),

                TextColumn::make('check_in_time')
                    ->label('Jam Masuk')
                    ->getStateUsing(fn($record) => $record->check_in ? \Carbon\Carbon::parse($record->check_in)->format('H:i') : '-'),

                TextColumn::make('check_out_time')
                    ->label('Jam Pulang')
                    ->getStateUsing(fn($record) => $record->check_out ? \Carbon\Carbon::parse($record->check_out)->format('H:i') : '-'),

                TextColumn::make('effective_hours')
                    ->label('Jam Kerja Efektif')
                    ->getStateUsing(fn($record) => $record->calculateEffectiveWorkingTime() . ' jam'),

                TextColumn::make('rate_per_hour')
                    ->label('Rate per Jam')
                    ->getStateUsing(function($record) {
                        if (!$record->createdBy) {
                            return 'Rp 0';
                        }
                        $rate = app(\App\Services\SalaryService::class)->getHourlyRateForUser(
                            $record->createdBy, 
                            \Carbon\Carbon::parse($record->check_in)
                        );
                        return 'Rp ' . number_format($rate, 0, ',', '.');
                    }),

                TextColumn::make('daily_salary_calculated')
                    ->label('Gaji Harian')
                    ->getStateUsing(function($record) {
                        if (!$record->createdBy) {
                            return 'Rp 0';
                        }
                        $rate = app(\App\Services\SalaryService::class)->getHourlyRateForUser(
                            $record->createdBy, 
                            \Carbon\Carbon::parse($record->check_in)
                        );
                        $effectiveTime = $record->calculateEffectiveWorkingTime();
                        return 'Rp ' . number_format($effectiveTime * $rate, 0, ',', '.');
                    }),
            ])
            ->filters([])
            ->headerActions([])
            ->actions([])
            ->bulkActions([]);
    }
}
