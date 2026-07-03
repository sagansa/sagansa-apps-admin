<?php

namespace App\Filament\Resources\Panel\PresenceResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\PresenceResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use App\Models\Presence;
use App\Models\Store;
use App\Models\PayrollPeriodSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class ListPresences extends ListRecords
{
    protected static string $resource = PresenceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('approvePeriod')
                ->label('Setujui Masal per Siklus Gaji')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->form([
                    Select::make('month')
                        ->label('Bulan Gaji')
                        ->options([
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ])
                        ->default(now()->month)
                        ->required(),
                    Select::make('year')
                        ->label('Tahun')
                        ->options(array_combine(range(2024, 2030), range(2024, 2030)))
                        ->default(now()->year)
                        ->required(),
                ])
                ->action(function (array $data) {
                    $month = (int) $data['month'];
                    $year = (int) $data['year'];

                    // Ambil tenant_id
                    $tenantId = Store::first()?->tenant_id
                        ?? DB::connection('mysql_auth')->table('tenants')->first()?->id
                        ?? '00000000-0000-0000-0000-000000000000';

                    $setting = PayrollPeriodSetting::where('tenant_id', $tenantId)->first();
                    $startDay = $setting ? $setting->start_day : 26;

                    // Hitung rentang tanggal penggajian untuk siklus gajian terpilih
                    if ($startDay == 1) {
                        $periodStart = Carbon::create($year, $month, 1)->startOfMonth();
                        $periodEnd = Carbon::create($year, $month, 1)->endOfMonth();
                    } else {
                        $prevMonth = Carbon::create($year, $month, 1)->subMonth();
                        $startDayClamped = min($startDay, $prevMonth->daysInMonth);
                        $periodStart = $prevMonth->day($startDayClamped)->startOfDay();

                        $currentMonth = Carbon::create($year, $month, 1);
                        $endDayClamped = min($startDay - 1, $currentMonth->daysInMonth);
                        $periodEnd = $currentMonth->day($endDayClamped)->endOfDay();
                    }

                    // Update semua data presensi dalam rentang ini menjadi valid (status = 2)
                    $count = Presence::whereBetween('check_in', [$periodStart, $periodEnd])
                        ->where('status', '!=', '2')
                        ->update(['status' => '2']);

                    Notification::make()
                        ->title("Berhasil menyetujui {$count} data presensi untuk periode gaji " . Carbon::create($year, $month, 1)->translatedFormat('F Y') . ".")
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
