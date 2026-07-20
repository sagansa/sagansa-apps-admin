<?php

namespace App\Filament\Forms;

use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Carbon;

class DateInput extends DatePicker
{
    protected function setUp(): void
    {
        parent::setUp();

        $this
            ->required()
            ->rules(['date'])
            // Laporan dibatasi window 22:00 tgl D-1 s.d. 11:00 tgl D yang
            // dihitung sebagai hari D-1. Jadi sebelum jam 11:00, tanggal
            // default laporan adalah hari sebelumnya (mis. 00:12 tgl 18 =
            // laporan tgl 17).
            ->default(function () {
                $now = Carbon::now();
                return $now->hour < 11
                    ? $now->copy()->subDay()->toDateString()
                    : $now->toDateString();
            });
    }
}
