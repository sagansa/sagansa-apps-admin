<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\HRD;
use App\Models\EmployeeLoan;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Panel\EmployeeLoanResource\Pages;
use App\Filament\Resources\Panel\EmployeeLoanResource\RelationManagers;

class EmployeeLoanResource extends Resource
{
    protected static ?string $model = EmployeeLoan::class;

    protected static ?int $navigationSort = 5;


    protected static ?string $pluralLabel = 'Employee Loans';

    protected static ?string $cluster = HRD::class;

    public static function getModelLabel(): string
    {
        return 'Employee Loan';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Employee Loans';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Informasi Pinjaman Kasbon')->schema([
                Grid::make(['default' => 2])->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label('Karyawan')
                        ->searchable()
                        ->required(),

                    DatePicker::make('loan_date')
                        ->label('Tanggal Pinjam')
                        ->default(now())
                        ->required(),

                    TextInput::make('amount')
                        ->label('Total Pinjaman (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                            $count = (int) $get('installment_count') ?: 1;
                            $set('installment_amount', round((float)$state / $count, 2));
                        }),

                    TextInput::make('installment_count')
                        ->label('Tenor (Bulan)')
                        ->numeric()
                        ->default(1)
                        ->required()
                        ->live()
                        ->afterStateUpdated(function (callable $set, $state, callable $get) {
                            $amount = (float) $get('amount') ?: 0;
                            $count = (int) $state ?: 1;
                            $set('installment_amount', round($amount / $count, 2));
                        }),

                    TextInput::make('installment_amount')
                        ->label('Cicilan per Bulan (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->disabled()
                        ->dehydrated()
                        ->required(),

                    TextInput::make('guarantee')
                        ->label('Jaminan Kasbon')
                        ->placeholder('Misal: Ijazah Asli, BPKB Motor, dll (Opsional)')
                        ->nullable(),

                    Textarea::make('description')
                        ->label('Keterangan')
                        ->placeholder('Alasan kasbon')
                        ->columnSpanFull()
                        ->required(),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('loan_date')
                    ->label('Tanggal Pinjam')
                    ->date()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Total Pinjaman')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('installment_count')
                    ->label('Tenor (Bulan)')
                    ->sortable(),

                TextColumn::make('installment_amount')
                    ->label('Cicilan/Bulan')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('guarantee')
                    ->label('Jaminan')
                    ->placeholder('Tanpa Jaminan')
                    ->searchable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->getStateUsing(fn ($record) => $record->status === 1 ? 'Belum Lunas' : 'Lunas')
                    ->color(fn ($state) => $state === 'Lunas' ? 'success' : 'warning'),
            ])
            ->filters([])
            ->actions([
                \Filament\Actions\EditAction::make(),
                \Filament\Actions\ViewAction::make(),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('loan_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\InstallmentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployeeLoans::route('/'),
            'create' => Pages\CreateEmployeeLoan::route('/create'),
            'view' => Pages\ViewEmployeeLoan::route('/{record}'),
            'edit' => Pages\EditEmployeeLoan::route('/{record}/edit'),
        ];
    }
}
