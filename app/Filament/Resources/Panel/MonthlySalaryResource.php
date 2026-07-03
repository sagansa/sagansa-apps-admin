<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\HRD;
use App\Filament\Forms\CurrencyInput;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\MonthlySalary;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\KeyValue;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Panel\MonthlySalaryResource\Pages;

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
            Section::make('Rincian Gaji Bulanan')->schema([
                Grid::make(['default' => 2])->schema([
                    Select::make('user_id')
                        ->relationship('user', 'name')
                        ->disabled(),

                    Select::make('status')
                        ->options([
                            MonthlySalary::STATUS_DRAFT => 'Draft',
                            MonthlySalary::STATUS_APPROVED => 'Approved',
                            MonthlySalary::STATUS_PAID => 'Paid',
                        ])
                        ->required(),

                    DatePicker::make('period_start')
                        ->disabled(),

                    DatePicker::make('period_end')
                        ->disabled(),

                    TextInput::make('total_work_days')
                        ->disabled()
                        ->numeric(),

                    TextInput::make('total_hours')
                        ->disabled()
                        ->numeric(),

                    TextInput::make('base_salary')
                        ->disabled()
                        ->numeric(),

                    TextInput::make('total_salary')
                        ->disabled()
                        ->numeric(),

                    KeyValue::make('allowances')
                        ->disabled(),

                    KeyValue::make('deductions')
                        ->disabled(),
                ]),
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
                    ->label('Gaji Kotor')
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
        return [];
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
