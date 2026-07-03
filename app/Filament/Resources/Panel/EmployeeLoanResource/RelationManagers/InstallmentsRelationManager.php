<?php

namespace App\Filament\Resources\Panel\EmployeeLoanResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Tables\Columns\TextColumn;

class InstallmentsRelationManager extends RelationManager
{
    protected static string $relationship = 'installments';

    public function form(Schema $form): Schema
    {
        return $form->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('due_date')
            ->columns([
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date('F Y')
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Jumlah Cicilan')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => match ($record->status) {
                        1 => 'Pending',
                        2 => 'Lunas',
                        3 => 'Ditunda',
                        default => 'Unknown'
                    })
                    ->color(fn ($state) => match ($state) {
                        'Pending' => 'gray',
                        'Lunas' => 'success',
                        'Ditunda' => 'warning',
                        default => 'danger'
                    }),

                TextColumn::make('monthlySalary')
                    ->label('Terpotong Pada Slip')
                    ->getStateUsing(fn($record) => $record->monthlySalary ? 'Gaji ' . \Carbon\Carbon::parse($record->monthlySalary->period_start)->format('F Y') : '-')
            ])
            ->filters([])
            ->headerActions([])
            ->actions([
                \Filament\Tables\Actions\Action::make('defer')
                    ->label('Tunda Cicilan')
                    ->icon('heroicon-o-clock')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->modalHeading('Tunda Cicilan Kasbon')
                    ->modalDescription('Apakah Anda yakin ingin menunda cicilan bulan ini? Pembayaran cicilan bulan ini akan ditangguhkan, dan cicilan baru akan otomatis ditambahkan pada akhir tenor pinjaman.')
                    ->visible(fn ($record) => $record->status === 1)
                    ->action(function ($record) {
                        $record->update(['status' => 3]); // 3 = deferred
                        
                        // Cari cicilan dengan due_date terjauh untuk kasbon ini
                        $latestInstallment = $record->employeeLoan->installments()->orderBy('due_date', 'desc')->first();
                        $nextDueDate = \Carbon\Carbon::parse($latestInstallment->due_date)->addMonth()->toDateString();
                        
                        $record->employeeLoan->installments()->create([
                            'amount' => $record->amount,
                            'due_date' => $nextDueDate,
                            'status' => 1 // pending
                        ]);
                    })
            ])
            ->bulkActions([])
            ->defaultSort('due_date', 'asc');
    }
}
