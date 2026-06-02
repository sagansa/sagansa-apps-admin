<?php

namespace App\Filament\Resources\Panel;

use App\Filament\Clusters\Purchases;
use App\Filament\Filters\DateFilter;
use App\Filament\Filters\SelectPaymentTypeFilter;
use App\Filament\Filters\SelectStoreFilter;
use Filament\Schemas\Schema;
use Filament\Tables\Table;
use App\Models\DetailRequest;
use Filament\Resources\Resource;
use Filament\Forms\Components\Select;
use Filament\Tables\Columns\TextColumn;
use App\Filament\Resources\Panel\DetailRequestResource\Pages;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Auth;

class DetailRequestResource extends Resource
{
    protected static ?string $model = DetailRequest::class;

    // protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-rectangle-stack';

    protected static ?int $navigationSort = 2;

    protected static string|\UnitEnum|null $navigationGroup = 'Invoice';

    protected static ?string $cluster = Purchases::class;

    protected static ?string $pluralLabel = 'Invoice';

    public static function getModelLabel(): string
    {
        return __('crud.detailRequests.itemTitle');
    }

    public static function getPluralModelLabel(): string
    {
        return __('crud.detailRequests.collectionTitle');
    }

    public static function getNavigationLabel(): string
    {
        return __('crud.detailRequests.collectionTitle');
    }

    public static function form(Schema $form): Schema
    {
        return $form->schema([
            Section::make()->schema([
                Grid::make(['default' => 2])->schema([
                    Select::make('product_id')
                        ->required()
                        ->inlineLabel()
                        ->relationship('product', 'name')
                        ->disabled()
                        ->preload(),

                    Select::make('payment_type_id')
                        ->required()
                        ->inlineLabel()
                        ->relationship('paymentType', 'name')
                        ->preload()
                        ,
                ]),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->poll('60s')
            ->groups([
                'requestPurchase.date',
                'store.nickname'
            ])
            // ->defaultGroup('requestPurchase.date')
            ->columns([
                TextColumn::make('product.name')
                    ->label('Product'),
                TextColumn::make('requestPurchase.date')
                    ->label('Request Date'),
                TextColumn::make('paymentType.name')
                    ->label('Payment Type'),
                TextColumn::make('store.nickname')
                    ->label('Store'),

                TextColumn::make('quantity_purchase_summary')
                    ->state(fn (DetailRequest $record): string => $record->detailInvoices->pluck('quantity_product')->implode(', '))
                    ->label('Qty Purchase'),

                TextColumn::make('quantity_plan')
                    ->label('Qty Plan')
                    ->formatStateUsing(fn (DetailRequest $record) =>
                        number_format($record->quantity_plan, 0, ',', '.') . ' ' .
                            $record->product->unit->unit),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->color(
                        fn(string $state): string => match ($state) {
                            '1' => 'warning',
                            '2' => 'success',
                            '3' => 'danger',
                            '4' => 'warning',
                            '5' => 'danger',
                            '6' => 'gray',
                            default => $state,
                        }
                    )
                    ->formatStateUsing(
                        fn(string $state): string => match ($state) {
                            '1' => 'process',
                            '2' => 'done',
                            '3' => 'reject',
                            '4' => 'approved',
                            '5' => 'not valid',
                            '6' => 'not used',
                            default => $state,
                        }
                    ),

                    TextColumn::make('requestPurchase.user.name')
                        ->label('Request By')
                        ->hidden(fn () => !Auth::user()->hasRole('admin')),
            ])
            ->filters([
                SelectStoreFilter::make('store_id'),
                SelectPaymentTypeFilter::make('payment_type_id'),
                DateFilter::make('delivery_date'),
            ])
            ->actions([
                ActionGroup::make([
                    \Filament\Actions\EditAction::make(),
                    // \Filament\Actions\ViewAction::make(),
                    Action::make('Update Payment Type To Cash')
                        ->icon('heroicon-o-pencil-square')
                        ->action(function ($record) {
                            $record->update(['payment_type_id' => 2]);
                        })
                        ->requiresConfirmation(),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('setStatusToDone')
                        ->label('Set Status to Done')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            DetailRequest::whereIn('id', $records->pluck('id'))->update(['status' => 2]);
                        })
                        ->color('success'),
                    \Filament\Actions\BulkAction::make('setStatusToReject')
                        ->label('Set Status to Reject')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            DetailRequest::whereIn('id', $records->pluck('id'))->update(['status' => 3]);
                        })
                        ->color('danger'),
                    \Filament\Actions\BulkAction::make('setStatusToNot Valid')
                        ->label('Set Status to Not Valid')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            DetailRequest::whereIn('id', $records->pluck('id'))->update(['status' => 5]);
                        })
                        ->color('warning'),
                    \Filament\Actions\BulkAction::make('setStatusToNotUsed')
                        ->label('Set Status to Not Used')
                        ->icon('heroicon-o-check')
                        ->requiresConfirmation()
                        ->action(function (Collection $records) {
                            DetailRequest::whereIn('id', $records->pluck('id'))->update(['status' => 6]);
                        })
                        ->color('gray'),
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
            'index' => Pages\ListDetailRequests::route('/'),
            'create' => Pages\CreateDetailRequest::route('/create'),
            'view' => Pages\ViewDetailRequest::route('/{record}'),
            'edit' => Pages\EditDetailRequest::route('/{record}/edit'),
        ];
    }
}
