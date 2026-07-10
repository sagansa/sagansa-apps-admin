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

    protected static ?int $navigationSort = 20;


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

    public static function getNavigationBadge(): ?string
    {
        if (!Auth::user()?->hasRole('admin')) {
            return null;
        }

        $count = DetailRequest::where('status', '1')->count();

        return $count > 0 ? (string) $count : null;
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'warning';
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
                    Action::make('approve')
                        ->label('Approve')
                        ->icon('heroicon-o-check-circle')
                        ->color('success')
                        ->visible(fn () => Auth::user()->hasRole('admin'))
                        ->hidden(fn ($record) => $record->status != '1')
                        ->requiresConfirmation()
                        ->modalHeading('Approve Request Item')
                        ->modalDescription('Produk ini akan disetujui dan bisa dimasukkan ke invoice.')
                        ->action(fn ($record) => $record->update(['status' => 4])),
                    Action::make('reject')
                        ->label('Reject')
                        ->icon('heroicon-o-x-circle')
                        ->color('danger')
                        ->visible(fn () => Auth::user()->hasRole('admin'))
                        ->hidden(fn ($record) => $record->status != '1')
                        ->requiresConfirmation()
                        ->modalHeading('Reject Request Item')
                        ->modalDescription('Produk ini akan ditolak dan tidak bisa diproses ke invoice.')
                        ->action(fn ($record) => $record->update(['status' => 3])),
                    Action::make('Update Payment Type To Cash')
                        ->icon('heroicon-o-pencil-square')
                        ->action(function ($record) {
                            $productDefault = $record->product->payment_type_id ?? 1;
                            $status = ($productDefault == 1) ? '1' : '4';
                            $record->update([
                                'payment_type_id' => 2,
                                'status' => $status
                            ]);
                        })
                        ->requiresConfirmation(),
                    Action::make('markAsNotUsed')
                        ->label('Tidak Digunakan')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->visible(fn () => Auth::user()->hasRole('admin'))
                        ->hidden(fn ($record) => in_array($record->status, ['2', '3', '5', '6']))
                        ->requiresConfirmation()
                        ->modalHeading('Tandai Tidak Digunakan')
                        ->modalDescription('Apakah Anda yakin ingin menandai item request ini sebagai tidak digunakan?')
                        ->action(fn ($record) => $record->update(['status' => '6'])),
                ])
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DeleteBulkAction::make(),
                    \Filament\Actions\BulkAction::make('approveSelected')
                        ->label('Approve Selected')
                        ->icon('heroicon-o-check-circle')
                        ->visible(fn () => Auth::user()->hasRole('admin'))
                        ->requiresConfirmation()
                        ->modalHeading('Approve Item Terpilih')
                        ->modalDescription('Semua item yang dipilih akan disetujui dan bisa diproses ke invoice.')
                        ->action(function (Collection $records) {
                            DetailRequest::whereIn('id', $records->pluck('id'))
                                ->where('status', '1')
                                ->update(['status' => 4]);
                        })
                        ->color('success'),
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
                    \Filament\Actions\BulkAction::make('markAsNotUsedSelected')
                        ->label('Set status Tidak Digunakan')
                        ->icon('heroicon-o-no-symbol')
                        ->color('warning')
                        ->visible(fn () => Auth::user()->hasRole('admin'))
                        ->requiresConfirmation()
                        ->modalHeading('Tandai Tidak Digunakan Terpilih')
                        ->modalDescription('Apakah Anda yakin ingin menandai semua item terpilih sebagai tidak digunakan?')
                        ->action(function (Collection $records) {
                            DetailRequest::whereIn('id', $records->pluck('id'))
                                ->whereNotIn('status', ['2', '3', '5', '6'])
                                ->update(['status' => '6']);
                        })
                        ->color('warning'),
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
            ->headerActions([
                Action::make('cleanUpStale')
                    ->label('Bersihkan Request Usang (> 1 Bulan)')
                    ->icon('heroicon-o-trash')
                    ->color('danger')
                    ->visible(fn () => Auth::user()->hasRole('admin'))
                    ->modalHeading('Konfirmasi Pembersihan Data Usang')
                    ->modalDescription(function () {
                        $cutoffDate = now()->subDays(30);

                        $notUsedCount = \App\Models\DetailRequest::where('status', '6')->count();
                        
                        $staleDetailsCount = \App\Models\DetailRequest::where('status', '!=', '2')
                            ->where(function ($q) use ($cutoffDate) {
                                $q->where('created_at', '<', $cutoffDate)
                                  ->orWhereNull('created_at');
                            })
                            ->count();

                        $staleRequestsCount = \App\Models\RequestPurchase::where('date', '<', $cutoffDate->toDateString())
                            ->whereDoesntHave('detailRequests')
                            ->count();

                        return "Informasi data saat ini:
- Total data berstatus 'Tidak Digunakan' (Not Used): {$notUsedCount} item.
- Jumlah item detail request usang (> 1 bulan & belum selesai) yang akan dihapus: {$staleDetailsCount} item.
- Jumlah data induk request purchase kosong (> 1 bulan) yang akan dihapus: {$staleRequestsCount} data.

Apakah Anda yakin ingin menghapus data-data usang tersebut secara permanen?";
                    })
                    ->requiresConfirmation()
                    ->action(function () {
                        $cutoffDate = now()->subDays(30);

                        // Delete detail requests
                        $deletedDetailsCount = \App\Models\DetailRequest::where('status', '!=', '2')
                            ->where(function ($q) use ($cutoffDate) {
                                $q->where('created_at', '<', $cutoffDate)
                                  ->orWhereNull('created_at');
                            })
                            ->delete();

                        // Delete empty parent RequestPurchases
                        $staleRequests = \App\Models\RequestPurchase::where('date', '<', $cutoffDate->toDateString())
                            ->whereDoesntHave('detailRequests')
                            ->get();

                        $deletedRequestsCount = 0;
                        foreach ($staleRequests as $rp) {
                            $rp->delete();
                            $deletedRequestsCount++;
                        }

                        \Filament\Notifications\Notification::make()
                            ->title('Pembersihan Berhasil')
                            ->body("Berhasil menghapus {$deletedDetailsCount} detail request usang dan {$deletedRequestsCount} parent request purchase kosong.")
                            ->success()
                            ->send();
                    })
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
