<?php

namespace App\Support;

use App\Models\User;

/**
 * Menangani resolusi identifier `created_by_id` yang bisa berupa
 * numeric id ATAU uuid user (karena tabel user ada di database auth terpisah,
 * sehingga beberapa record legacy menyimpan uuid alih-alih integer).
 *
 * Dipakai oleh:
 * - PaymentReceiptResource (filter/query relasi fuelServices & dailySalaries)
 * - FuelService::getFuelServiceNameAttribute
 * - DailySalary::getDailySalaryNameAttribute
 */
trait ResolvesCreatedBy
{
    /**
     * Kembalikan daftar identifier yang mungkin cocok dengan user yang dimaksud:
     * - input numeric id    -> [numeric id] + [uuid jika user punya uuid]
     * - input uuid          -> [uuid]
     * - input null/empty    -> []
     *
     * Berguna untuk `whereIn('created_by_id', $targetIds)`.
     *
     * @return list<string>
     */
    public static function resolveUserIdentifier(mixed $id): array
    {
        if (blank($id)) {
            return [];
        }

        $id = (string) $id;
        $targetIds = [$id];

        // Jika id numeric, coba tambahkan uuid user tsb (jika ada) supaya
        // query juga menangkap record lama yang created_by_id-nya disimpan sebagai uuid.
        if (is_numeric($id)) {
            $user = User::find($id);
            if ($user && isset($user->uuid) && $user->uuid) {
                $targetIds[] = $user->uuid;
            }
        }

        return $targetIds;
    }

    /**
     * Cari nama user dari identifier (numeric id atau uuid).
     * Mengembalikan string kosong bila tidak ditemukan / input kosong.
     */
    public static function findCreatorName(?string $createdById): string
    {
        if (blank($createdById)) {
            return '';
        }

        $query = User::withTrashed();

        if (is_numeric($createdById)) {
            $user = $query->find($createdById);
        } else {
            $user = $query->where('uuid', $createdById)->first();
        }

        return $user?->name ?? '';
    }
}
