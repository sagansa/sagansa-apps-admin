<?php

namespace App\Filament\Pages;

use App\Models\EmployeeLocation;
use App\Models\User;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;

/**
 * Halaman peta yang menampilkan lokasi terbaru setiap pegawai (Leaflet + OSM).
 *
 * Data lokasi di-render server-side ke JavaScript via $latestLocations. Tombol
 * "Segarkan" memicu re-query. Endpoint terpisah tidak diperlukan karena data
 * disuntik langsung ke blade.
 */
class TrackEmployeeLocation extends Page
{
    // protected static string | \BackedEnum | null $navigationIcon = 'heroicon-o-map-pin';

    protected static string | \UnitEnum | null $navigationGroup = 'HRD';

    protected static ?string $navigationLabel = 'Lacak Lokasi Pegawai';

    protected static ?string $title = 'Lacak Lokasi Pegawai';

    protected static ?int $navigationSort = 30;

    protected string $view = 'filament.pages.track-employee-location';

    public static function canAccess(): bool
    {
        return Auth::user()?->hasRole(['super_admin', 'admin']) ?? false;
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    /**
     * Lokasi terbaru tiap pegawai staff, di-cache untuk view & refresh Livewire.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getLatestLocationsProperty(): array
    {
        $latest = EmployeeLocation::query()
            ->whereIn('id', function ($query) {
                $query->selectRaw('MAX(id)')
                    ->from('employee_locations')
                    ->groupBy('created_by_id');
            })
            ->with('user:id,name,email')
            ->orderBy('captured_at', 'desc')
            ->limit(500)
            ->get();

        return $latest->map(function (EmployeeLocation $loc) {
            $stale = $loc->captured_at?->lt(now()->subHours(6));

            return [
                'user_id' => $loc->created_by_id,
                'name' => $loc->user?->name ?? ('User #' . $loc->created_by_id),
                'email' => $loc->user?->email,
                'latitude' => (float) $loc->latitude,
                'longitude' => (float) $loc->longitude,
                'accuracy' => $loc->accuracy !== null ? (float) $loc->accuracy : null,
                'source' => $loc->source,
                'captured_at' => $loc->captured_at?->format('Y-m-d H:i:s'),
                'is_stale' => (bool) $stale,
            ];
        })->values()->toArray();
    }

    /**
     * Hitung ulang data & kirim notifikasi (dipicu tombol "Segarkan" via Livewire).
     */
    public function refreshData(): void
    {
        // Memaksa re-render Livewire; getLatestLocationsProperty() dijalankan lagi.
        $this->dispatch('$refresh');

        \Filament\Notifications\Notification::make()
            ->title('Peta disegarkan')
            ->success()
            ->send();
    }

    protected function getViewData(): array
    {
        return [
            'locationsJson' => json_encode($this->latestLocations),
        ];
    }
}
