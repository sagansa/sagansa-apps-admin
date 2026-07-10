<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Cash;
use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\StatusColumn;
use App\Filament\Forms\CurrencyInput;
use App\Filament\Forms\CurrencyMinusInput;
use App\Filament\Forms\CurrencyRepeaterInput;
use App\Filament\Forms\DateInput;
use App\Filament\Forms\ImageInput;
use App\Filament\Forms\Notes;
use App\Filament\Forms\StatusSelectLabel;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\CashAdvance;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\ImageColumn;
use App\Filament\Resources\Panel\CashAdvanceResource\Pages;
use Filament\Schemas\Components\Group;
use Filament\Forms\Components\Repeater;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Collection;

class CashAdvanceResource extends Resource
{
    protected static ?string $model = CashAdvance::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 40;

    protected static ?string $cluster = Cash::class;


    public static function getModelLabel(): string
    {
        return __('crud.cashAdvances.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.cashAdvances.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.cashAdvances.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Grid::make(['default' => 1, 'md' => 3])
                ->schema([
                    // Left Column (Grid span 2 for main info)
                    Grid::make(['default' => 1])
                        ->schema([
                            Section::make('Informasi Dasar')
                                ->schema([
                                    Select::make('user_id')
                                        ->label('User')
                                        ->inlineLabel()
                                        ->required()
                                        ->relationship('user', 'name', fn(Builder $query) => $query
                                            ->whereHas('roles', fn(Builder $query) => $query
                                                ->where('name', 'staff') || $query
                                                    ->where('name', 'supervisor')))
                                        ->searchable()
                                        ->preload()
                                        ->default(fn() => Auth::id())
                                        ->disabled(fn() => !Auth::user()->hasRole('admin'))
                                        ->dehydrated()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set, Get $get) {
                                            if ($state) {
                                                $before = CashAdvanceResource::getRemainBefore($state);
                                                $set('before', $before);
                                                $transfer = $get('transfer') !== null ? (int) $get('transfer') : 0;
                                                $purchase = $get('purchase') !== null ? (int) $get('purchase') : 0;
                                                $set('remains', $transfer + $before - $purchase);
                                            }
                                        }),

                                    DateInput::make('date'),

                                    StatusSelectLabel::make('status')
                                        ->label('Status')
                                        ->inlineLabel(),

                                    Notes::make('notes'),
                                ]),

                            Section::make('Bukti Transfer')
                                ->schema([
                                    ImageInput::make('image')
                                        ->directory('images/CashAdvance'),
                                ]),
                        ])
                        ->columnSpan(['md' => 2]),

                    // Right Column (Grid span 1 for financial calculations)
                    Section::make('Perhitungan Saldo')
                        ->schema([
                            CurrencyInput::make('transfer')
                                ->label('Transfer')
                                ->debounce(2000)
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, Get $get) {
                                    self::updateTotalPurchase($get, $set);
                                }),

                            CurrencyMinusInput::make('before')
                                ->label('Before (Saldo Sebelumnya)')
                                ->default(function (Get $get) {
                                    $userId = Auth::user()->hasRole('admin') ? $get('user_id') : Auth::id();
                                    return $userId ? self::getRemainBefore($userId) : 0;
                                })
                                ->debounce(2000)
                                ->reactive()
                                ->afterStateUpdated(function (Set $set, Get $get) {
                                    self::updateTotalPurchase($get, $set);
                                }),

                            CurrencyInput::make('purchase')
                                ->readOnly()
                                ->label('Purchase (Total Belanja)')
                                ->afterStateUpdated(function (Set $set, Get $get) {
                                    self::updateTotalPurchase($get, $set);
                                }),

                            CurrencyMinusInput::make('remains')
                                ->readOnly()
                                ->label('Remains (Sisa Saldo)')
                                ->default(function (Get $get) {
                                    $userId = Auth::user()->hasRole('admin') ? $get('user_id') : Auth::id();
                                    $before = $userId ? self::getRemainBefore($userId) : 0;
                                    $transfer = $get('transfer') !== null ? (int) $get('transfer') : 0;
                                    $purchase = $get('purchase') !== null ? (int) $get('purchase') : 0;
                                    return $transfer + $before - $purchase;
                                }),
                        ])
                        ->columnSpan(['md' => 1]),
                ]),

            Section::make('Daftar Pembelian')
                ->schema([
                    static::getItemsRepeater()
                ])
                ->hidden(fn($operation) => $operation === 'create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        $cashAdvance = CashAdvance::query();

        if (!Auth::user()->hasRole('admin')) {
            $cashAdvance->where('user_id', Auth::id());
        }

        return $table
            ->poll('60s')
            ->columns([
                ImageColumn::make('image')->visibility('public'),

                TextColumn::make('date'),

                CurrencyColumn::make('transfer'),

                CurrencyColumn::make('purchase'),

                CurrencyColumn::make('before'),

                CurrencyColumn::make('remains'),

                TextColumn::make('user.name'),

                StatusColumn::make('status'),
            ])
            ->filters([
                SelectFilter::make('user_id')
                    ->label('user')
                    ->relationship('user', 'name')
            ])
            ->actions([
                \Filament\Actions\Action::make('validate')
                    ->label('Validate')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(fn (CashAdvance $record) => $record->update(['status' => 2]))
                    ->visible(fn (CashAdvance $record) => Auth::user()->hasRole('admin') && $record->status !== 2),
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    \Filament\Actions\ViewAction::make(),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('setStatusToSudahDiperiksa')
                        ->label('Set Status to Sudah Diperiksa')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            CashAdvance::whereIn('id', $records->pluck('id'))->update(['status' => 2]);
                        })
                        ->color('success'),
                ]),
            ])
            ->defaultSort('date', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\AdvancePurchasesRelationManager::class
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCashAdvances::route('/'),
            'create' => Pages\CreateCashAdvance::route('/create'),
            'view' => Pages\ViewCashAdvance::route('/{record}'),
            'edit' => Pages\EditCashAdvance::route('/{record}/edit'),
        ];
    }

    protected static function updateTotalPurchase(Get $get, Set $set): void
    {
        $repeaterItems = $get('advancePurchases') ?? [];

        $totalPurchase = 0;

        foreach ($repeaterItems as $item) {
            if (isset($item['total_price'])) {
                $totalPurchase += (int) $item['total_price'];
            }
        }

        $set('purchase', $totalPurchase);

        $transfer = $get('transfer') !== null ? (int) $get('transfer') : 0;
        $before = $get('before') !== null ? (int) $get('before') : 0;
        $remains = $transfer + $before - $totalPurchase;

        $set('remains', $remains);
    }

    public static function getItemsRepeater(): Repeater
    {
        return Repeater::make('advancePurchases')
            ->hiddenLabel()
            ->columns(['md' => 8])
            ->relationship()
            ->schema([
                Select::make('supplier_id')
                    ->hiddenLabel()
                    ->disabled()
                    ->relationship('supplier', 'name')
                    ->columnSpan(4),

                Select::make('store_id')
                    ->hiddenLabel()
                    ->disabled()
                    ->relationship('store', 'nickname')
                    ->columnSpan(2),

                CurrencyRepeaterInput::make('total_price')
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        self::updateTotalPurchase($get, $set);
                    })
                    ->columnSpan(2),
            ])
            ->afterStateUpdated(function (Get $get, Set $set) {
                self::updateTotalPurchase($get, $set);
            });
    }

    public static function getRemainBefore($userId)
    {
        $remainBefore = CashAdvance::where('user_id', $userId)
            ->latest('created_at')
            ->first();

        return $remainBefore ? $remainBefore->remains : 0;
    }
}
