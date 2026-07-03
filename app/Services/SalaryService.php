<?php

namespace App\Services;

use App\Models\MonthlySalary;
use App\Models\DailySalary;
use App\Models\Presence;
use App\Models\PayrollPeriodSetting;
use App\Models\User;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class SalaryService
{
    /**
     * Generate atau update gaji bulanan
     */
    public function generateMonthlySalary($userId, $year, $month)
    {
        $user = User::findOrFail($userId);
        
        // Dapatkan tenant_id dari user, store, atau fallback
        $tenantId = $user->tenant_id
            ?? Store::first()?->tenant_id
            ?? DB::table('tenants')->first()?->id
            ?? '00000000-0000-0000-0000-000000000000';

        // Ambil pengaturan periode penggajian tenant
        $setting = PayrollPeriodSetting::where('tenant_id', $tenantId)->first();
        if (!$setting) {
            $setting = PayrollPeriodSetting::create([
                'tenant_id' => $tenantId,
                'start_day' => 26,
                'end_day' => 25,
                'transport_allowance_per_day' => 25000,
                'meal_allowance_per_day' => 20000,
                'late_penalty_per_hour' => 10000,
                'no_checkout_penalty' => 20000,
            ]);
        }

        $startDay = $setting->start_day;

        // Hitung rentang tanggal dinamis
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

        // Ambil semua presensi valid ('2') karyawan dalam rentang tanggal
        $presences = Presence::where('created_by_id', $userId)
            ->whereBetween('check_in', [$periodStart, $periodEnd])
            ->where('status', '2') // 2 = valid
            ->with(['shiftStore', 'store'])
            ->get();

        $totalWorkDays = $presences->count();
        $totalEffectiveHours = 0;
        $totalBaseSalary = 0;
        $totalPenaltyAmount = 0;

        foreach ($presences as $presence) {
            // 1. Total jam efektif kerja
            $effectiveHours = $presence->calculateEffectiveWorkingTime();
            $totalEffectiveHours += $effectiveHours;

            // 2. Gaji kotor harian (dari tabel daily_salaries atau default toko)
            $dailySalary = DailySalary::where('presence_id', $presence->id)->first();
            $baseSalaryAmount = $dailySalary ? $dailySalary->amount : ($presence->store?->daily_salary_amount ?? 50000);
            $totalBaseSalary += $baseSalaryAmount;

            // 3. Potongan denda harian
            if (is_null($presence->check_out)) {
                // Denda flat jika tidak check-out
                $totalPenaltyAmount += $setting->no_checkout_penalty;
            } else {
                // Denda jam terlambat check-in + cepat pulang
                $penaltyHours = $presence->calculateTotalPenalty();
                $totalPenaltyAmount += $penaltyHours * $setting->late_penalty_per_hour;
            }
        }

        // Hitung tunjangan (dinamis dari pengaturan)
        $transportAllowance = $totalWorkDays * $setting->transport_allowance_per_day;
        $mealAllowance = $totalWorkDays * $setting->meal_allowance_per_day;

        $allowances = [
            'transport' => $transportAllowance,
            'meal' => $mealAllowance
        ];

        $deductions = [
            'late_penalties' => $totalPenaltyAmount
        ];

        $finalSalary = $totalBaseSalary + $transportAllowance + $mealAllowance - $totalPenaltyAmount;

        // Simpan atau update rekapitulasi gaji bulanan
        $monthlySalary = MonthlySalary::updateOrCreate(
            [
                'user_id' => $userId,
                'period_start' => $periodStart->toDateString(),
                'period_end' => $periodEnd->toDateString(),
            ],
            [
                'tenant_id' => $tenantId,
                'total_work_days' => $totalWorkDays,
                'total_hours' => $totalEffectiveHours,
                'base_salary' => $totalBaseSalary,
                'allowances' => $allowances,
                'deductions' => $deductions,
                'total_salary' => $finalSalary,
                'status' => MonthlySalary::STATUS_DRAFT
            ]
        );

        // Sinkronisasi data presensi yang masuk dalam slip gaji ini ke pivot table
        $monthlySalary->presences()->sync($presences->pluck('id')->toArray());

        return $monthlySalary;
    }
}
