<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use Illuminate\Database\Seeder;

class AcademicYearSeeder extends Seeder
{
    public function run(): void
    {
        $years = [
            ['name' => '2020-2021', 'start' => '2020-06-01', 'end' => '2021-03-31', 'status' => 'completed'],
            ['name' => '2021-2022', 'start' => '2021-06-01', 'end' => '2022-03-31', 'status' => 'completed'],
            ['name' => '2022-2023', 'start' => '2022-06-01', 'end' => '2023-03-31', 'status' => 'completed'],
            ['name' => '2023-2024', 'start' => '2023-06-01', 'end' => '2024-03-31', 'status' => 'completed'],
            ['name' => '2024-2025', 'start' => '2024-06-01', 'end' => '2025-03-31', 'status' => 'ongoing'],
        ];

        foreach ($years as $year) {
            AcademicYear::updateOrCreate(
                ['name' => $year['name']],
                [
                    'start_date' => $year['start'],
                    'end_date' => $year['end'],
                    'status' => $year['status'],
                    'current_quarter' => '1',
                ]
            );
        }
    }
}
