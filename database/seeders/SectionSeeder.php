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
        $sectionNames = [
            'Rizal',
            'Bonifacio',
            'Mabini',
            'Del Pilar',
            'Luna',
            'Aguinaldo',
        ];

        foreach ($academicYears as $academicYear) {
            foreach ($grades as $grade) {
                foreach ($sectionNames as $sectionName) {
                    Section::updateOrCreate([
                        'academic_year_id' => $academicYear->id,
                        'grade_level_id' => $grade->id,
                        'name' => $sectionName,
                    ]);
                }
            }
        }
    }
}
