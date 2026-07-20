<?php

namespace App\Services;

use App\Models\Production;
use App\Models\Product;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Service untuk mengaplikasikan / membalik mutasi stok dari sebuah production.
 *
 * Dipanggil saat production selesai dievaluasi (apply) atau dibatalkan
 * (revert). Idempoten via kolom `productions.applied_at`:
 *  • apply() hanya jalan kalau applied_at masih null → set applied_at = now()
 *  • revert() hanya jalan kalau applied_at tidak null → set applied_at = null
 *
 * Sesuai keputusan desain: stok minus diizinkan (warning only). Jadi tidak
 * ada validasi "stok tidak cukup" yang membatalkan apply — hanya di-log.
 */
class ProductionLedgerService
{
    /**
     * Apply mutasi stok production: kurangi ingredient (direction=in),
     * tambahkan output (direction=out). Atomic via DB::transaction.
     *
     * @return bool true jika apply berhasil (atau sudah pernah di-apply),
     *              false jika gagal (DB error).
     */
    public function apply(Production $production): bool
    {
        if ($production->isApplied()) {
            return true; // idempotent — sudah pernah di-apply
        }

        try {
            DB::connection('mysql')->transaction(function () use ($production) {
                foreach ($production->items as $item) {
                    $delta = $item->direction === 'out'
                        ? (int) $item->quantity          // output → stok naik
                        : -1 * (int) $item->quantity;     // ingredient → stok turun

                    // Pakai increment supaya atomic di DB level (race-safe).
                    // Tidak perlu scope tenant_id — product sudah terikat via
                    // FK product_id di production_items.
                    Product::where('id', $item->product_id)
                        ->increment('stock', $delta);
                }

                $production->forceFill(['applied_at' => now()])->save();
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('ProductionLedgerService::apply gagal', [
                'production_id' => $production->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }

    /**
     * Balik mutasi stok: kembalikan ingredient, hapus output. Dipakai saat
     * production dibatalkan / status dikembalikan ke draft.
     */
    public function revert(Production $production): bool
    {
        if (!$production->isApplied()) {
            return true; // idempotent — belum pernah di-apply
        }

        try {
            DB::connection('mysql')->transaction(function () use ($production) {
                foreach ($production->items as $item) {
                    // Kebalikan dari apply: output → stok turun, ingredient → stok naik.
                    $delta = $item->direction === 'out'
                        ? -1 * (int) $item->quantity
                        : (int) $item->quantity;

                    Product::where('id', $item->product_id)
                        ->increment('stock', $delta);
                }

                $production->forceFill(['applied_at' => null])->save();
            });

            return true;
        } catch (\Throwable $e) {
            Log::error('ProductionLedgerService::revert gagal', [
                'production_id' => $production->id,
                'error' => $e->getMessage(),
            ]);
            return false;
        }
    }
}
