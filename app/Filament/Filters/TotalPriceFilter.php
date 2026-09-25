<?php

namespace App\Filament\Filters;

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
                $table = $query->getModel()->getTable();

                return match ($data['value'] ?? null) {
                    // total_price memuat ongkir, jadi keberadaan produk
                    // tidak bisa disimpulkan dari nilainya — cek detail item.
                    'ada_produk' => $query->whereHas('detailSalesOrders'),
                    'tanpa_produk' => $query->doesntHave('detailSalesOrders'),
                    'total_nol' => $query->where(
                        fn (Builder $query): Builder => $query
                            ->where('total_price', 0)
                            ->orWhereNull('total_price')
                    ),
                    'tidak_cocok' => $query->whereRaw(
                        "COALESCE(`{$table}`.`total_price`, -1) "
                        . '<> COALESCE(('
                        . "SELECT SUM(`detail`.`subtotal_price`) FROM `detail_sales_orders` AS `detail` "
                        . "WHERE `detail`.`sales_order_id` = `{$table}`.`id`"
                        . '), 0) + COALESCE(`' . $table . '`.`shipping_cost`, 0)'
                    ),
                    default => $query,
                };
            });
    }
}
