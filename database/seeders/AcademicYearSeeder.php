<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        AcademicYear::create([
            'name' => '2024-2025',
            'start_date' => '2024-06-01',
            'end_date' => '2025-03-31',
            'status' => 'ongoing',
            'current_quarter' => '1',
        ]);
    }
}
