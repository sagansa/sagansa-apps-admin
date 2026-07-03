<?php

namespace App\Filament\Resources\Panel\MonthlySalaryResource\Pages;

use Filament\Actions;
use Filament\Resources\Pages\ListRecords;
use App\Filament\Resources\Panel\MonthlySalaryResource;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use App\Services\SalaryService;
use App\Models\Presence;
use App\Models\PayrollPeriodSetting;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Filament\Notifications\Notification;

class ListMonthlySalaries extends ListRecords
{
    protected static string $resource = MonthlySalaryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('generatePayroll')
                ->label('Generate/Regenerate Payroll')
                ->icon('heroicon-o-cpu-chip')
                ->color('success')
                ->form([
                    Select::make('month')
                        ->label('Bulan')
                        ->options([
                            1 => 'Januari',
                            2 => 'Februari',
                            3 => 'Maret',
                            4 => 'April',
                            5 => 'Mei',
                            6 => 'Juni',
                            7 => 'Juli',
                            8 => 'Agustus',
                            9 => 'September',
                            10 => 'Oktober',
                            11 => 'November',
                            12 => 'Desember',
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
                        ?? DB::table('tenants')->first()?->id
                        ?? '00000000-0000-0000-0000-000000000000';

                    $setting = PayrollPeriodSetting::where('tenant_id', $tenantId)->first();
                    $startDay = $setting ? $setting->start_day : 26;

                    // Hitung rentang tanggal penggajian untuk menentukan siapa saja yang memiliki presensi
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

                    // Cari semua user_id yang memiliki presensi valid dalam rentang ini
                    $userIds = Presence::whereBetween('check_in', [$periodStart, $periodEnd])
                        ->where('status', '2') // 2 = valid
                        ->pluck('created_by_id')
                        ->unique()
                        ->filter();

                    if ($userIds->isEmpty()) {
                        Notification::make()
                            ->title('Tidak ada data presensi valid untuk periode ini.')
                            ->warning()
                            ->send();
                        return;
                    }

                    $salaryService = app(SalaryService::class);
                    $count = 0;

                    foreach ($userIds as $userId) {
                        try {
                            $salaryService->generateMonthlySalary($userId, $year, $month);
                            $count++;
                        } catch (\Exception $e) {
                            // Abaikan error individu agar proses terus berjalan
                        }
                    }

                    Notification::make()
                        ->title("Berhasil men-generate {$count} slip gaji bulanan.")
                        ->success()
                        ->send();
                }),
            Actions\CreateAction::make(),
        ];
    }
}
