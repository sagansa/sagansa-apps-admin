<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Carbon\Carbon;

class Employee extends Model
{

    protected $connection = 'mysql';
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'join_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Titik lokasi tracking pegawai, diakses melalui user (employee_locations
     * disimpan per user_id pada kolom created_by_id, cross-DB).
     */
    public function locations()
    {
        return $this->hasMany(EmployeeLocation::class, 'created_by_id', 'user_id');
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }

    public function employeeStatus()
    {
        return $this->belongsTo(EmployeeStatus::class);
    }

    public function workingExperiences()
    {
        return $this->hasMany(WorkingExperience::class);
    }

    public function province()
    {
        return $this->belongsTo(Province::class);
    }

    public function city()
    {
        return $this->belongsTo(City::class);
    }

    public function district()
    {
        return $this->belongsTo(District::class);
    }

    public function subdistrict()
    {
        return $this->belongsTo(Subdistrict::class);
    }

    public function postalCode()
    {
        return $this->belongsTo(PostalCode::class);
    }

    public function calculateYearsOfService()
    {
        $joinDate = Carbon::parse($this->join_date);
        $currentDate = Carbon::now();

        return floor($joinDate->diffInYears($currentDate));
    }

    public function getSalaryRatePerHour()
    {
        $yearsOfService = $this->calculateYearsOfService();
        $currentYear = Carbon::now()->year;

        $salaryRate = SalaryRate::whereYear('effective_date', $currentYear)
            ->orderBy('effective_date', 'desc')
            ->first();

        if (!$salaryRate) {
            return 0;
        }

        $salaryRateDetail = SalaryRateDetail::where('salary_rate_id', $salaryRate->id)
            ->where('years_of_service', '<=', $yearsOfService)
            ->orderBy('years_of_service', 'desc')
            ->first();

        if (!$salaryRateDetail) {
            return 0;
        }

        return $salaryRateDetail->rate_per_hour;
    }

    public function calculateAge()
    {
        $birthDate = Carbon::parse($this->birth_date);
        $currentDate = Carbon::now();

        return $birthDate->diffInYears($currentDate);
    }

    public function salaryRates()
    {
        return $this->hasMany(SalaryRate::class);
    }

    public function salaryRateDetails()
    {
        return $this->hasMany(SalaryRateDetail::class);
    }
}
