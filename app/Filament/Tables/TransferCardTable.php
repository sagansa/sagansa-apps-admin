<?php

namespace App\Filament\Tables;

use App\Filament\Columns\ImageOpenUrlColumn;
use App\Filament\Columns\StatusColumn;
use App\Support\PublicStorageUrl;
use Filament\Tables\Columns\TextColumn;
use Illuminate\Support\Facades\Auth;

class TransferCardTable
{
    public static function schema($modelClass): array
    {
        return [
            \Filament\Tables\Columns\Layout\Split::make([
                ImageOpenUrlColumn::make('image')
                    ->visibility('public')
                    ->url(fn($record) => PublicStorageUrl::from($record->image))
                    ->grow(false),

                \Filament\Tables\Columns\Layout\Stack::make([
                    TextColumn::make('date')
                        ->date('d M Y')
                        ->weight('bold'),
                    
                    \Filament\Tables\Columns\Layout\Split::make([
                        TextColumn::make('storeFrom.nickname')
                            ->color('gray')
                            ->icon('heroicon-m-arrow-right-start-on-rectangle')
                            ->iconColor('danger'),
                        
                        TextColumn::make('arrow')
                            ->default('→')
                            ->extraAttributes(['class' => 'px-2 text-gray-400'])
                            ->grow(false),

                        TextColumn::make('storeTo.nickname')
                            ->color('gray')
                            ->icon('heroicon-m-arrow-left-end-on-rectangle')
                            ->iconColor('success'),
                    ])->grow(false),
                ]),

                StatusColumn::make('status')
                    ->grow(false),
            ]),

            \Filament\Tables\Columns\Layout\Panel::make([
                \Filament\Tables\Columns\Layout\Stack::make([
                    TextColumn::make('rincian_transfer_tabel')
                        ->label('Rincian Stok')
                        ->getStateUsing(fn ($record) => $record)
                        ->html()
                        ->formatStateUsing(function ($state) {
                            $items = $state->detailTransferCards->map(function ($detail) {
                                $productName = $detail->product?->name ?? 'Unknown';
                                $qty = number_format($detail->quantity, 0, ',', '.');
                                $unit = $detail->product?->unit?->unit ?? '';
                                return "<div style='padding: 6px 0; border-bottom: 1px solid rgba(156, 163, 175, 0.2); font-size: 11px; line-height: 1.4;'>
                                            <span style='opacity: 0.8;'>{$productName}</span>
                                            <span style='font-weight: bold;'> = {$qty} <small style='font-weight: normal; opacity: 0.6;'>{$unit}</small></span>
                                        </div>";
                            })->implode('');

                            return "<div style='display: grid; grid-template-columns: repeat(3, 1fr); gap: 0 40px;'>{$items}</div>";
                        })
                ])
            ])->collapsible(),

            TextColumn::make('sentBy.name')
                ->label('Pengirim')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('receivedBy.name')
                ->label('Penerima')
                ->toggleable(isToggledHiddenByDefault: true),

            TextColumn::make('approvedBy.name')
                ->label('Disetujui Oleh')
                ->toggleable(isToggledHiddenByDefault: true),
        ];
    }
}
