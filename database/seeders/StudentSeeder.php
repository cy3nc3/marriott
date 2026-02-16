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
        $studentCounter = 1;

        foreach ($sections as $section) {
            // Create 20 students per section
            for ($i = 1; $i <= 20; $i++) {
                $lrn = '100000' . str_pad($studentCounter, 4, '0', STR_PAD_LEFT);
                
                $student = Student::updateOrCreate(
                    ['lrn' => $lrn],
                    [
                        'first_name' => 'Student',
                        'last_name' => (string) $studentCounter,
                        'gender' => $i % 2 == 0 ? 'Male' : 'Female',
                        'birthdate' => '2010-01-01',
                    ]
                );

                Enrollment::updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $activeYear->id,
                    ],
                    [
                        'grade_level_id' => $section->grade_level_id,
                        'section_id' => $section->id,
                        'payment_term' => 'full',
                        'status' => 'enrolled',
                    ]
                );

                $studentCounter++;
            }
        }
    }
}
