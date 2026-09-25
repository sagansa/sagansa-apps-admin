<?php

namespace App\Filament\Filters;

use App\Support\SalesTotalPrice;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;

class TotalPriceFilter extends SelectFilter
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->label('Total Price')
            ->options([
                'ada_produk' => 'Ada produk (punya item detail)',
                'tanpa_produk' => 'Tanpa produk (tidak ada item detail)',
                'total_nol' => 'Total = 0 / kosong',
                'tidak_cocok' => 'Anomali: total ≠ Σ subtotal produk + ongkir',
            ])
            ->query(function (Builder $query, array $data): Builder {
                // total_price memuat ongkir, jadi keberadaan produk
                // tidak bisa disimpulkan dari nilainya — cek detail item.
                return match ($data['value'] ?? null) {
                    'ada_produk' => $query->whereHas('detailSalesOrders'),
                    'tanpa_produk' => $query->doesntHave('detailSalesOrders'),
                    'total_nol' => $query->where(
                        fn (Builder $query): Builder => $query
                            ->where('total_price', 0)
                            ->orWhereNull('total_price')
                    ),
                    'tidak_cocok' => SalesTotalPrice::applyMismatchScope($query),
                    default => $query,
                };
            });
    }
}
