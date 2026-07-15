<?php

namespace App\Http\Controllers;

use App\Models\Presence;
use App\Models\PermitEmployee;
use App\Models\DailySalary;
use App\Models\ClosingStore;
use App\Models\RemainingStorage;
use App\Models\AssetCheck;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AdminCalendarController extends Controller
{
    /**
     * Get all calendar events for the HRD calendar.
     */
    public function events(Request $request)
    {
        $start = $request->input('start', Carbon::now()->startOfMonth()->toDateTimeString());
        $end = $request->input('end', Carbon::now()->endOfMonth()->toDateTimeString());

        $events = [];

        // 1. Fetch Presences
        $this->fetchPresences($events, $start, $end);

        // 2. Fetch Leaves
        $this->fetchLeaves($events, $start, $end);

        // 3. Fetch Daily Salaries
        $this->fetchDailySalaries($events, $start, $end);

        // 4. Fetch Closing Stores
        $this->fetchClosingStores($events, $start, $end);

        // 5. Fetch Storage Stocks
        $this->fetchStorageStocks($events, $start, $end);

        // 6. Fetch Asset Checks
        $this->fetchAssetChecks($events, $start, $end);

        return response()->json(['events' => $events]);
    }

    private function fetchPresences(array &$events, string $start, string $end): void
    {
        $presences = Presence::with(['createdBy', 'store'])
            ->whereBetween('check_in', [$start, $end])
            ->get();

        foreach ($presences as $presence) {
            $checkInTime = Carbon::parse($presence->check_in);
            $checkOutTime = $presence->check_out ? Carbon::parse($presence->check_out) : $checkInTime->copy()->addHours(8);

            // Determine status color
            $status = $presence->check_in_status ?? 'tepat_waktu';
            $color = match($status) {
                'terlambat' => '#eab308',
                'tidak_absen' => '#ef4444',
                default => '#22c55e',
            };

            $events[] = [
                'id' => 'presence-' . $presence->id,
                'title' => 'Presensi: ' . ($presence->store?->nickname ?? 'Unknown'),
                'start' => $checkInTime->toISOString(),
                'end' => $checkOutTime->toISOString(),
                'color' => $color,
                'extendedProps' => [
                    'type' => 'presence',
                    'employee' => $presence->createdBy?->name ?? 'Unknown',
                    'store' => $presence->store?->nickname ?? 'Unknown',
                    'check_in' => $checkInTime->format('H:i'),
                    'check_out' => $checkOutTime->format('H:i'),
                    'status' => $status,
                ],
            ];
        }
    }

    private function fetchLeaves(array &$events, string $start, string $end): void
    {
        $leaves = PermitEmployee::with(['createdBy'])
            ->where('status', '!=', '3') // Exclude rejected
            ->where(function ($query) use ($start, $end) {
                $query->whereBetween('from_date', [$start, $end])
                    ->orWhereBetween('until_date', [$start, $end])
                    ->orWhere(function ($q) use ($start, $end) {
                        $q->where('from_date', '<=', $start)
                          ->where('until_date', '>=', $end);
                    });
            })
            ->get();

        foreach ($leaves as $leave) {
            $status = $leave->status ?? '1';
            $color = match($status) {
                '1' => '#ca8a04', // Pending - yellow dark
                '2' => '#16a34a', // Approved - green dark
                '4' => '#2563eb', // Resubmit - blue
                default => '#6b7280',
            };

            $reasonText = PermitEmployee::getReasonText($leave->reason);

            $events[] = [
                'id' => 'leave-' . $leave->id,
                'title' => 'Cuti: ' . ($leave->createdBy?->name ?? 'Unknown') . ' (' . $reasonText . ')',
                'start' => Carbon::parse($leave->from_date)->startOfDay()->toISOString(),
                'end' => Carbon::parse($leave->until_date)->endOfDay()->toISOString(),
                'color' => $color,
                'display' => 'background',
                'extendedProps' => [
                    'type' => 'leave',
                    'employee' => $leave->createdBy?->name ?? 'Unknown',
                    'reason' => $reasonText,
                    'status' => PermitEmployee::getStatusText($status),
                    'notes' => $leave->notes,
                ],
            ];
        }
    }

    private function fetchDailySalaries(array &$events, string $start, string $end): void
    {
        $salaries = DailySalary::with(['createdBy', 'store'])
            ->whereBetween('date', [$start, $end])
            ->get();

        foreach ($salaries as $salary) {
            $statusText = match($salary->status) {
                '1' => 'Belum Dibayar',
                '2' => 'Sudah Dibayar',
                '3' => 'Siap Dibayar',
                '4' => 'Perbaiki',
                default => 'Unknown',
            };

            $events[] = [
                'id' => 'salary-' . $salary->id,
                'title' => 'Gaji: ' . ($salary->createdBy?->name ?? 'Unknown') . ' - Rp ' . number_format($salary->amount),
                'start' => Carbon::parse($salary->date)->toISOString(),
                'color' => '#3b82f6',
                'extendedProps' => [
                    'type' => 'daily_salary',
                    'employee' => $salary->createdBy?->name ?? 'Unknown',
                    'store' => $salary->store?->nickname ?? 'Unknown',
                    'amount' => $salary->amount,
                    'status' => $statusText,
                ],
            ];
        }
    }

    private function fetchClosingStores(array &$events, string $start, string $end): void
    {
        $closings = ClosingStore::with(['store', 'createdBy'])
            ->whereBetween('date', [$start, $end])
            ->get();

        foreach ($closings as $closing) {
            $events[] = [
                'id' => 'closing-' . $closing->id,
                'title' => 'Closing: ' . ($closing->store?->nickname ?? 'Unknown'),
                'start' => Carbon::parse($closing->date)->toISOString(),
                'color' => '#a855f7',
                'extendedProps' => [
                    'type' => 'closing_store',
                    'store' => $closing->store?->nickname ?? 'Unknown',
                    'amount' => $closing->total_cash_transfer,
                    'status' => $closing->status ? 'Selesai' : 'Draft',
                ],
            ];
        }
    }

    private function fetchStorageStocks(array &$events, string $start, string $end): void
    {
        $stocks = RemainingStorage::with(['store'])
            ->where('for', 'remaining_storage')
            ->whereBetween('date', [$start, $end])
            ->get();

        foreach ($stocks as $stock) {
            $events[] = [
                'id' => 'stock-' . $stock->id,
                'title' => 'Stok: ' . ($stock->store?->nickname ?? 'Unknown'),
                'start' => Carbon::parse($stock->date)->toISOString(),
                'color' => '#f97316',
                'extendedProps' => [
                    'type' => 'storage_stock',
                    'store' => $stock->store?->nickname ?? 'Unknown',
                    'status' => 'Dilaporkan',
                ],
            ];
        }
    }

    private function fetchAssetChecks(array &$events, string $start, string $end): void
    {
        $checks = AssetCheck::with(['asset', 'checkedBy'])
            ->whereBetween('check_date', [$start, $end])
            ->get();

        foreach ($checks as $check) {
            $events[] = [
                'id' => 'asset-' . $check->id,
                'title' => 'Aset: ' . ($check->asset?->name ?? 'Unknown'),
                'start' => Carbon::parse($check->check_date)->toISOString(),
                'color' => '#06b6d4',
                'extendedProps' => [
                    'type' => 'asset_check',
                    'asset' => $check->asset?->name ?? 'Unknown',
                    'checked_by' => $check->checkedBy?->name ?? 'Unknown',
                    'status' => $check->status == 1 ? 'OK' : 'Issue',
                ],
            ];
        }
    }
}
