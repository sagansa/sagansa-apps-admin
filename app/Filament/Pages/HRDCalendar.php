<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use App\Filament\Clusters\HRD;
use App\Models\Presence;
use App\Models\PermitEmployee;
use App\Models\DailySalary;
use App\Models\ClosingStore;
use App\Models\RemainingStorage;
use App\Models\AssetCheck;
use Carbon\Carbon;

class HRDCalendar extends Page
{
    protected static ?string $cluster = HRD::class;

    protected static string|\BackedEnum|null $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationLabel = 'Kalender';

    protected static ?string $title = 'Kalender HRD';

    protected static ?int $navigationSort = 0;

    public function getView(): string
    {
        return 'filament.pages.hrd-calendar';
    }

    public ?array $events = [];

    public ?string $selectedEvent = null;

    public bool $showPresence = true;

    public bool $showLeave = true;

    public bool $showSalary = true;

    public bool $showClosing = true;

    public bool $showStock = true;

    public bool $showAsset = true;

    public function mount(): void
    {
        $this->loadEvents();
    }

    public function updatedShowPresence(): void
    {
        $this->loadEvents();
    }

    public function updatedShowLeave(): void
    {
        $this->loadEvents();
    }

    public function updatedShowSalary(): void
    {
        $this->loadEvents();
    }

    public function updatedShowClosing(): void
    {
        $this->loadEvents();
    }

    public function updatedShowStock(): void
    {
        $this->loadEvents();
    }

    public function updatedShowAsset(): void
    {
        $this->loadEvents();
    }

    public function closeEventModal(): void
    {
        $this->selectedEvent = null;
    }

    public function loadEvents(): void
    {
        $start = Carbon::now()->startOfMonth()->toDateString();
        $end = Carbon::now()->endOfMonth()->toDateString();

        $events = [];

        if ($this->showPresence) {
            $this->fetchPresences($events, $start, $end);
        }

        if ($this->showLeave) {
            $this->fetchLeaves($events, $start, $end);
        }

        if ($this->showSalary) {
            $this->fetchDailySalaries($events, $start, $end);
        }

        if ($this->showClosing) {
            $this->fetchClosingStores($events, $start, $end);
        }

        if ($this->showStock) {
            $this->fetchStorageStocks($events, $start, $end);
        }

        if ($this->showAsset) {
            $this->fetchAssetChecks($events, $start, $end);
        }

        $this->events = $events;
    }

    public function getEvents(): array
    {
        return $this->events ?? [];
    }

    private function fetchPresences(array &$events, string $start, string $end): void
    {
        // Presences use dateTime columns, so we need to handle date range properly
        $startDateTime = $start . ' 00:00:00';
        $endDateTime = $end . ' 23:59:59';

        $presences = Presence::with(['createdBy', 'store'])
            ->where('check_in', '>=', $startDateTime)
            ->where('check_in', '<=', $endDateTime)
            ->get();

        foreach ($presences as $presence) {
            $checkInTime = Carbon::parse($presence->check_in);
            $checkOutTime = $presence->check_out ? Carbon::parse($presence->check_out) : $checkInTime->copy()->addHours(8);

            // Determine status based on check_out
            $status = 'tepat_waktu';
            if (!$presence->check_out) {
                $status = 'tidak_absen';
            }

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
            ->where('status', '!=', '3')
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
                '1' => '#ca8a04',
                '2' => '#16a34a',
                '4' => '#2563eb',
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
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
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
                'start' => Carbon::parse($salary->date)->toDateString(),
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
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->get();

        foreach ($closings as $closing) {
            $events[] = [
                'id' => 'closing-' . $closing->id,
                'title' => 'Closing: ' . ($closing->store?->nickname ?? 'Unknown'),
                'start' => Carbon::parse($closing->date)->toDateString(),
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
            ->where('date', '>=', $start)
            ->where('date', '<=', $end)
            ->get();

        foreach ($stocks as $stock) {
            $events[] = [
                'id' => 'stock-' . $stock->id,
                'title' => 'Stok: ' . ($stock->store?->nickname ?? 'Unknown'),
                'start' => Carbon::parse($stock->date)->toDateString(),
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
            ->where('check_date', '>=', $start)
            ->where('check_date', '<=', $end)
            ->get();

        foreach ($checks as $check) {
            $events[] = [
                'id' => 'asset-' . $check->id,
                'title' => 'Aset: ' . ($check->asset?->name ?? 'Unknown'),
                'start' => Carbon::parse($check->check_date)->toDateString(),
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
