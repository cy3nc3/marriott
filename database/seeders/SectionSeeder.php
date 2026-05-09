<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\Section;
use Illuminate\Database\Seeder;

class SectionSeeder extends Seeder
{
    /**
     * Keep all school years aligned with the same section naming map used by SY 2025-2026.
     *
     * @var array<int, array<int, string>>
     */
    private const SECTION_BLUEPRINT = [
        7 => ['St. Paul'],
        8 => ['St. Anthony'],
        9 => ['St. Francis'],
        10 => ['St. John', 'St. Anne'],
    ];

    public function run(): void
    {
        $academicYears = AcademicYear::all();
        $gradesByOrder = GradeLevel::query()
            ->get()
            ->keyBy(fn (GradeLevel $grade): int => (int) $grade->level_order);

        foreach ($academicYears as $academicYear) {
            foreach (self::SECTION_BLUEPRINT as $gradeOrder => $sectionNames) {
                /** @var GradeLevel|null $grade */
                $grade = $gradesByOrder->get($gradeOrder);
                if (! $grade instanceof GradeLevel) {
                    continue;
                }

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
