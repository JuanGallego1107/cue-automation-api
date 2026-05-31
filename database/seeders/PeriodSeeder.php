<?php

namespace Database\Seeders;

use App\Models\Period;
use Illuminate\Database\Seeder;

class PeriodSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $currentYear = now()->year;

        // Active current period
        Period::create([
            'name'       => "{$currentYear}-1",
            'start_date' => "{$currentYear}-01-01",
            'end_date'   => "{$currentYear}-06-30",
            'is_active'  => 1,
        ]);

        // Previous inactive period
        $previousYear = $currentYear - 1;
        Period::create([
            'name'       => "{$previousYear}-2",
            'start_date' => "{$previousYear}-07-01",
            'end_date'   => "{$previousYear}-12-31",
            'is_active'  => 0,
        ]);
    }
}
