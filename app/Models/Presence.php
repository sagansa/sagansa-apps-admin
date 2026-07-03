<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Presence extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    protected $guarded = [];

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }

    public function approvedBy()
    {
        return $this->belongsTo(User::class, 'approved_by_id');
    }

    public function store()
    {
        return $this->belongsTo(Store::class, 'store_id');
    }

    public function shiftStore()
    {
        return $this->belongsTo(ShiftStore::class, 'shift_store_id');
    }

    public function storeOut()
    {
        return $this->belongsTo(Store::class, 'store_out_id');
    }

    public function calculateLateHours()
    {
        if (is_null($this->check_in)) {
            return 2; // Tidak absen masuk dianggap 2 jam
        }

        $checkInTime = Carbon::parse($this->check_in)->format('H:i:s');
        $shiftStartTime = Carbon::parse($this->shiftStore->shift_start_time)->format('H:i:s'); // Asumsikan ada kolom start_time di shiftStore

        if (Carbon::parse($checkInTime)->lessThanOrEqualTo($shiftStartTime)) {
            return 0; // Tidak terlambat
        }

        $lateSeconds = Carbon::parse($shiftStartTime)->diffInSeconds($checkInTime);
        $lateHours = ceil($lateSeconds / 3600); // 3600 detik dalam 1 jam

        return min($lateHours, 2); // Maksimum penalti 2 jam
    }

    public function getCheckOutStatus()
    {
        if (is_null($this->check_out)) {
            return 'Tidak Absen Pulang';
        }

        $checkOutTime = Carbon::parse($this->check_out);
        $shiftEndTime = Carbon::parse($this->shiftStore->shift_end_time); // Asumsikan ada kolom end_time di shiftStore

        // Jika check_out melewati hari, tambahkan satu hari ke shiftEndTime
        if ($checkOutTime->lessThan($shiftEndTime) && $checkOutTime->isNextDay()) {
            $shiftEndTime->addDay();
        }

        if ($checkOutTime->lessThan($shiftEndTime)) {
            return 'Cepat Pulang';
        } elseif ($checkOutTime->equalTo($shiftEndTime)) {
            return 'Tepat Waktu';
        } else {
            return 'Terlambat Pulang';
        }
    }

    public function calculateCheckOutPenalty()
    {
        if (is_null($this->check_out)) {
            return 2; // Tidak absen pulang dianggap 2 jam
        }

        $checkOutTime = Carbon::parse($this->check_out);
        $shiftEndTime = Carbon::parse($this->shiftStore->shift_end_time); // Asumsikan ada kolom end_time di shiftStore

        // Jika check_out melewati hari, tambahkan satu hari ke shiftEndTime
        if ($checkOutTime->lessThan($shiftEndTime) && $checkOutTime->isNextDay()) {
            $shiftEndTime->addDay();
        }

        if ($shiftEndTime->greaterThanOrEqualTo($checkOutTime)) {
            return 0; // Tepat waktu atau lebih
        }

        $penaltySeconds = $shiftEndTime->diffInSeconds($checkOutTime);
        $penaltyHours = ceil($penaltySeconds / 3600); // 3600 detik dalam 1 jam

        return $penaltyHours;
    }

    public function calculateTotalPenalty()
    {
        $lateHours = $this->calculateLateHours();
        $checkOutPenalty = $this->calculateCheckOutPenalty();

        return $lateHours + $checkOutPenalty;
    }

    public function calculateEffectiveWorkingTime()
    {
        $shiftDuration = $this->shiftStore->duration; // Assume duration is in hours
        $totalPenalty = $this->calculateTotalPenalty();

        return max(0, $shiftDuration - $totalPenalty); // Ensure no negative working time
    }

    public function calculateDailySalary()
    {
        $effectiveWorkingTime = $this->calculateEffectiveWorkingTime();

        // Access the employee through the user
        if (!$this->createdBy || !$this->createdBy->employee) {
            return 0; // Or handle the error as needed
        }

        $salaryRatePerHour = $this->createdBy->employee->getSalaryRatePerHour();

        return $effectiveWorkingTime * $salaryRatePerHour;
    }
}
