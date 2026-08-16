<?php

namespace App\Filament\Resources\Panel\DailySalaryResource\RelationManagers;

use App\Filament\Columns\CurrencyColumn;
use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Resources\Panel\PaymentReceiptResource;
use App\Support\PublicStorageUrl;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Filament\Resources\RelationManagers\RelationManager;
use Illuminate\Database\Eloquent\Builder;

/**
 * Relation manager pembayaran (payment receipts) untuk satu daily salary.
 *
 * Menampilkan list receipt di bawah halaman View/Edit DailySalary (relation
 * manager inilah yang dirender oleh Filament pada halaman detail). Hanya
 * receipt payment_for = 2 (gaji harian) yang relevan — catatan:
 * DailySalaryPolicy::view mensyaratkan status = 2, jadi list ini memang
 * hanya muncul untuk salary yang sudah dibayar.
 */
class PaymentReceiptsRelationManager extends RelationManager
{
    protected static string $relationship = 'paymentReceipts';

    protected static ?string $recordTitleAttribute = 'notes';

    public function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->where('payment_for', '2'))
            ->columns([
                ImageOpenUrlColumn::make('image')
                    ->label('Payment')
                    ->disk('public')
                    ->url(fn ($record) => PublicStorageUrl::from($record->image)),

                TextColumn::make('created_at')
                    ->date(),

                CurrencyColumn::make('transfer_amount'),

                TextColumn::make('notes')
                    ->limit(50),
            ])
            ->filters([])
            ->actions([
                \Filament\Actions\ViewAction::make()
                    ->url(fn ($record) => PaymentReceiptResource::getUrl('view', ['record' => $record])),

                \Filament\Actions\DetachAction::make()
                    ->action(function ($record) {
                        $record->pivot->delete();
                        $this->getOwnerRecord()->update(['status' => 3]);
                    }),
            ])
            ->bulkActions([
                \Filament\Actions\BulkActionGroup::make([
                    \Filament\Actions\DetachBulkAction::make()
                        ->action(function ($records) {
                            foreach ($records as $record) {
                                if ($record->pivot) {
                                    $record->pivot->delete();
                                }
                            }
                            $this->getOwnerRecord()->update(['status' => 3]);
                        }),
                ]),
            ]);
    }
}