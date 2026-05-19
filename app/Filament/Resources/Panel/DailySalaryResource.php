<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\HRD;
use App\Filament\Filters\DateFilter;
use App\Filament\Filters\SelectEmployeeFilter;
use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\CurrencyInput;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\PaymentStatusSelectInput;
use App\Filament\Forms\StoreSelect;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\DailySalary;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use App\Filament\Resources\Panel\DailySalaryResource\Pages;
use App\Filament\Tables\DailySalaryTable;
use App\Models\PaymentType;
use Filament\Actions\ActionGroup;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class DailySalaryResource extends Resource
{
    protected static ?string $model = DailySalary::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = HRD::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Salaries';

    protected static ?string $pluralLabel = 'Daily Salaries';

    public static function getModelLabel(): string
    {
        return __('crud.dailySalaries.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.dailySalaries.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.dailySalaries.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    StoreSelect::make('store_id'),

                    BaseSelect::make('shift_store_id')
                        ->placeholder('Shift Store')
                        ->relationship('shiftStore', 'name'),

                    DateInput::make('date')
                        ->rules([
                            'date',
                            fn ($record) => function (string $attribute, $value, $fail) use ($record) {
                                $createdBy = $record?->created_by_id ?? Auth::id();

                                $exists = DailySalary::query()
                                    ->where('created_by_id', $createdBy)
                                    ->where('date', $value)
                                    ->when($record, fn ($query) => $query->where('id', '!=', $record->id))
                                    ->exists();

                                if ($exists) {
                                    $fail('Gaji harian untuk tanggal ini sudah diinput sebelumnya.');
                                }
                            },
                        ]),

                    CurrencyInput::make('amount'),

                    BaseSelect::make('payment_type_id')
                        ->required()
                        // ->relationship('paymentType', 'name')
                        ->relationship(
                            name: 'paymentType',
                            modifyQueryUsing: fn(Builder $query) => $query->where('status', '1'),
                        )
                        ->getOptionLabelFromRecordUsing(fn(PaymentType $record) => "{$record->name}")
                        ->searchable()
                        ->preload(),

                    PaymentStatusSelectInput::make('status'),

                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $dailySalaries = DailySalary::query();

        if (!Auth::user()->hasRole('admin')) {
            $dailySalaries->where('created_by_id', Auth::id());
        }

        return $table
            ->query($dailySalaries)
            ->poll('60s')
            ->columns(
                DailySalaryTable::schema()
            )
            ->filters([
                SelectEmployeeFilter::make('created_by_id'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->hidden(fn() => !Auth::user()->hasRole('admin'))
                    ->options([
                        '1' => 'belum dibayar',
                        '2' => 'sudah dibayar',
                        '3' => 'siap dibayar',
                        '4' => 'perbaiki',
                    ]),

                DateFilter::make('date'),

            ], layout: FiltersLayout::AboveContent)

            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make()->visible(fn($record) => auth()->user()->can('update', $record)),
                    \Filament\Actions\ViewAction::make()->visible(fn($record) => auth()->user()->can('view', $record)),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('setStatusToThree')
                        ->label('Set Status to Siap Dibayar')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            DailySalary::whereIn('id', $records->pluck('id'))->update(['status' => 3]);
                        })
                        ->color('gray'),
                    \Filament\Actions\BulkAction::make('setStatusToOne')
                        ->label('Set Status to Belum Dibayar')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            DailySalary::whereIn('id', $records->pluck('id'))->update(['status' => 1]);
                        })
                        ->color('warning'),
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
            'index' => Pages\ListDailySalaries::route('/'),
            'create' => Pages\CreateDailySalary::route('/create'),
            'view' => Pages\ViewDailySalary::route('/{record}'),
            'edit' => Pages\EditDailySalary::route('/{record}/edit'),
        ];
    }
}
