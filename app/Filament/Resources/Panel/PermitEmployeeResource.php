<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\HRD;
use App\Filament\Clusters\Salaries;
use App\Filament\Forms\BaseSelect;
use App\Filament\Forms\Notes;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\PermitEmployee;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\RichEditor;
use App\Filament\Resources\Panel\PermitEmployeeResource\Pages;
use App\Filament\Resources\Panel\PermitEmployeeResource\RelationManagers;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;
use Filament\Actions\ActionGroup;

class PermitEmployeeResource extends Resource
{
    protected static ?string $model = PermitEmployee::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;


    protected static ?string $pluralLabel = 'Permits';

    protected static ?string $cluster = HRD::class;

    public static function getModelLabel(): string
    {
        return __('crud.permitEmployees.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.permitEmployees.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.permitEmployees.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    BaseSelect::make('reason')
                        // ->searchable()
                        ->preload()
                        ->options([
                            '1' => 'menikah',
                            '2' => 'sakit',
                            '3' => 'pulkam',
                            '4' => 'libur',
                            '5' => 'keluarga meninggal',
                        ]),

                    Grid::make()->schema([
                        DatePicker::make('from_date')
                            ->rules(['date'])
                            ->required()
                            ->hiddenLabel()
                            // ->placeholder('From Date')
                            ->prefix('From Date'),

                        DatePicker::make('until_date')
                            ->rules([
                                'date',
                                'after:from_date', // Add this rule
                            ])
                            ->required()
                            ->hiddenLabel()
                            ->prefix('Until Date')
                            ->minDate(fn($get) => $get('from_date'))
                    ]),

                    BaseSelect::make('status')
                        ->required(fn() => Auth::user()->hasRole('admin'))
                        ->hidden(fn($operation) => $operation === 'create')
                        ->disabled(fn() => Auth::user()->hasRole('staff'))
                        // ->searchable()
                        ->placeholder('Status')
                        ->preload()
                        ->options([
                            '1' => 'belum disetujui',
                            '2' => 'disetujui',
                            '3' => 'tidak disetujui',
                            '4' => 'pengajuan ulang',
                        ]),

                    Notes::make('notes'),
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $query = PermitEmployee::query();

        if (!Auth::user()->hasRole('admin')) {
            $query->where('created_by_id', Auth::id());
        }

        return $table
            ->query($query)
            ->poll('60s')
            ->columns([
                TextColumn::make('createdBy.name')
                    ->hidden(fn() => !Auth::user()->hasRole('admin')),

                TextColumn::make('reason')
                    ->formatStateUsing(
                        fn(string $state): string => match ($state) {
                            '1' => 'menikah',
                            '2' => 'sakit',
                            '3' => 'pulkam',
                            '4' => 'libur',
                            '5' => 'keluarga meninggal',
                        }
                    ),

                TextColumn::make('from_date')->date(),

                TextColumn::make('until_date')->date(),

                TextColumn::make('status')
                    ->formatStateUsing(
                        fn(string $state): string => match ($state) {
                            '1' => 'belum disetujui',
                            '2' => 'disetujui',
                            '3' => 'tidak disetujui',
                            '4' => 'pengajuan ulang',
                        }
                    )
                    ->badge()
                    ->color(
                        fn(string $state): string => match ($state) {
                            '1' => 'warning',
                            '2' => 'success',
                            '3' => 'gray',
                            '4' => 'danger',
                        }
                    ),
            ])
            ->filters([])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                ])

            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('approve')
                        ->label('disetujui')
                        ->icon('heroicon-o-check')
                        ->action(fn(Collection $records) => $records->each->update(['status' => '2']))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),

                    \Filament\Actions\BulkAction::make('notApprove')
                        ->label('Tidak disetujui')
                        ->icon('heroicon-o-x-mark')
                        ->action(fn(Collection $records) => $records->each->update(['status' => '3']))
                        ->deselectRecordsAfterCompletion()
                        ->requiresConfirmation(),
                ]),
            ])

            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPermitEmployees::route('/'),
            'create' => Pages\CreatePermitEmployee::route('/create'),
            'view' => Pages\ViewPermitEmployee::route('/{record}'),
            'edit' => Pages\EditPermitEmployee::route('/{record}/edit'),
        ];
    }
}
