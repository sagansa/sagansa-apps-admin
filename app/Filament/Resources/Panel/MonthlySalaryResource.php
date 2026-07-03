<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\HRD;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\MonthlySalary;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Panel\MonthlySalaryResource\Pages;

use App\Filament\Resources\Panel\MonthlySalaryResource\RelationManagers\PresencesRelationManager;
use App\Filament\Resources\Panel\MonthlySalaryResource\RelationManagers\DailySalariesRelationManager;

class MonthlySalaryResource extends Resource
{
    protected static ?string $model = MonthlySalary::class;

    protected static ?int $navigationSort = 1;

    protected static string|\UnitEnum|null $navigationGroup = 'Salaries';

    protected static ?string $pluralLabel = 'Monthly Salaries';

    protected static ?string $cluster = HRD::class;

    public static function getModelLabel(): string
    {
        return __('crud.monthlySalaries.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.monthlySalaries.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.monthlySalaries.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Informasi Karyawan & Periode')
                ->columns(2)
                ->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->label('Karyawan')
                        ->disabled(),

                    Select::make('status')
                        ->label('Status Slip Gaji')
                        ->options([
                            MonthlySalary::STATUS_DRAFT => 'Draft',
                            MonthlySalary::STATUS_APPROVED => 'Approved',
                            MonthlySalary::STATUS_PAID => 'Paid',
                        ])
                        ->required(),

                    DatePicker::make('period_start')
                        ->label('Tanggal Mulai')
                        ->disabled(),

                    DatePicker::make('period_end')
                        ->label('Tanggal Selesai')
                        ->disabled(),

                    TextInput::make('total_work_days')
                        ->label('Total Hari Kerja')
                        ->disabled()
                        ->numeric(),

                    TextInput::make('total_hours')
                        ->label('Total Jam Kerja')
                        ->disabled()
                        ->numeric(),
                ]),

            Section::make('Rincian Perhitungan Gaji')
                ->columns(2)
                ->schema([
                    TextInput::make('base_salary')
                        ->label('Gaji Utama Tenur (A)')
                        ->disabled()
                        ->prefix('Rp')
                        ->numeric(),

                    TextInput::make('daily_salary_total')
                        ->label('Total Gaji Harian (B)')
                        ->disabled()
                        ->prefix('Rp')
                        ->numeric(),

                    TextInput::make('total_salary')
                        ->label('Gaji Bersih Akhir (A - Potongan + B)')
                        ->disabled()
                        ->prefix('Rp')
                        ->numeric()
                        ->columnSpanFull()
                        ->extraInputAttributes(['style' => 'font-weight: bold; color: #10b981; font-size: 1.1rem;']),
                ]),

            Section::make('Daftar Potongan / Denda')
                ->schema([
                    KeyValue::make('deductions')
                        ->label('Daftar Potongan Pengurang')
                        ->disabled(),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Karyawan')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('period_start')
                    ->label('Mulai')
                    ->date()
                    ->sortable(),

                TextColumn::make('period_end')
                    ->label('Selesai')
                    ->date()
                    ->sortable(),

                TextColumn::make('total_work_days')
                    ->label('Hari Kerja')
                    ->sortable(),

                TextColumn::make('total_hours')
                    ->label('Total Jam')
                    ->sortable(),

                TextColumn::make('base_salary')
                    ->label('Gaji Tenur')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('daily_salary_total')
                    ->label('Gaji Harian')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('total_salary')
                    ->label('Gaji Bersih')
                    ->money('idr')
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        '1' => 'gray',
                        '2' => 'warning',
                        '3' => 'success',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        '1' => 'Draft',
                        '2' => 'Approved',
                        '3' => 'Paid',
                        default => $state,
                    }),
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
            ->defaultSort('id', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            PresencesRelationManager::class,
            DailySalariesRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMonthlySalaries::route('/'),
            'create' => Pages\CreateMonthlySalary::route('/create'),
            'view' => Pages\ViewMonthlySalary::route('/{record}'),
            'edit' => Pages\EditMonthlySalary::route('/{record}/edit'),
        ];
    }
}
