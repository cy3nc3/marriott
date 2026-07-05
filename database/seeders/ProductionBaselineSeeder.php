<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\ClassSchedule;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\GradeLevel;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherProfile;
use App\Models\TeacherSubject;
use App\Models\User;
use Database\Seeders\Support\SeedNameBank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionBaselineSeeder extends Seeder
{
    /**
     * Historical baseline schedule slots used when this seeder creates legacy schedules.
     * Keep slots distinct so seeded subjects do not collapse into one time block.
     *
     * @var array<int, array{day: string, start_time: string, end_time: string}>
     */
    private const BASELINE_SCHEDULE_SLOTS = [
        ['day' => 'Monday', 'start_time' => '07:30:00', 'end_time' => '08:30:00'],
        ['day' => 'Monday', 'start_time' => '08:45:00', 'end_time' => '09:45:00'],
        ['day' => 'Tuesday', 'start_time' => '07:30:00', 'end_time' => '08:30:00'],
        ['day' => 'Tuesday', 'start_time' => '08:45:00', 'end_time' => '09:45:00'],
        ['day' => 'Wednesday', 'start_time' => '07:30:00', 'end_time' => '08:30:00'],
        ['day' => 'Wednesday', 'start_time' => '08:45:00', 'end_time' => '09:45:00'],
        ['day' => 'Thursday', 'start_time' => '07:30:00', 'end_time' => '08:30:00'],
        ['day' => 'Friday', 'start_time' => '07:30:00', 'end_time' => '08:30:00'],
    ];

    public function run(): void
    {
        $this->seedAcademicYears();

        $this->call([
            GradeLevelSeeder::class,
            SubjectSeeder::class,
            TeacherSeeder::class,
            SectionSeeder::class,
            PermissionSeeder::class,
        ]);

        $this->seedFees();
        $this->seedRoleAccounts();
        $this->seedTeacherProfiles();
        $this->seedTeacherAssignments();
        $this->seedHistoricalStudents();
    }

    private function seedFees(): void
    {
        $academicYears = AcademicYear::all();
        $gradeLevels = GradeLevel::all();

        foreach ($academicYears as $year) {
            foreach ($gradeLevels as $grade) {
                // Tuition Fee
                DB::table('fees')->updateOrInsert(
                    ['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'type' => 'tuition'],
                    ['name' => 'Tuition Fee', 'amount' => 25000.00, 'created_at' => now(), 'updated_at' => now()]
                );

                // Miscellaneous Fee
                DB::table('fees')->updateOrInsert(
                    ['academic_year_id' => $year->id, 'grade_level_id' => $grade->id, 'type' => 'miscellaneous'],
                    ['name' => 'Miscellaneous Fee', 'amount' => 10000.00, 'created_at' => now(), 'updated_at' => now()]
                );
            }
        }
    }

    private function seedAcademicYears(): void
    {
        foreach ([
            ['name' => '2022-2023', 'start_date' => '2022-06-06', 'end_date' => '2023-03-31', 'status' => 'completed', 'current_quarter' => '4'],
            ['name' => '2023-2024', 'start_date' => '2023-06-05', 'end_date' => '2024-03-29', 'status' => 'completed', 'current_quarter' => '4'],
            ['name' => '2024-2025', 'start_date' => '2024-06-03', 'end_date' => '2025-03-28', 'status' => 'completed', 'current_quarter' => '4'],
            ['name' => '2025-2026', 'start_date' => '2025-06-02', 'end_date' => '2026-03-27', 'status' => 'ongoing', 'current_quarter' => '1'],
        ] as $academicYear) {
            AcademicYear::query()->updateOrCreate(
                ['name' => $academicYear['name']],
                $academicYear
            );
        }
    }

    private function seedRoleAccounts(): void
    {
        $staffBlueprint = [
            ['role' => UserRole::SUPER_ADMIN, 'email' => 'superadmin@marriott.edu', 'first_name' => 'Super', 'last_name' => 'Admin'],
            ['role' => UserRole::ADMIN, 'email' => 'alex.avellanosa@marriott.edu', 'first_name' => 'Alex', 'last_name' => 'Avellanosa'],
            ['role' => UserRole::REGISTRAR, 'email' => 'jocelyn.cleofe@marriott.edu', 'first_name' => 'Jocelyn', 'last_name' => 'Cleofe'],
            ['role' => UserRole::FINANCE, 'email' => 'corrine.avellanosa@marriott.edu', 'first_name' => 'Corrine', 'last_name' => 'Avellanosa'],
        ];

        foreach ($staffBlueprint as $staff) {
            User::query()->updateOrCreate(
                ['email' => $staff['email']],
                [
                    'first_name' => $staff['first_name'],
                    'last_name' => $staff['last_name'],
                    'name' => "{$staff['first_name']} {$staff['last_name']}",
                    'personal_email' => strtolower($staff['first_name']).'.'.strtolower($staff['last_name']).'@test.com',
                    'password' => Hash::make('password'),
                    'birthday' => '1990-01-01',
                    'role' => $staff['role'],
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
            ->with('teacherProfile')
            ->get()
            ->keyBy('email');

        if ($teachers->isEmpty()) {
            return;
        }

        $subjectOwnerByCode = [
            'MATH' => 'rowell.almonte@marriott.edu',
            'SCI' => 'rocelle.delacruz@marriott.edu',
            'AP' => 'elenor.cendana@marriott.edu',
            'ESP' => 'manimfa.guinacaran@marriott.edu',
            'FIL' => 'racquel.vergara@marriott.edu',
            'ENG' => 'fe.cavitt@marriott.edu',
            'MAPEH' => 'beronica.renton@marriott.edu',
            'TLE' => 'mary.guira@marriott.edu',
        ];

        $fallbackTeachers = $teachers->values();

        Subject::query()
            ->orderBy('grade_level_id')
            ->orderBy('subject_code')
            ->get()
            ->each(function (Subject $subject, int $index) use ($teachers, $subjectOwnerByCode, $fallbackTeachers): void {
                $baseCode = strtoupper((string) preg_replace('/\d+$/', '', $subject->subject_code));
                $preferredTeacherEmail = $subjectOwnerByCode[$baseCode] ?? null;

                $teacher = $preferredTeacherEmail
                    ? $teachers->get($preferredTeacherEmail)
                    : null;

                if (! $teacher instanceof User) {
                    $teacher = $fallbackTeachers[$index % $fallbackTeachers->count()];
                }

                $teacherSubject = TeacherSubject::query()->firstOrCreate([
                    'teacher_id' => $teacher->id,
                    'subject_id' => $subject->id,
                ]);

                Section::query()
                    ->where('grade_level_id', $subject->grade_level_id)
                    ->orderBy('id')
                    ->get()
                    ->values()
                    ->each(function (Section $section, int $sectionIndex) use ($teacherSubject, $index): void {
                        $subjectAssignment = SubjectAssignment::query()->updateOrCreate(
                            [
                                'section_id' => $section->id,
                                'teacher_subject_id' => $teacherSubject->id,
                            ],
                            []
                        );

                        $slotCount = count(self::BASELINE_SCHEDULE_SLOTS);
                        $slot = self::BASELINE_SCHEDULE_SLOTS[($index + $sectionIndex) % $slotCount];

                        ClassSchedule::query()->updateOrCreate(
                            [
                                'section_id' => $section->id,
                                'subject_assignment_id' => $subjectAssignment->id,
                                'day' => $slot['day'],
                            ],
                            [
                                'type' => 'academic',
                                'label' => null,
                                'start_time' => $slot['start_time'],
                                'end_time' => $slot['end_time'],
                            ]
                        );
                    });
            });

        $sections = Section::query()
            ->orderBy('academic_year_id')
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get(['id', 'academic_year_id', 'grade_level_id', 'name', 'adviser_id']);

        foreach ($sections as $index => $section) {
            $teacher = $fallbackTeachers[$index % $fallbackTeachers->count()];
            if (! $teacher instanceof User) {
                continue;
            }

            $section->update([
                'adviser_id' => $teacher->id,
            ]);
        }
    }

    private function seedTeacherProfiles(): void
    {
        $profiles = [
            'rowell.almonte@marriott.edu' => [
                'degree' => 'Bachelor of Secondary Education',
                'major' => 'Mathematics',
                'subject_competency_tags' => ['MATH'],
            ],
            'rocelle.delacruz@marriott.edu' => [
                'degree' => 'Bachelor of Secondary Education',
                'major' => 'Science',
                'subject_competency_tags' => ['SCI'],
            ],
            'fe.cavitt@marriott.edu' => [
                'degree' => 'Bachelor of Secondary Education',
                'major' => 'English',
                'subject_competency_tags' => ['ENG'],
            ],
            'elenor.cendana@marriott.edu' => [
                'degree' => 'Bachelor of Secondary Education',
                'major' => 'Social Studies',
                'subject_competency_tags' => ['AP'],
            ],
            'ma.guinacaran@marriott.edu' => [
                'degree' => 'Bachelor of Secondary Education',
                'major' => 'Values Education',
                'subject_competency_tags' => ['ESP'],
            ],
            'mary.guira@marriott.edu' => [
                'degree' => 'Bachelor of Technology and Livelihood Education',
                'major' => 'Technology and Livelihood Education',
                'subject_competency_tags' => ['TLE'],
            ],
            'racquel.vergara@marriott.edu' => [
                'degree' => 'Bachelor of Secondary Education',
                'major' => 'Filipino',
                'subject_competency_tags' => ['FIL'],
            ],
            'beronica.renton@marriott.edu' => [
                'degree' => 'Bachelor of Physical Education',
                'major' => 'MAPEH',
                'subject_competency_tags' => ['MAPEH'],
            ],
        ];

        foreach ($profiles as $email => $profile) {
            $teacher = User::query()
                ->where('role', UserRole::TEACHER)
                ->where('email', $email)
                ->first();

            if (! $teacher) {
                continue;
            }

            TeacherProfile::query()->updateOrCreate(
                ['user_id' => $teacher->id],
                [
                    'qualification_status' => 'fully_qualified',
                    'is_let_passer' => true,
                    'prc_license_no' => 'PRC-'.str_pad((string) $teacher->id, 6, '0', STR_PAD_LEFT),
                    'license_valid_until' => '2029-12-31',
                    'degree' => $profile['degree'],
                    'major' => $profile['major'],
                    'professional_education_units' => 18,
                    'exception_basis' => null,
                    'provisional_until' => null,
                    'grade_band_eligibility' => ['junior_high'],
                    'subject_competency_tags' => $profile['subject_competency_tags'],
                    'notes' => 'Seeded profile aligned with assigned curriculum subjects.',
                ]
            );
        }
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
        $lrn = sprintf('2404%08d', $index);
        $nameSet = $this->nameSetForIndex($index - 1);
        $emailLastNameToken = Str::of($nameSet['student_last_name'])
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '');

        $studentUser = User::query()->updateOrCreate(
            ['email' => "{$emailLastNameToken}.{$lrn}@marriott.edu"],
            [
                'first_name' => $nameSet['student_first_name'],
                'last_name' => $nameSet['student_last_name'],
                'name' => "{$nameSet['student_first_name']} {$nameSet['student_last_name']}",
                'password' => Hash::make('password'),
                'birthday' => '2010-01-01',
                'role' => UserRole::STUDENT,
                'is_active' => true,
            ]
        );

        $parentUser = User::query()->updateOrCreate(
            ['email' => "parent.{$lrn}@marriott.edu"],
            [
                'first_name' => $nameSet['guardian_first_name'],
                'last_name' => $nameSet['student_last_name'],
                'name' => "{$nameSet['guardian_first_name']} {$nameSet['student_last_name']}",
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
                'first_name' => $nameSet['student_first_name'],
                'last_name' => $nameSet['student_last_name'],
                'gender' => $index % 2 === 0 ? 'Male' : 'Female',
                'birthdate' => '2010-01-01',
                'guardian_name' => "{$nameSet['guardian_first_name']} {$nameSet['student_last_name']}",
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

    /**
     * @return array{student_first_name: string, student_last_name: string, guardian_first_name: string}
     */
    private function nameSetForIndex(int $index): array
    {
        return SeedNameBank::studentIdentity($index);
    }
}
