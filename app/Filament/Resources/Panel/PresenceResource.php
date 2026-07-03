<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\HRD;
use App\Filament\Filters\DateFilter;
use App\Filament\Columns\ImageOpenUrlColumn;
use Filament\Forms;
use Filament\Tables;
use Livewire\Component;
use Filament\Schemas\Schema;
use App\Models\Presence;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Forms\Components\TextInput;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Components\DateTimePicker;
use App\Filament\Resources\Panel\PresenceResource\Pages;
use App\Filament\Resources\Panel\PresenceResource\RelationManagers;
use Filament\Actions\ActionGroup;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Support\Facades\Auth;
use App\Helpers\ImageHelper;
use Filament\Tables\Columns\ImageColumn;

class PresenceResource extends Resource
{
    protected static ?string $model = Presence::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 1;

    protected static ?string $cluster = HRD::class;

    protected static string|\UnitEnum|null $navigationGroup = 'Personal Data';

    protected static ?string $pluralLabel = 'Presence';

    public static function getModelLabel(): string
    {
        return __('crud.presences.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.presences.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.presences.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 1])->schema([
                    Select::make('store_id')
                        ->required()
                        ->relationship('store', 'nickname')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('shift_store_id')
                        ->required()
                        ->relationship('shiftStore', 'name')
                        ->searchable()
                        ->preload()
                        ->native(false),

                    Select::make('status')
                        ->required()
                        ->options([
                            '1' => 'belum diperiksa',
                            '2' => 'valid',
                            '3' => 'tidak valid',
                        ]),

                    DateTimePicker::make('check_in')
                        ->required()
                        ->native(false),

                    DateTimePicker::make('check_out')
                        ->nullable()
                        ->native(false),

                    Select::make('created_by_id')
                        ->label('For')
                        ->nullable()
                        ->relationship('createdBy', 'name')
                        ->searchable()
                        // ->disabled()
                        ->preload()
                        ->native(false),

                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        $presence = Presence::query();

        if (!Auth::user()->hasRole('admin')) {
            $presence->where('created_by_id', Auth::id());
        }
        return $table
            ->query($presence)
            ->poll('60s')
            ->columns([

                // ImageOpenUrlColumn::make('image_in')
                //     ->visibility('public')
                //     ->url(fn($record) => ImageHelper::getImageUrl($record->image_in)),

                ImageColumn::make('image_in')
                    ->openUrlInNewTab()
                    ->visibility('public')
                    ->state(fn($record) => ImageHelper::getImageUrl($record->image_in))
                    ->url(fn($record) => ImageHelper::getImageUrl($record->image_in)),

                ImageOpenUrlColumn::make('image_out')
                    ->visibility('public')
                    ->url(fn($record) => ImageHelper::getImageUrl($record->image_out)),

                TextColumn::make('createdBy.name')
                    ->sortable(),

                TextColumn::make('store.nickname')
                    ->sortable(),

                TextColumn::make('shiftStore.name'),

                TextColumn::make('status')
                    ->formatStateUsing(
                        fn(string $state): string => match ($state) {
                            '1' => 'belum diperiksa',
                            '2' => 'valid',
                            '3' => 'tidak valid',

                            default => $state,
                        }
                    )
                    ->badge()
                    ->color(
                        fn(string $state): string => match ($state) {
                            '1' => 'warning',
                            '2' => 'success',
                            '3' => 'danger',
                            default => $state,
                        }
                    ),

                TextColumn::make('check_in'),

                TextColumn::make('check_out'),

                TextColumn::make('late_hours')
                    ->label('Keterlambatan (Jam)')
                    ->getStateUsing(fn($record) => $record->calculateLateHours()),

                TextColumn::make('check_out_penalty')
                    ->label('Pinalty Pulang (Jam)')
                    ->getStateUsing(fn($record) => $record->calculateCheckOutPenalty()),

                TextColumn::make('total_penalty')
                    ->label('Total Pinalty (Jam)')
                    ->getStateUsing(fn($record) => $record->calculateTotalPenalty()),

                TextColumn::make('effective_working_time')
                    ->label('Effective Working Time (Jam)')
                    ->getStateUsing(fn($record) => $record->calculateEffectiveWorkingTime()),

                TextColumn::make('createdBy.employees.salary_rate_per_hour')
                    ->label('Salary Rate per Hour'),

                TextColumn::make('daily_salary')
                    ->label('Daily Salary')
                    ->getStateUsing(fn($record) => 'Rp ' . number_format($record->calculateDailySalary(), 0, ',', '.')),
            ])
            ->filters([
                DateFilter::make('check_in'),

                SelectFilter::make('created_by_id')
                    ->label('Created By')
                    ->searchable()
                    ->relationship('createdBy', 'name'),

                SelectFilter::make('status')
                    ->label('Status')
                    ->options([
                        '1' => 'belum diperiksa',
                        '2' => 'valid',
                        '3' => 'tidak valid',
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\BulkAction::make('approveSelected')
                        ->label('Setujui Presensi Terpilih')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->requiresConfirmation()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records) {
                            \App\Models\Presence::whereIn('id', $records->pluck('id'))->update(['status' => 2]);
                        }),
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
            'index' => Pages\ListPresences::route('/'),
            'create' => Pages\CreatePresence::route('/create'),
            'view' => Pages\ViewPresence::route('/{record}'),
            'edit' => Pages\EditPresence::route('/{record}/edit'),
        ];
    }
}
