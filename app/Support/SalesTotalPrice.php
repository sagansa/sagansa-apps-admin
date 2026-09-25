<?php

namespace App\Support;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Satu sumber kebenaran rumus total harga sales order:
 * total_price = Σ(subtotal_price detail) + shipping_cost.
 */
class SalesTotalPrice
{
    public static function expectedTotal(Model $salesOrder): int
    {
        return (int) $salesOrder->detailSalesOrders->sum('subtotal_price')
            + (int) ($salesOrder->shipping_cost ?? 0);
    }

    public static function isMismatched(Model $salesOrder): bool
    {
        return (int) ($salesOrder->total_price ?? 0) !== static::expectedTotal($salesOrder);
    }

    /**
     * Scope query ke order yang total_price-nya tidak cocok
     * dengan Σ subtotal detail + ongkir (total NULL dianggap anomali).
     */
    public static function applyMismatchScope(Builder $query): Builder
    {
        $table = $query->getModel()->getTable();

        return $query->whereRaw(
            "COALESCE(`{$table}`.`total_price`, -1) "
            . '<> COALESCE(('
            . "SELECT SUM(`detail`.`subtotal_price`) FROM `detail_sales_orders` AS `detail` "
            . "WHERE `detail`.`sales_order_id` = `{$table}`.`id`"
            . '), 0) + COALESCE(`' . $table . '`.`shipping_cost`, 0)'
        );
    }

    /**
     * Hitung ulang total_price dari data detail + ongkir yang tersimpan
     * saat ini, lalu simpan. Mengembalikan nilai total yang baru.
     */
    public static function recalculate(Model $salesOrder): int
    {
        $salesOrder->refresh();

        $total = static::expectedTotal($salesOrder);

        $salesOrder->forceFill(['total_price' => $total])->saveQuietly();

        return $total;
    }
}
