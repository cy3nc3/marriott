<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    public function run(): void
    {
        $academicYears = AcademicYear::all();
        $grades = GradeLevel::all();

        foreach ($academicYears as $academicYear) {
            foreach ($grades as $grade) {
                // Create Section - A
                Section::updateOrCreate([
                    'academic_year_id' => $academicYear->id,
                    'grade_level_id' => $grade->id,
                    'name' => 'Section - A',
                ]);

                // Create Section - B
                Section::updateOrCreate([
                    'academic_year_id' => $academicYear->id,
                    'grade_level_id' => $grade->id,
                    'name' => 'Section - B',
                ]);
            }
        }
    }
}
