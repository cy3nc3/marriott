<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
use App\Models\Attendance;
use App\Models\BillingSchedule;
use App\Models\ClassSchedule;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\Fee;
use App\Models\GradedActivity;
use App\Models\GradeLevel;
use App\Models\GradeSubmission;
use App\Models\InventoryItem;
use App\Models\LedgerEntry;
use App\Models\RemedialSubjectFee;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\StudentScore;
use App\Models\Subject;
use App\Models\SubjectAssignment;
use App\Models\TeacherSubject;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Finance\BillingScheduleService;
use App\Services\Finance\DiscountBucketCalculator;
use Carbon\CarbonImmutable;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class ProductionQuarterOneDayFifteenSeeder extends Seeder
{
    private const QUARTER = '1';

    private const SCHOOL_DAYS_TO_SEED = 15;

    private const STUDENTS_PER_SECTION = 25;

    private const WEEK_DAYS = [
        'Monday',
        'Tuesday',
        'Wednesday',
        'Thursday',
        'Friday',
    ];

    /**
     * Extracted from image.png (Grades 7-10 only).
     *
     * @var array<int, array<int, string>>
     */
    private const SECTION_BLUEPRINT = [
        7 => ['St. Paul'],
        8 => ['St. Anthony'],
        9 => ['St. Francis'],
        10 => ['St. John', 'St. Anne'],
    ];

    /**
     * Extracted from TSS Schedule SY 25-26.xlsx.
     *
     * @var array<int, array{
     *     first_name: string,
     *     last_name: string,
     *     email: string,
     *     subject: string,
     *     slots: array<int, array{grade: int, section: string, start: string, end: string}>
     * }>
     */
    private const TEACHER_SCHEDULE_BLUEPRINT = [
        [
            'first_name' => 'Rowell',
            'last_name' => 'Almonte',
            'email' => 'rowell.almonte@marriott.edu',
            'subject' => 'Filipino',
            'slots' => [
                ['grade' => 7, 'section' => 'St. Paul', 'start' => '14:00', 'end' => '14:50'],
                ['grade' => 8, 'section' => 'St. Anthony', 'start' => '10:40', 'end' => '11:30'],
                ['grade' => 9, 'section' => 'St. Francis', 'start' => '13:00', 'end' => '13:50'],
                ['grade' => 10, 'section' => 'St. John', 'start' => '09:40', 'end' => '10:30'],
                ['grade' => 10, 'section' => 'St. Anne', 'start' => '08:00', 'end' => '08:50'],
            ],
        ],
        [
            'first_name' => 'Rocelle',
            'last_name' => 'De la Cruz',
            'email' => 'rocelle.delacruz@marriott.edu',
            'subject' => 'Math',
            'slots' => [
                ['grade' => 7, 'section' => 'St. Paul', 'start' => '09:50', 'end' => '10:50'],
                ['grade' => 8, 'section' => 'St. Anthony', 'start' => '07:20', 'end' => '08:20'],
                ['grade' => 9, 'section' => 'St. Francis', 'start' => '10:50', 'end' => '11:50'],
                ['grade' => 10, 'section' => 'St. John', 'start' => '08:20', 'end' => '09:20'],
                ['grade' => 10, 'section' => 'St. Anne', 'start' => '13:10', 'end' => '14:10'],
            ],
        ],
        [
            'first_name' => 'Fe Mercedes',
            'last_name' => 'Cavitt',
            'email' => 'fe.cavitt@marriott.edu',
            'subject' => 'Edukasyon sa Pagpapakatao',
            'slots' => [
                ['grade' => 7, 'section' => 'St. Paul', 'start' => '07:20', 'end' => '08:10'],
                ['grade' => 8, 'section' => 'St. Anthony', 'start' => '12:40', 'end' => '13:30'],
                ['grade' => 9, 'section' => 'St. Francis', 'start' => '10:00', 'end' => '10:50'],
                ['grade' => 10, 'section' => 'St. John', 'start' => '11:10', 'end' => '12:00'],
                ['grade' => 10, 'section' => 'St. Anne', 'start' => '08:50', 'end' => '09:40'],
            ],
        ],
        [
            'first_name' => 'Elenor',
            'last_name' => 'Cendana',
            'email' => 'elenor.cendana@marriott.edu',
            'subject' => 'English',
            'slots' => [
                ['grade' => 7, 'section' => 'St. Paul', 'start' => '10:50', 'end' => '11:50'],
                ['grade' => 8, 'section' => 'St. Anthony', 'start' => '09:40', 'end' => '10:40'],
                ['grade' => 9, 'section' => 'St. Francis', 'start' => '08:00', 'end' => '09:00'],
                ['grade' => 10, 'section' => 'St. John', 'start' => '13:50', 'end' => '14:50'],
                ['grade' => 10, 'section' => 'St. Anne', 'start' => '12:10', 'end' => '13:10'],
            ],
        ],
        [
            'first_name' => 'Ma Nimfa',
            'last_name' => 'Guinacaran',
            'email' => 'manimfa.guinacaran@marriott.edu',
            'subject' => 'MAPEH',
            'slots' => [
                ['grade' => 7, 'section' => 'St. Paul', 'start' => '08:50', 'end' => '09:30'],
                ['grade' => 8, 'section' => 'St. Anthony', 'start' => '13:30', 'end' => '14:10'],
                ['grade' => 9, 'section' => 'St. Francis', 'start' => '07:20', 'end' => '08:00'],
                ['grade' => 10, 'section' => 'St. John', 'start' => '12:30', 'end' => '13:10'],
                ['grade' => 10, 'section' => 'St. Anne', 'start' => '11:00', 'end' => '11:40'],
            ],
        ],
        [
            'first_name' => 'Mary Joyce',
            'last_name' => 'Guira',
            'email' => 'maryjoyce.guira@marriott.edu',
            'subject' => 'Araling Panlipunan',
            'slots' => [
                ['grade' => 7, 'section' => 'St. Paul', 'start' => '08:10', 'end' => '08:50'],
                ['grade' => 8, 'section' => 'St. Anthony', 'start' => '14:10', 'end' => '14:50'],
                ['grade' => 9, 'section' => 'St. Francis', 'start' => '09:00', 'end' => '09:40'],
                ['grade' => 10, 'section' => 'St. John', 'start' => '13:10', 'end' => '13:50'],
                ['grade' => 10, 'section' => 'St. Anne', 'start' => '07:20', 'end' => '08:00'],
            ],
        ],
        [
            'first_name' => 'Racquel',
            'last_name' => 'Vergara',
            'email' => 'racquel.vergara@marriott.edu',
            'subject' => 'Technology and Livelihood Education',
            'slots' => [
                ['grade' => 7, 'section' => 'St. Paul', 'start' => '13:20', 'end' => '14:00'],
                ['grade' => 8, 'section' => 'St. Anthony', 'start' => '11:30', 'end' => '12:10'],
                ['grade' => 9, 'section' => 'St. Francis', 'start' => '12:20', 'end' => '13:00'],
                ['grade' => 10, 'section' => 'St. John', 'start' => '10:30', 'end' => '11:10'],
                ['grade' => 10, 'section' => 'St. Anne', 'start' => '14:10', 'end' => '14:50'],
            ],
        ],
        [
            'first_name' => 'Beronica',
            'last_name' => 'Renton',
            'email' => 'beronica.renton@marriott.edu',
            'subject' => 'Science',
            'slots' => [
                ['grade' => 7, 'section' => 'St. Paul', 'start' => '12:20', 'end' => '13:20'],
                ['grade' => 8, 'section' => 'St. Anthony', 'start' => '08:20', 'end' => '09:20'],
                ['grade' => 9, 'section' => 'St. Francis', 'start' => '13:50', 'end' => '14:50'],
                ['grade' => 10, 'section' => 'St. John', 'start' => '07:20', 'end' => '08:20'],
                ['grade' => 10, 'section' => 'St. Anne', 'start' => '10:00', 'end' => '11:00'],
            ],
        ],
    ];

    /**
     * @var array<string, string>
     */
    private const SECTION_ADVISER_BY_KEY = [
        '7|St. Paul' => 'maryjoyce.guira@marriott.edu',
        '8|St. Anthony' => 'rocelle.delacruz@marriott.edu',
        '9|St. Francis' => 'elenor.cendana@marriott.edu',
        '10|St. John' => 'beronica.renton@marriott.edu',
        '10|St. Anne' => 'rowell.almonte@marriott.edu',
    ];

    /**
     * @var array<int, string>
     */
    private const FIRST_NAMES = [
        'Arielle', 'Bea', 'Carlo', 'Dani', 'Eli', 'Faith', 'Gabriel', 'Hana', 'Ian', 'Janna', 'Kyle', 'Lia',
        'Mika', 'Noah', 'Olive', 'Paolo', 'Quinn', 'Rica', 'Sean', 'Tala', 'Uriel', 'Vince', 'Wena', 'Xian',
        'Ysa', 'Zach',
    ];

    /**
     * @var array<int, string>
     */
    private const LAST_NAMES = [
        'Abad', 'Bautista', 'Cruz', 'Dizon', 'Escobar', 'Fernandez', 'Garcia', 'Hernandez', 'Ilagan', 'Jimenez',
        'Luna', 'Mendoza', 'Navarro', 'Ocampo', 'Pascual', 'Quinto', 'Ramos', 'Santos', 'Tolentino', 'Umali',
        'Valencia', 'Wong', 'Yap', 'Zamora',
    ];

    /**
     * @var array<string, float>
     */
    private const DOWNPAYMENT_BY_TERM = [
        'cash' => 0.0,
        'monthly' => 4000.0,
        'quarterly' => 5000.0,
        'semi-annual' => 6500.0,
    ];

    private const PAYMENT_TERMS = ['cash', 'monthly', 'quarterly', 'semi-annual'];

    private const PAYMENT_MODES = ['cash', 'gcash', 'bank_transfer'];

    private string $passwordHash = '';

    public function run(): void
    {
        $this->passwordHash = Hash::make('password');

        $this->call([
            ProductionBaselineSeeder::class,
            PermissionSeeder::class,
        ]);

        $academicYear = $this->prepareAcademicYear();
        $gradeLevels = GradeLevel::query()
            ->orderBy('level_order')
            ->get()
            ->keyBy('level_order');

        $this->seedDefaultStaffAccounts();

        $sections = $this->seedSectionsFromImage($academicYear, $gradeLevels);
        $teachers = $this->seedTeachersAndSchedulesFromWorkbook($sections, $gradeLevels);
        $this->assignSectionAdvisers($sections, $teachers);

        $this->seedFeeStructure($academicYear, $gradeLevels);
        $inventoryItems = $this->seedInventoryItems();
        $discounts = $this->seedDiscounts();

        $enrollments = $this->seedStudentsAndEnrollments($academicYear, $sections);
        $this->seedStudentDiscounts($academicYear, $enrollments, $discounts);
        $this->seedBillingSchedules($enrollments);
        $this->seedOpeningLedgerCharges($academicYear, $enrollments);
        $this->seedDailyTransactions($academicYear, $enrollments, $inventoryItems);
        $this->seedQuarterOneActivitiesAndScores($academicYear, $enrollments);
        $this->seedQuarterOneAttendance($academicYear, $enrollments);

        $this->call(SuperAdminSeeder::class);
    }

    private function prepareAcademicYear(): AcademicYear
    {
        $academicYear = AcademicYear::query()->updateOrCreate(
            ['name' => '2025-2026'],
            [
                'start_date' => '2025-06-02',
                'end_date' => '2026-03-27',
                'status' => 'ongoing',
                'current_quarter' => self::QUARTER,
            ]
        );

        AcademicYear::query()
            ->where('id', '!=', $academicYear->id)
            ->where('status', 'ongoing')
            ->update([
                'status' => 'completed',
                'current_quarter' => '4',
            ]);

        AcademicYear::query()
            ->where('name', '2024-2025')
            ->update([
                'status' => 'completed',
                'current_quarter' => '4',
            ]);

        return $academicYear;
    }

    private function seedDefaultStaffAccounts(): void
    {
        $staffBlueprint = [
            [
                'role' => UserRole::SUPER_ADMIN,
                'email' => 'superadmin@marriott.edu',
                'first_name' => 'Test',
                'last_name' => 'Super Admin',
            ],
            [
                'role' => UserRole::ADMIN,
                'email' => 'admin@marriott.edu',
                'first_name' => 'Test',
                'last_name' => 'Admin',
            ],
            [
                'role' => UserRole::REGISTRAR,
                'email' => 'registrar@marriott.edu',
                'first_name' => 'Test',
                'last_name' => 'Registrar',
            ],
            [
                'role' => UserRole::FINANCE,
                'email' => 'finance@marriott.edu',
                'first_name' => 'Test',
                'last_name' => 'Finance',
            ],
        ];

        foreach ($staffBlueprint as $staffData) {
            User::query()->updateOrCreate(
                ['email' => $staffData['email']],
                [
                    'first_name' => $staffData['first_name'],
                    'last_name' => $staffData['last_name'],
                    'name' => trim("{$staffData['first_name']} {$staffData['last_name']}"),
                    'password' => $this->passwordHash,
                    'birthday' => '1985-01-01',
                    'role' => $staffData['role'],
                    'is_active' => true,
                    'must_change_password' => false,
                    'password_updated_at' => now(),
                ]
            );
        }
    }

    /**
     * @param  Collection<int, GradeLevel>  $gradeLevels
     * @return Collection<int, Section>
     */
    private function seedSectionsFromImage(AcademicYear $academicYear, Collection $gradeLevels): Collection
    {
        $existingSectionIds = Section::query()
            ->where('academic_year_id', $academicYear->id)
            ->pluck('id');

        if ($existingSectionIds->isNotEmpty()) {
            ClassSchedule::query()->whereIn('section_id', $existingSectionIds)->delete();
            SubjectAssignment::query()->whereIn('section_id', $existingSectionIds)->delete();
            Section::query()->whereIn('id', $existingSectionIds)->delete();
        }

        $sections = collect();

        foreach (self::SECTION_BLUEPRINT as $gradeOrder => $sectionNames) {
            $gradeLevel = $gradeLevels->get($gradeOrder);
            if (! $gradeLevel instanceof GradeLevel) {
                continue;
            }

            foreach ($sectionNames as $sectionName) {
                $sections->push(Section::query()->create([
                    'academic_year_id' => $academicYear->id,
                    'grade_level_id' => $gradeLevel->id,
                    'name' => $sectionName,
                ]));
            }
        }

        return $sections->values();
    }

    /**
     * @param  Collection<int, Section>  $sections
     * @param  Collection<int, GradeLevel>  $gradeLevels
     * @return Collection<string, User>
     */
    private function seedTeachersAndSchedulesFromWorkbook(Collection $sections, Collection $gradeLevels): Collection
    {
        $teachersByEmail = collect();

        foreach (self::TEACHER_SCHEDULE_BLUEPRINT as $teacherData) {
            $teacherUser = User::query()->updateOrCreate(
                ['email' => $teacherData['email']],
                [
                    'first_name' => $teacherData['first_name'],
                    'last_name' => $teacherData['last_name'],
                    'name' => trim("{$teacherData['first_name']} {$teacherData['last_name']}"),
                    'password' => $this->passwordHash,
                    'birthday' => '1984-01-01',
                    'role' => UserRole::TEACHER,
                    'is_active' => true,
                    'must_change_password' => false,
                    'password_updated_at' => now(),
                ]
            );

            $teachersByEmail->put($teacherData['email'], $teacherUser);

            foreach ($teacherData['slots'] as $slot) {
                $gradeLevel = $gradeLevels->get((int) $slot['grade']);
                if (! $gradeLevel instanceof GradeLevel) {
                    continue;
                }

                /** @var Section|null $section */
                $section = $sections->first(function (Section $candidateSection) use ($gradeLevel, $slot): bool {
                    return (int) $candidateSection->grade_level_id === (int) $gradeLevel->id
                        && (string) $candidateSection->name === (string) $slot['section'];
                });

                if (! $section instanceof Section) {
                    continue;
                }

                $subject = Subject::query()
                    ->where('grade_level_id', $gradeLevel->id)
                    ->where('subject_name', "{$teacherData['subject']} {$slot['grade']}")
                    ->first();

                if (! $subject instanceof Subject) {
                    continue;
                }

                $teacherSubject = TeacherSubject::query()->firstOrCreate([
                    'teacher_id' => $teacherUser->id,
                    'subject_id' => $subject->id,
                ]);

                $subjectAssignment = SubjectAssignment::query()->firstOrCreate([
                    'section_id' => $section->id,
                    'teacher_subject_id' => $teacherSubject->id,
                ]);

                $startTime = $this->formatScheduleTime((string) $slot['start']);
                $endTime = $this->formatScheduleTime((string) $slot['end']);

                foreach (self::WEEK_DAYS as $day) {
                    ClassSchedule::query()->updateOrCreate(
                        [
                            'section_id' => $section->id,
                            'subject_assignment_id' => $subjectAssignment->id,
                            'day' => $day,
                            'start_time' => $startTime,
                            'end_time' => $endTime,
                        ],
                        [
                            'type' => 'academic',
                            'label' => null,
                        ]
                    );
                }
            }
        }

        return $teachersByEmail;
    }

    /**
     * @param  Collection<int, Section>  $sections
     * @param  Collection<string, User>  $teachers
     */
    private function assignSectionAdvisers(Collection $sections, Collection $teachers): void
    {
        foreach ($sections as $section) {
            $key = $this->sectionKey((int) $section->gradeLevel?->level_order, (string) $section->name);
            $adviserEmail = self::SECTION_ADVISER_BY_KEY[$key] ?? null;
            if (! is_string($adviserEmail)) {
                continue;
            }

            /** @var User|null $adviser */
            $adviser = $teachers->get($adviserEmail);
            if (! $adviser instanceof User) {
                continue;
            }

            $section->update([
                'adviser_id' => $adviser->id,
            ]);
        }
    }

    /**
     * @param  Collection<int, GradeLevel>  $gradeLevels
     */
    private function seedFeeStructure(AcademicYear $academicYear, Collection $gradeLevels): void
    {
        foreach ($gradeLevels as $gradeLevel) {
            $gradeOrder = (int) $gradeLevel->level_order;
            $tuition = 33000 + (($gradeOrder - 7) * 1200);
            $miscellaneous = 7000 + (($gradeOrder - 7) * 300);
            $booksAndModules = 3200 + (($gradeOrder - 7) * 100);
            $energyFee = 1500;

            $feeRows = [
                ['type' => 'tuition', 'name' => 'Tuition Fee', 'amount' => $tuition],
                ['type' => 'miscellaneous', 'name' => 'Miscellaneous Fee', 'amount' => $miscellaneous],
                ['type' => 'books_modules', 'name' => 'Books and Modules', 'amount' => $booksAndModules],
                ['type' => 'other', 'name' => 'Energy and Facilities', 'amount' => $energyFee],
            ];

            foreach ($feeRows as $feeRow) {
                Fee::query()->updateOrCreate(
                    [
                        'grade_level_id' => $gradeLevel->id,
                        'academic_year_id' => $academicYear->id,
                        'type' => $feeRow['type'],
                        'name' => $feeRow['name'],
                    ],
                    [
                        'amount' => $feeRow['amount'],
                    ]
                );
            }
        }

        $subjects = Subject::query()
            ->whereIn('grade_level_id', $gradeLevels->pluck('id'))
            ->get();

        foreach ($subjects as $subject) {
            RemedialSubjectFee::query()->updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'subject_id' => $subject->id,
                ],
                [
                    'amount' => 850,
                ]
            );
        }
    }

    /**
     * @return Collection<int, InventoryItem>
     */
    private function seedInventoryItems(): Collection
    {
        $inventoryBlueprint = [
            ['name' => 'PE Uniform Set', 'price' => 850, 'type' => 'Uniform'],
            ['name' => 'School ID Replacement', 'price' => 250, 'type' => 'Others'],
            ['name' => 'Science Lab Manual', 'price' => 380, 'type' => 'Book'],
            ['name' => 'School Patch and Tie', 'price' => 420, 'type' => 'Merchandise'],
        ];

        $items = collect();

        foreach ($inventoryBlueprint as $inventoryRow) {
            $items->push(
                InventoryItem::query()->updateOrCreate(
                    ['name' => $inventoryRow['name']],
                    [
                        'price' => $inventoryRow['price'],
                        'type' => $inventoryRow['type'],
                    ]
                )
            );
        }

        return $items;
    }

    /**
     * @return Collection<string, Discount>
     */
    private function seedDiscounts(): Collection
    {
        $discountRows = [
            ['name' => 'Sibling Discount', 'type' => 'percentage', 'value' => 10, 'export_bucket' => 'tuition_sibling_discount'],
            ['name' => 'Academic Scholarship', 'type' => 'percentage', 'value' => 20, 'export_bucket' => 'special_discount'],
            ['name' => 'Early Enrollment Discount', 'type' => 'fixed', 'value' => 1500, 'export_bucket' => 'early_enrollment_discount'],
        ];

        return collect($discountRows)
            ->mapWithKeys(function (array $discountRow): array {
                $discount = Discount::query()->updateOrCreate(
                    ['name' => $discountRow['name']],
                    [
                        'type' => $discountRow['type'],
                        'value' => $discountRow['value'],
                        'export_bucket' => $discountRow['export_bucket'],
                    ]
                );

                return [$discountRow['name'] => $discount];
            });
    }

    /**
     * @param  Collection<int, Section>  $sections
     * @return Collection<int, Enrollment>
     */
    private function seedStudentsAndEnrollments(AcademicYear $academicYear, Collection $sections): Collection
    {
        $enrollments = collect();

        $orderedSections = Section::query()
            ->with('gradeLevel')
            ->whereIn('id', $sections->pluck('id')->all())
            ->get()
            ->sortBy(function (Section $section): string {
                $gradeOrder = str_pad((string) $section->gradeLevel?->level_order, 2, '0', STR_PAD_LEFT);

                return "{$gradeOrder}|{$section->name}";
            })
            ->values();

        foreach ($orderedSections as $sectionIndex => $section) {
            $gradeOrder = (int) $section->gradeLevel?->level_order;
            $birthYear = 2013 - max($gradeOrder - 7, 0);

            for ($studentIndex = 1; $studentIndex <= self::STUDENTS_PER_SECTION; $studentIndex++) {
                $firstName = self::FIRST_NAMES[($sectionIndex + $studentIndex) % count(self::FIRST_NAMES)];
                $lastName = self::LAST_NAMES[($sectionIndex * 3 + $studentIndex) % count(self::LAST_NAMES)];
                $middleName = chr(65 + (($sectionIndex + $studentIndex) % 26));
                $gender = $studentIndex % 2 === 0 ? 'Male' : 'Female';
                $lrn = sprintf('2526%02d%02d%04d', $gradeOrder, $sectionIndex + 1, $studentIndex);
                $birthdate = CarbonImmutable::create(
                    $birthYear,
                    (($studentIndex - 1) % 12) + 1,
                    (($studentIndex - 1) % 27) + 1
                )->toDateString();
                $studentEmail = strtolower("{$firstName}.{$lastName}.{$lrn}@marriott.edu");
                $studentEmail = (string) preg_replace('/[^a-z0-9@.]/', '', $studentEmail);
                $parentEmail = "parent.{$lrn}@marriott.edu";

                $studentUser = User::query()->updateOrCreate(
                    ['email' => $studentEmail],
                    [
                        'first_name' => $firstName,
                        'last_name' => $lastName,
                        'name' => trim("{$firstName} {$lastName}"),
                        'password' => $this->passwordHash,
                        'birthday' => $birthdate,
                        'role' => UserRole::STUDENT,
                        'is_active' => true,
                        'must_change_password' => true,
                        'password_updated_at' => now(),
                    ]
                );

                $parentUser = User::query()->updateOrCreate(
                    ['email' => $parentEmail],
                    [
                        'first_name' => 'Parent',
                        'last_name' => $lastName,
                        'name' => "Parent {$lastName}",
                        'password' => $this->passwordHash,
                        'birthday' => '1983-01-01',
                        'role' => UserRole::PARENT,
                        'is_active' => true,
                        'must_change_password' => true,
                        'password_updated_at' => now(),
                    ]
                );

                $student = Student::query()->updateOrCreate(
                    ['lrn' => $lrn],
                    [
                        'user_id' => $studentUser->id,
                        'first_name' => $firstName,
                        'middle_name' => "{$middleName}.",
                        'last_name' => $lastName,
                        'gender' => $gender,
                        'birthdate' => $birthdate,
                        'contact_number' => '+639'.substr($lrn, 0, 9),
                        'address' => "{$section->name}, San Francisco Del Monte, Quezon City",
                        'guardian_name' => "Parent {$lastName}",
                        'is_lis_synced' => true,
                        'sync_error_flag' => false,
                        'sync_error_notes' => null,
                        'is_for_remedial' => false,
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

                $paymentTerm = self::PAYMENT_TERMS[($sectionIndex + $studentIndex - 1) % count(self::PAYMENT_TERMS)];
                $downpayment = self::DOWNPAYMENT_BY_TERM[$paymentTerm] ?? 0.0;

                $enrollment = Enrollment::query()->updateOrCreate(
                    [
                        'student_id' => $student->id,
                        'academic_year_id' => $academicYear->id,
                    ],
                    [
                        'grade_level_id' => $section->grade_level_id,
                        'section_id' => $section->id,
                        'payment_term' => $paymentTerm,
                        'downpayment' => $downpayment,
                        'status' => 'enrolled',
                    ]
                );

                $enrollments->push($enrollment);
            }
        }

        return $enrollments;
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @param  Collection<string, Discount>  $discounts
     */
    private function seedStudentDiscounts(
        AcademicYear $academicYear,
        Collection $enrollments,
        Collection $discounts
    ): void {
        $studentIds = $enrollments
            ->pluck('student_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->values();

        StudentDiscount::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('student_id', $studentIds)
            ->delete();

        $siblingDiscount = $discounts->get('Sibling Discount');
        $scholarshipDiscount = $discounts->get('Academic Scholarship');
        $earlyEnrollmentDiscount = $discounts->get('Early Enrollment Discount');

        foreach ($enrollments->values() as $index => $enrollment) {
            if (! $enrollment instanceof Enrollment) {
                continue;
            }

            if ($siblingDiscount instanceof Discount && $index % 7 === 0) {
                StudentDiscount::query()->updateOrCreate(
                    [
                        'student_id' => $enrollment->student_id,
                        'discount_id' => $siblingDiscount->id,
                        'academic_year_id' => $academicYear->id,
                    ],
                    []
                );
            }

            if ($scholarshipDiscount instanceof Discount && $index % 19 === 0) {
                StudentDiscount::query()->updateOrCreate(
                    [
                        'student_id' => $enrollment->student_id,
                        'discount_id' => $scholarshipDiscount->id,
                        'academic_year_id' => $academicYear->id,
                    ],
                    []
                );
            }

            if ($earlyEnrollmentDiscount instanceof Discount && $index % 11 === 0) {
                StudentDiscount::query()->updateOrCreate(
                    [
                        'student_id' => $enrollment->student_id,
                        'discount_id' => $earlyEnrollmentDiscount->id,
                        'academic_year_id' => $academicYear->id,
                    ],
                    []
                );
            }
        }
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     */
    private function seedBillingSchedules(Collection $enrollments): void
    {
        /** @var BillingScheduleService $billingScheduleService */
        $billingScheduleService = app(BillingScheduleService::class);

        foreach ($enrollments as $enrollment) {
            if (! $enrollment instanceof Enrollment) {
                continue;
            }

            $billingScheduleService->syncForEnrollment($enrollment);
        }
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     */
    private function seedOpeningLedgerCharges(AcademicYear $academicYear, Collection $enrollments): void
    {
        foreach ($enrollments as $enrollment) {
            if (! $enrollment instanceof Enrollment) {
                continue;
            }

            $assessmentFeeTotal = $this->resolveAssessmentFeeTotal(
                (int) $enrollment->grade_level_id,
                (int) $academicYear->id
            );
            $discountAmount = $this->resolveDiscountAmount(
                (int) $enrollment->student_id,
                (int) $academicYear->id,
                $assessmentFeeTotal
            );
            $openingBalance = round(max($assessmentFeeTotal - $discountAmount, 0), 2);

            LedgerEntry::query()->updateOrCreate(
                [
                    'student_id' => $enrollment->student_id,
                    'academic_year_id' => $academicYear->id,
                    'date' => $academicYear->start_date,
                    'description' => 'Opening Balance (Q1 Start)',
                ],
                [
                    'debit' => $openingBalance,
                    'credit' => null,
                    'running_balance' => $openingBalance,
                    'reference_id' => null,
                ]
            );
        }
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     * @param  Collection<int, InventoryItem>  $inventoryItems
     */
    private function seedDailyTransactions(
        AcademicYear $academicYear,
        Collection $enrollments,
        Collection $inventoryItems
    ): void {
        $cashier = User::query()
            ->where('role', UserRole::FINANCE)
            ->orderBy('id')
            ->first();

        if (! $cashier instanceof User) {
            return;
        }

        $schoolDays = $this->firstSchoolDays((string) $academicYear->start_date, self::SCHOOL_DAYS_TO_SEED);
        $enrollmentPool = Enrollment::query()
            ->with('student')
            ->whereIn('id', $enrollments->pluck('id')->all())
            ->orderBy('id')
            ->get()
            ->values();

        if ($enrollmentPool->isEmpty()) {
            return;
        }

        $tuitionFeesByGrade = Fee::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('type', 'tuition')
            ->get()
            ->keyBy('grade_level_id');

        $orSequence = 1;
        $transactionsPerDay = 4;

        foreach ($schoolDays as $dayIndex => $schoolDay) {
            for ($transactionIndex = 0; $transactionIndex < $transactionsPerDay; $transactionIndex++) {
                /** @var Enrollment|null $enrollment */
                $enrollment = $enrollmentPool->get(
                    ($dayIndex * $transactionsPerDay + $transactionIndex) % $enrollmentPool->count()
                );

                $student = $enrollment?->student;
                if (! $enrollment instanceof Enrollment || ! $student instanceof Student) {
                    continue;
                }

                $billingSchedule = BillingSchedule::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->whereIn('status', ['unpaid', 'partially_paid'])
                    ->orderBy('due_date')
                    ->orderBy('id')
                    ->first();

                $assessmentAmount = 0.0;
                $transactionItems = collect();
                $tuitionFee = $tuitionFeesByGrade->get((int) $enrollment->grade_level_id);

                if ($billingSchedule instanceof BillingSchedule) {
                    $outstandingAmount = max(
                        (float) $billingSchedule->amount_due - (float) $billingSchedule->amount_paid,
                        0
                    );

                    if ($outstandingAmount > 0) {
                        $assessmentAmount = round(min($outstandingAmount, 1100 + (($dayIndex + $transactionIndex) % 5) * 250), 2);
                        if ($assessmentAmount > 0) {
                            $transactionItems->push([
                                'type' => 'assessment_fee',
                                'description' => "Assessment Payment - {$billingSchedule->description}",
                                'amount' => $assessmentAmount,
                                'fee_id' => $tuitionFee?->id,
                                'inventory_item_id' => null,
                            ]);
                        }
                    }
                }

                if ($inventoryItems->isNotEmpty() && ($dayIndex + $transactionIndex) % 3 === 0) {
                    /** @var InventoryItem $inventoryItem */
                    $inventoryItem = $inventoryItems[($dayIndex + $transactionIndex) % $inventoryItems->count()];
                    $transactionItems->push([
                        'type' => 'inventory',
                        'description' => $inventoryItem->name,
                        'amount' => (float) $inventoryItem->price,
                        'fee_id' => null,
                        'inventory_item_id' => $inventoryItem->id,
                    ]);
                }

                if ($transactionItems->isEmpty()) {
                    $transactionItems->push([
                        'type' => 'custom',
                        'description' => 'Cashier Counter Adjustment',
                        'amount' => 500.0,
                        'fee_id' => null,
                        'inventory_item_id' => null,
                    ]);
                }

                $totalAmount = round((float) $transactionItems->sum('amount'), 2);
                $timestamp = $schoolDay
                    ->setTime(8, 30)
                    ->addMinutes($transactionIndex * 20);
                $orNumber = sprintf('OR-2526-%06d', $orSequence++);

                $transaction = Transaction::query()->create([
                    'or_number' => $orNumber,
                    'student_id' => $student->id,
                    'cashier_id' => $cashier->id,
                    'total_amount' => $totalAmount,
                    'payment_mode' => self::PAYMENT_MODES[($dayIndex + $transactionIndex) % count(self::PAYMENT_MODES)],
                    'reference_no' => null,
                    'remarks' => 'Seeded day-15 quarter one transaction.',
                    'status' => 'posted',
                ]);

                $transaction->timestamps = false;
                $transaction->created_at = $timestamp;
                $transaction->updated_at = $timestamp;
                $transaction->save();

                $transaction->items()->createMany(
                    $transactionItems
                        ->map(function (array $item): array {
                            return [
                                'fee_id' => $item['fee_id'],
                                'inventory_item_id' => $item['inventory_item_id'],
                                'description' => $item['description'],
                                'amount' => $item['amount'],
                            ];
                        })
                        ->all()
                );

                $this->allocatePaymentAcrossDues(
                    $transaction,
                    $student,
                    $academicYear,
                    $assessmentAmount
                );

                $previousRunningBalance = (float) (LedgerEntry::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $academicYear->id)
                    ->latest('date')
                    ->latest('id')
                    ->value('running_balance') ?? 0);

                LedgerEntry::query()->create([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'date' => $timestamp->toDateString(),
                    'description' => "Payment ({$orNumber})",
                    'debit' => null,
                    'credit' => $totalAmount,
                    'running_balance' => round($previousRunningBalance - $totalAmount, 2),
                    'reference_id' => $transaction->id,
                ]);
            }
        }
    }

    private function allocatePaymentAcrossDues(
        Transaction $transaction,
        Student $student,
        AcademicYear $academicYear,
        float $paymentAmount
    ): void {
        $remainingPaymentCents = (int) round(max($paymentAmount, 0) * 100);

        if ($remainingPaymentCents <= 0) {
            return;
        }

        $billingSchedules = BillingSchedule::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('status', ['unpaid', 'partially_paid'])
            ->orderBy('due_date')
            ->orderBy('id')
            ->get();

        foreach ($billingSchedules as $billingSchedule) {
            if ($remainingPaymentCents <= 0) {
                break;
            }

            $amountDueCents = (int) round((float) $billingSchedule->amount_due * 100);
            $amountPaidCents = (int) round((float) $billingSchedule->amount_paid * 100);
            $outstandingCents = max($amountDueCents - $amountPaidCents, 0);

            if ($outstandingCents <= 0) {
                continue;
            }

            $appliedCents = min($remainingPaymentCents, $outstandingCents);
            $newPaidCents = $amountPaidCents + $appliedCents;

            $billingSchedule->update([
                'amount_paid' => round($newPaidCents / 100, 2),
                'status' => $newPaidCents >= $amountDueCents ? 'paid' : 'partially_paid',
            ]);

            $transaction->dueAllocations()->create([
                'billing_schedule_id' => $billingSchedule->id,
                'amount' => round($appliedCents / 100, 2),
            ]);

            $remainingPaymentCents -= $appliedCents;
        }
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     */
    private function seedQuarterOneActivitiesAndScores(AcademicYear $academicYear, Collection $enrollments): void
    {
        $sectionIds = $enrollments
            ->pluck('section_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $assignments = SubjectAssignment::query()
            ->with(['teacherSubject.subject'])
            ->whereIn('section_id', $sectionIds)
            ->orderBy('section_id')
            ->orderBy('id')
            ->get();

        $enrollmentsBySection = $enrollments->groupBy('section_id');

        foreach ($assignments as $assignmentIndex => $assignment) {
            $subjectName = (string) Str::beforeLast(
                (string) $assignment->teacherSubject?->subject?->subject_name,
                ' '
            );
            if ($subjectName === '') {
                $subjectName = 'Subject';
            }

            $activityBlueprint = [
                ['type' => 'WW', 'title' => "Q1 {$subjectName} Written Work 1", 'max_score' => 50],
                ['type' => 'WW', 'title' => "Q1 {$subjectName} Written Work 2", 'max_score' => 50],
                ['type' => 'PT', 'title' => "Q1 {$subjectName} Performance Task 1", 'max_score' => 100],
            ];

            foreach ($activityBlueprint as $activityIndex => $activityData) {
                $activity = GradedActivity::query()->updateOrCreate(
                    [
                        'subject_assignment_id' => $assignment->id,
                        'type' => $activityData['type'],
                        'quarter' => self::QUARTER,
                        'title' => $activityData['title'],
                    ],
                    [
                        'max_score' => $activityData['max_score'],
                    ]
                );

                $sectionEnrollments = $enrollmentsBySection->get((int) $assignment->section_id, collect());
                foreach ($sectionEnrollments as $enrollmentIndex => $sectionEnrollment) {
                    if (! $sectionEnrollment instanceof Enrollment) {
                        continue;
                    }

                    $minimumScore = (int) round((int) $activityData['max_score'] * 0.72);
                    $scoreSpan = max((int) $activityData['max_score'] - $minimumScore, 1);
                    $score = min(
                        (int) $activityData['max_score'],
                        $minimumScore + (($assignmentIndex + $activityIndex + $enrollmentIndex) % ($scoreSpan + 1))
                    );

                    StudentScore::query()->updateOrCreate(
                        [
                            'student_id' => $sectionEnrollment->student_id,
                            'graded_activity_id' => $activity->id,
                        ],
                        [
                            'score' => $score,
                        ]
                    );
                }
            }

            $isSubmitted = $assignmentIndex % 3 !== 0;
            GradeSubmission::query()->updateOrCreate(
                [
                    'academic_year_id' => $academicYear->id,
                    'subject_assignment_id' => $assignment->id,
                    'quarter' => self::QUARTER,
                ],
                [
                    'status' => $isSubmitted ? GradeSubmission::STATUS_SUBMITTED : GradeSubmission::STATUS_DRAFT,
                    'submitted_by' => $isSubmitted ? $assignment->teacherSubject?->teacher_id : null,
                    'submitted_at' => $isSubmitted
                        ? CarbonImmutable::parse((string) $academicYear->start_date)
                            ->addDays(10 + ($assignmentIndex % 4))
                            ->setTime(16, 0)
                            ->toDateTimeString()
                        : null,
                ]
            );
        }
    }

    /**
     * @param  Collection<int, Enrollment>  $enrollments
     */
    private function seedQuarterOneAttendance(AcademicYear $academicYear, Collection $enrollments): void
    {
        $sectionIds = $enrollments
            ->pluck('section_id')
            ->filter()
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        $classSchedules = ClassSchedule::query()
            ->whereIn('section_id', $sectionIds)
            ->where('type', 'academic')
            ->whereNotNull('subject_assignment_id')
            ->get();

        $assignmentIdsBySectionDay = $classSchedules
            ->groupBy(function (ClassSchedule $schedule): string {
                return $schedule->section_id.'|'.$schedule->day;
            })
            ->map(function (Collection $schedules): Collection {
                return $schedules
                    ->pluck('subject_assignment_id')
                    ->filter()
                    ->map(fn ($id): int => (int) $id)
                    ->unique()
                    ->values();
            });

        $enrollmentsBySection = $enrollments
            ->groupBy('section_id')
            ->map(function (Collection $sectionEnrollments): Collection {
                return $sectionEnrollments->values();
            });

        $schoolDays = $this->firstSchoolDays((string) $academicYear->start_date, self::SCHOOL_DAYS_TO_SEED);

        foreach ($schoolDays as $dayIndex => $schoolDay) {
            $dayName = $schoolDay->format('l');
            foreach ($enrollmentsBySection as $sectionId => $sectionEnrollments) {
                $assignmentKey = "{$sectionId}|{$dayName}";
                $assignmentIds = $assignmentIdsBySectionDay->get($assignmentKey, collect());

                foreach ($sectionEnrollments as $enrollmentIndex => $enrollment) {
                    if (! $enrollment instanceof Enrollment) {
                        continue;
                    }

                    foreach ($assignmentIds as $assignmentIndex => $assignmentId) {
                        $status = $this->attendanceStatus(
                            (int) $enrollment->id,
                            (int) $assignmentId,
                            $dayIndex + $enrollmentIndex + $assignmentIndex
                        );

                        Attendance::query()->updateOrCreate(
                            [
                                'enrollment_id' => $enrollment->id,
                                'subject_assignment_id' => $assignmentId,
                                'date' => $schoolDay->toDateString(),
                            ],
                            [
                                'status' => $status,
                                'remarks' => $status === Attendance::STATUS_PRESENT
                                    ? null
                                    : 'Seeded Q1 variance record.',
                            ]
                        );
                    }
                }
            }
        }
    }

    /**
     * @return Collection<int, CarbonImmutable>
     */
    private function firstSchoolDays(string $startDate, int $count): Collection
    {
        $schoolDays = collect();
        $cursor = CarbonImmutable::parse($startDate);

        while ($schoolDays->count() < $count) {
            if (! $cursor->isWeekend()) {
                $schoolDays->push($cursor);
            }

            $cursor = $cursor->addDay();
        }

        return $schoolDays;
    }

    private function attendanceStatus(int $enrollmentId, int $assignmentId, int $daySeed): string
    {
        $seed = $enrollmentId + ($assignmentId * 3) + ($daySeed * 5);

        if ($seed % 41 === 0) {
            return Attendance::STATUS_TARDY_CUTTING_CLASSES;
        }

        if ($seed % 17 === 0) {
            return Attendance::STATUS_TARDY_LATE_COMER;
        }

        if ($seed % 11 === 0) {
            return Attendance::STATUS_ABSENT;
        }

        return Attendance::STATUS_PRESENT;
    }

    private function formatScheduleTime(string $time): string
    {
        return CarbonImmutable::createFromFormat('H:i', str_pad($time, 5, '0', STR_PAD_LEFT))
            ->format('H:i:s');
    }

    private function sectionKey(int $gradeOrder, string $sectionName): string
    {
        return "{$gradeOrder}|{$sectionName}";
    }

    private function resolveAssessmentFeeTotal(int $gradeLevelId, int $academicYearId): float
    {
        return round((float) Fee::query()
            ->where('grade_level_id', $gradeLevelId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('type', ['tuition', 'miscellaneous'])
            ->sum('amount'), 2);
    }

    private function resolveDiscountAmount(int $studentId, int $academicYearId, float $assessmentFeeTotal): float
    {
        /** @var DiscountBucketCalculator $discountBucketCalculator */
        $discountBucketCalculator = app(DiscountBucketCalculator::class);
        $summary = $discountBucketCalculator->summarizeForStudent(
            $studentId,
            $academicYearId,
            $assessmentFeeTotal
        );

        return round((float) ($summary['total_discount_amount'] ?? 0), 2);
    }
}
