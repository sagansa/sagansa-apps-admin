<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\HRD;
use App\Models\SalaryPenalty;
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
use App\Filament\Resources\Panel\SalaryPenaltyResource\Pages;

class SalaryPenaltyResource extends Resource
{
    protected static ?string $model = SalaryPenalty::class;

    protected static ?int $navigationSort = 4;

    protected static string|\UnitEnum|null $navigationGroup = 'Salaries';

    protected static ?string $pluralLabel = 'Salary Penalties';

    protected static ?string $cluster = HRD::class;

    public static function getModelLabel(): string
    {
        return 'Salary Penalty';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Salary Penalties';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Informasi Pinalti Manual')->schema([
                Grid::make(['default' => 2])->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label('Karyawan')
                        ->searchable()
                        ->required(),

                    DatePicker::make('date')
                        ->label('Tanggal Denda')
                        ->default(now())
                        ->required(),

                    TextInput::make('amount')
                        ->label('Nominal Pinalti (Rp)')
                        ->numeric()
                        ->prefix('Rp')
                        ->required(),

                    Textarea::make('description')
                        ->label('Keterangan / Alasan')
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

                TextColumn::make('date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),

                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('description')
                    ->label('Keterangan')
                    ->limit(50),

                TextColumn::make('monthly_salary_id')
                    ->label('Status Payroll')
                    ->getStateUsing(fn ($record) => $record->monthly_salary_id ? 'Sudah Dipotong' : 'Belum Dipotong')
                    ->badge()
                    ->color(fn ($state) => $state === 'Sudah Dipotong' ? 'success' : 'warning'),
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
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalaryPenalties::route('/'),
            'create' => Pages\CreateSalaryPenalty::route('/create'),
            'view' => Pages\ViewSalaryPenalty::route('/{record}'),
            'edit' => Pages\EditSalaryPenalty::route('/{record}/edit'),
        ];
    }
}
