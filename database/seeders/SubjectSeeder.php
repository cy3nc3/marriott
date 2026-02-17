<?php

namespace Database\Seeders;

use App\Models\GradeLevel;
use App\Models\Subject;
use Illuminate\Database\Seeder;

class SubjectSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $subjects = [
            ['name' => 'Math', 'code' => 'MATH'],
            ['name' => 'Science', 'code' => 'SCI'],
            ['name' => 'Araling Panlipunan', 'code' => 'AP'],
            ['name' => 'Edukasyon sa Pagpapakatao', 'code' => 'ESP'],
            ['name' => 'Filipino', 'code' => 'FIL'],
            ['name' => 'English', 'code' => 'ENG'],
            ['name' => 'MAPEH', 'code' => 'MAPEH'],
            ['name' => 'Technology and Livelihood Education', 'code' => 'TLE'],
        ];

        $gradeLevels = GradeLevel::all();

        foreach ($gradeLevels as $gradeLevel) {
            $level = $gradeLevel->level_order;

            foreach ($subjects as $subject) {
                Subject::updateOrCreate(
                    [
                        'grade_level_id' => $gradeLevel->id,
                        'subject_code' => "{$subject['code']}{$level}",
                    ],
                    [
                        'subject_name' => "{$subject['name']} {$level}",
                    ]
                );
            }
        }
    }
}
