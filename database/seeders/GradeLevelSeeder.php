<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use Illuminate\Database\Seeder;

class GradeLevelSeeder extends Seeder
{
    public function run(): void
    {
        $grades = [
            ['name' => 'Grade 7', 'level_order' => 7],
            ['name' => 'Grade 8', 'level_order' => 8],
            ['name' => 'Grade 9', 'level_order' => 9],
            ['name' => 'Grade 10', 'level_order' => 10],
        ];

        foreach ($grades as $grade) {
            GradeLevel::create($grade);
        }
    }
}
