<?php

namespace Database\Seeders;

use App\Models\PaymentType;
use Illuminate\Database\Seeder;

class PaymentTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        PaymentType::updateOrCreate(['id' => 1], ['name' => 'transfer', 'status' => 1]);
        PaymentType::updateOrCreate(['id' => 2], ['name' => 'tunai', 'status' => 1]);
    }
}
