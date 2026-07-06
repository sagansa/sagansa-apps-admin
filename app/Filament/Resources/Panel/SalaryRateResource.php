<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\HRD;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\SalaryRate;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Repeater;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Panel\SalaryRateResource\Pages;

class SalaryRateResource extends Resource
{
    protected static ?string $model = SalaryRate::class;

    protected static ?int $navigationSort = 3;


    protected static ?string $pluralLabel = 'Salary Rates';

    protected static ?string $cluster = HRD::class;

    public static function getModelLabel(): string
    {
        return 'Salary Rate';
    }

    public static function getPluralModelLabel(): string
    {
        return 'Salary Rates';
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make('Informasi Tarif Gaji')->schema([
                Grid::make(['default' => 2])->schema([
                    TextInput::make('name')
                        ->label('Nama Skema')
                        ->placeholder('Misal: Skema Gaji HRD 2026')
                        ->required(),

                    DatePicker::make('effective_date')
                        ->label('Tanggal Efektif')
                        ->required(),

                    Textarea::make('notes')
                        ->label('Catatan')
                        ->columnSpanFull()
                        ->nullable(),
                ]),
            ]),

            Section::make('Detail Tarif per Masa Kerja')->schema([
                Repeater::make('salaryRateDetails')
                    ->relationship('salaryRateDetails')
                    ->schema([
                        TextInput::make('years_of_service')
                            ->label('Minimal Masa Kerja (Tahun)')
                            ->numeric()
                            ->required(),

                        TextInput::make('rate_per_hour')
                            ->label('Gaji per Jam (Rp)')
                            ->numeric()
                            ->required(),
                    ])
                    ->columns(2)
                    ->minItems(1)
                    ->columnSpanFull(),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nama Skema')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('effective_date')
                    ->label('Tanggal Efektif')
                    ->date()
                    ->sortable(),

                TextColumn::make('notes')
                    ->label('Catatan')
                    ->limit(50),
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
            ->defaultSort('effective_date', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSalaryRates::route('/'),
            'create' => Pages\CreateSalaryRate::route('/create'),
            'view' => Pages\ViewSalaryRate::route('/{record}'),
            'edit' => Pages\EditSalaryRate::route('/{record}/edit'),
        ];
    }
}
