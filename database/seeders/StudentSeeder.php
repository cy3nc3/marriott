<?php

namespace Database\Seeders;

use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    public function run(): void
    {
        $activeYear = AcademicYear::where('status', '!=', 'completed')->first();
        if (! $activeYear) {
            return;
        }

        $sections = Section::all();

        foreach ($sections as $section) {
            // Create 15 students per section
            for ($i = 1; $i <= 15; $i++) {
                $student = Student::create([
                    'lrn' => '100000'.$section->id.str_pad($i, 2, '0', STR_PAD_LEFT),
                    'first_name' => 'Student '.$i,
                    'last_name' => $section->name.' Section',
                    'gender' => $i % 2 == 0 ? 'Male' : 'Female',
                ]);

                $status = 'enrolled';
                if ($i === 14) {
                    $status = 'dropped';
                }
                if ($i === 15) {
                    $status = 'transferred';
                }

                Enrollment::create([
                    'student_id' => $student->id,
                    'academic_year_id' => $activeYear->id,
                    'grade_level_id' => $section->grade_level_id,
                    'section_id' => $section->id,
                    'payment_term' => 'full',
                    'status' => $status,
                ]);
            }
        }
    }
}
