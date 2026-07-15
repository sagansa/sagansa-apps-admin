<?php

namespace App\Console\Commands;

use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

#[Signature('closing:fix-attached-status {--execute : Apply changes. Without this flag, runs as dry-run.}')]
#[Description('Set status paid (2) untuk daily salary, invoice purchase, dan fuel service yang ter-attach ke closing store tetapi masih berstatus unpaid (1). Mengatasi dampak bug event `is_attached` lama.')]
class FixClosingStoreAttachedStatus extends Command
{
    /**
     * Definisi pivot → (tabel target, kolom status, field id di pivot).
     *
     * Hanya record ber-status 1 (unpaid) yang akan di-update ke 2 (paid).
     * Record ber-status lain (mis. 3 = void/cancelled) tidak disentuh.
     */
    private const TARGETS = [
        'closing_store_daily_salary' => [
            'table' => 'daily_salaries',
            'status_column' => 'status',
            'related_id' => 'daily_salary_id',
        ],
        'closing_store_invoice_purchase' => [
            'table' => 'invoice_purchases',
            'status_column' => 'payment_status',
            'related_id' => 'invoice_purchase_id',
        ],
        'closing_store_fuel_service' => [
            'table' => 'fuel_services',
            'status_column' => 'status',
            'related_id' => 'fuel_service_id',
        ],
    ];

    public function handle(): int
    {
        $execute = (bool) $this->option('execute');

        if ($execute) {
            $this->info('Mode: EXECUTE (perubahan akan diterapkan)');
        } else {
            $this->warn('Mode: DRY-RUN (tidak ada perubahan. Tambah --execute untuk menerapkan.)');
        }
        $this->newLine();

        $totalAffected = 0;

        foreach (self::TARGETS as $pivot => $cfg) {
            $count = $this->countAffected($pivot, $cfg);

            $this->line(sprintf(
                '- %-32s → %-20s : %d record',
                $pivot,
                $cfg['table'] . '.' . $cfg['status_column'],
                $count
            ));

            if ($execute && $count > 0) {
                $this->apply($pivot, $cfg);
            }

            $totalAffected += $count;
        }

        $this->newLine();
        if ($execute) {
            $this->info("Selesai. Total {$totalAffected} record diupdate ke status paid (2).");
        } else {
            $this->warn("Dry-run: {$totalAffected} record akan diupdate. Jalankan dengan --execute untuk menerapkan.");
        }

        return self::SUCCESS;
    }

    /**
     * Hitung record yang akan terdampak: ter-attach ke closing store dan
     * masih ber-status 1 (unpaid).
     */
    private function countAffected(string $pivot, array $cfg): int
    {
        return DB::table($pivot)
            ->join($cfg['table'], "{$cfg['table']}.id", '=', "{$pivot}.{$cfg['related_id']}")
            ->where("{$cfg['table']}.{$cfg['status_column']}", 1)
            ->distinct()
            ->count("{$cfg['table']}.id");
    }

    /**
     * Terapkan update status 1 → 2 untuk record yang ter-attach.
     */
    private function apply(string $pivot, array $cfg): void
    {
        $affectedIds = DB::table($pivot)
            ->join($cfg['table'], "{$cfg['table']}.id", '=', "{$pivot}.{$cfg['related_id']}")
            ->where("{$cfg['table']}.{$cfg['status_column']}", 1)
            ->pluck("{$cfg['table']}.id")
            ->unique()
            ->values();

        if ($affectedIds->isEmpty()) {
            return;
        }

        DB::table($cfg['table'])
            ->whereIn('id', $affectedIds)
            ->update([$cfg['status_column'] => 2]);
    }
}
