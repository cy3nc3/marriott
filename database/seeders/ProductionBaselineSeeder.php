<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProductionBaselineSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedAcademicYears();

        $this->call([
            GradeLevelSeeder::class,
            SubjectSeeder::class,
            TeacherSeeder::class,
            SectionSeeder::class,
        ]);

        $this->seedRoleAccounts();
        $this->seedTeacherAssignments();
        $this->seedHistoricalStudents();
    }

    private function seedAcademicYears(): void
    {
        foreach ([
            ['name' => '2023-2024', 'start_date' => '2023-06-05', 'end_date' => '2024-03-29', 'status' => 'completed', 'current_quarter' => '4'],
            ['name' => '2024-2025', 'start_date' => '2024-06-03', 'end_date' => '2025-03-28', 'status' => 'completed', 'current_quarter' => '4'],
            ['name' => '2025-2026', 'start_date' => '2025-06-02', 'end_date' => '2026-03-27', 'status' => 'upcoming', 'current_quarter' => '1'],
        ] as $academicYear) {
            AcademicYear::query()->updateOrCreate(
                ['name' => $academicYear['name']],
                $academicYear
            );
        }
    }

    private function seedRoleAccounts(): void
    {
        foreach (UserRole::cases() as $role) {
            $prefix = str_replace('_', '', $role->value);

            User::query()->updateOrCreate(
                ['email' => "{$prefix}@marriott.edu"],
                [
                    'first_name' => 'Test',
                    'last_name' => $role->label(),
                    'name' => "Test {$role->label()}",
                    'password' => Hash::make('password'),
                    'birthday' => '1990-01-01',
                    'role' => $role,
                    'is_active' => true,
                ]
            );
        }
    }

    private function seedTeacherAssignments(): void
    {
        $teachers = User::query()
            ->where('role', UserRole::TEACHER)
            ->orderBy('email')
            ->get();

        if ($teachers->isEmpty()) {
            return;
        }

        Subject::query()
            ->orderBy('grade_level_id')
            ->orderBy('subject_code')
            ->get()
            ->each(function (Subject $subject, int $index) use ($teachers): void {
                $teacher = $teachers[$index % $teachers->count()];

                $teacherSubject = TeacherSubject::query()->firstOrCreate([
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subject->id,
                ]);

                Section::query()
                    ->where('grade_level_id', $subject->grade_level_id)
                    ->get()
                    ->each(function (Section $section) use ($teacherSubject): void {
                        $subjectAssignment = SubjectAssignment::query()->updateOrCreate(
                            [
                                'section_id' => $section->id,
                                'teacher_subject_id' => $teacherSubject->id,
                            ],
                            []
                        );

                        ClassSchedule::query()->updateOrCreate(
                            [
                                'section_id' => $section->id,
                                'subject_assignment_id' => $subjectAssignment->id,
                                'day' => 'Monday',
                            ],
                            [
                                'type' => 'academic',
                                'label' => null,
                                'start_time' => '08:00:00',
                                'end_time' => '09:00:00',
                            ]
                        );
                    });
            });
    }

    private function seedHistoricalStudents(): void
    {
        $completedYear = AcademicYear::query()->where('name', '2024-2025')->firstOrFail();
        $sections = Section::query()
            ->where('academic_year_id', $completedYear->id)
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get();

        for ($i = 1; $i <= 40; $i++) {
            $student = $this->upsertStudentWithParent($i);
            $section = $sections[($i - 1) % $sections->count()];

            Enrollment::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $completedYear->id,
                ],
                [
                    'grade_level_id' => $section->grade_level_id,
                    'section_id' => $section->id,
                    'payment_term' => 'cash',
                    'downpayment' => 0,
                    'status' => 'enrolled',
                    'created_at' => $completedYear->start_date,
                ]
            );

            PermanentRecord::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'academic_year_id' => $completedYear->id,
                ],
                [
                    'school_name' => 'Marriott School',
                    'grade_level_id' => $section->grade_level_id,
                    'general_average' => 88 + ($i % 7),
                    'status' => 'promoted',
                    'failed_subject_count' => 0,
                    'conditional_resolved_at' => null,
                    'conditional_resolution_notes' => null,
                    'remarks' => 'Seeded historical production record.',
                ]
            );
        }
    }

    private function upsertStudentWithParent(int $index): Student
    {
        $lrn = '100000'.str_pad((string) $index, 4, '0', STR_PAD_LEFT);

        $studentUser = User::query()->updateOrCreate(
            ['email' => "student.{$lrn}@marriott.edu"],
            [
                'first_name' => 'Student',
                'last_name' => (string) $index,
                'name' => "Student {$index}",
                'password' => Hash::make('password'),
                'birthday' => '2010-01-01',
                'role' => UserRole::STUDENT,
                'is_active' => true,
            ]
        );

        $parentUser = User::query()->updateOrCreate(
            ['email' => "parent.{$lrn}@marriott.edu"],
            [
                'first_name' => 'Parent',
                'last_name' => (string) $index,
                'name' => "Parent {$index}",
                'password' => Hash::make('password'),
                'birthday' => '1980-01-01',
                'role' => UserRole::PARENT,
                'is_active' => true,
            ]
        );

        $student = Student::query()->updateOrCreate(
            ['lrn' => $lrn],
            [
                'user_id' => $studentUser->id,
                'first_name' => 'Student',
                'last_name' => (string) $index,
                'gender' => $index % 2 === 0 ? 'Male' : 'Female',
                'birthdate' => '2010-01-01',
                'guardian_name' => "Parent {$index}",
                'contact_number' => '0917000'.str_pad((string) $index, 4, '0', STR_PAD_LEFT),
                'address' => "Demo Address {$index}",
                'is_lis_synced' => true,
                'sync_error_flag' => false,
                'sync_error_notes' => null,
            ]
        );

        DB::table('parent_student')->updateOrInsert(
            [
                'parent_id' => $parentUser->id,
                'student_id' => $student->id,
            ],
            [
                'created_at' => now(),
                'updated_at' => now(),
            ]
        );

        return $student;
    }
}
