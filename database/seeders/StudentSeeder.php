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
        // 1. Create a fixed pool of 160 students to be reused
        $studentPool = [];
        for ($i = 1; $i <= 160; $i++) {
            $lrn = '100000' . str_pad($i, 4, '0', STR_PAD_LEFT);
            
            $studentPool[] = Student::updateOrCreate(
                ['lrn' => $lrn],
                [
                    'first_name' => 'Student',
                    'last_name' => (string) $i,
                    'gender' => $i % 2 == 0 ? 'Male' : 'Female',
                    'birthdate' => '2010-01-01',
                ]
            );
        }

        $academicYears = AcademicYear::orderBy('start_date', 'asc')->get();
        
        // Simulating growth by increasing enrollees each year
        $yearEnrollmentLimit = [
            '2020-2021' => 80,
            '2021-2022' => 100,
            '2022-2023' => 120,
            '2023-2024' => 140,
            '2024-2025' => 160,
        ];

        foreach ($academicYears as $academicYear) {
            $sections = Section::where('academic_year_id', $academicYear->id)->get();
            $totalForYear = $yearEnrollmentLimit[$academicYear->name] ?? 80;
            
            // Distribute the year's quota across its sections
            $studentsPerSection = (int) ceil($totalForYear / max(1, $sections->count()));
            
            foreach ($sections as $index => $section) {
                $startOffset = $index * $studentsPerSection;
                
                for ($i = 0; $i < $studentsPerSection; $i++) {
                    $studentIndex = $startOffset + $i;
                    
                    // Don't exceed the year's total or the pool size
                    if ($studentIndex >= $totalForYear || $studentIndex >= count($studentPool)) {
                        break;
                    }
                    
                    $student = $studentPool[$studentIndex];

                    Enrollment::updateOrCreate(
                        [
                            'student_id' => $student->id,
                            'academic_year_id' => $academicYear->id,
                        ],
                        [
                            'grade_level_id' => $section->grade_level_id,
                            'section_id' => $section->id,
                            'payment_term' => 'full',
                            'status' => 'enrolled',
                            'created_at' => $academicYear->start_date,
                        ]
                    );
                }
            }
        }
    }
}
