<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\AcademicYear;
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
use App\Models\Transaction;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\Support\SeedNameBank;
use Illuminate\Database\Seeder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class HighVolumeDemoSeeder extends Seeder
{
    private const TARGET_TOTAL_USERS = 5200;
    private const HEAVY_PROFILE_STUDENT_LIMIT = 600;

    private const SECTION_NAMES = [
        'St. Paul', 'St. Anthony', 'St. Francis', 'St. John', 'St. Anne',
        'St. Matthew', 'St. Luke', 'St. Mark', 'St. Peter', 'St. James',
        'St. Jude', 'St. Thomas', 'St. Andrew', 'St. Philip', 'St. Bartholomew',
        'St. Simon', 'St. Matthias', 'St. Stephen', 'St. Lawrence', 'St. Vincent',
        'St. Benedict', 'St. Dominic', 'St. Ignatius', 'St. Augustine', 'St. Jerome',
        'St. Ambrose', 'St. Gregory', 'St. Patrick', 'St. Nicholas', 'St. Sebastian',
        'St. George', 'St. Christopher',
    ];

    private const OR_NUMBER_PREFIX = 'OR-2026-';

    private const SUBJECT_LABELS = [
        'AP' => ['major' => 'Araling Panlipunan', 'degree' => 'Bachelor of Secondary Education major in Social Studies'],
        'ENG' => ['major' => 'English', 'degree' => 'Bachelor of Secondary Education major in English'],
        'ESP' => ['major' => 'Values Education', 'degree' => 'Bachelor of Secondary Education major in Values Education'],
        'FIL' => ['major' => 'Filipino', 'degree' => 'Bachelor of Secondary Education major in Filipino'],
        'MAPEH' => ['major' => 'MAPEH', 'degree' => 'Bachelor of Physical Education'],
        'MATH' => ['major' => 'Mathematics', 'degree' => 'Bachelor of Secondary Education major in Mathematics'],
        'SCI' => ['major' => 'Science', 'degree' => 'Bachelor of Secondary Education major in Science'],
        'TLE' => ['major' => 'Technology and Livelihood Education', 'degree' => 'Bachelor of Technical-Vocational Teacher Education'],
    ];

    private const SCHOOL_DAYS = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday'];

    private const SCHEDULE_SLOTS = [
        ['start_time' => '07:00:00', 'end_time' => '08:00:00'],
        ['start_time' => '08:00:00', 'end_time' => '09:00:00'],
        ['start_time' => '09:00:00', 'end_time' => '10:00:00'],
        ['start_time' => '10:00:00', 'end_time' => '11:00:00'],
        ['start_time' => '11:00:00', 'end_time' => '12:00:00'],
        ['start_time' => '13:00:00', 'end_time' => '14:00:00'],
        ['start_time' => '14:00:00', 'end_time' => '15:00:00'],
        ['start_time' => '15:00:00', 'end_time' => '16:00:00'],
    ];

    private const GRADED_ACTIVITY_BLUEPRINTS = [
        ['type' => 'WW', 'title' => 'Quiz 1', 'max_score' => 20],
        ['type' => 'WW', 'title' => 'Unit Test', 'max_score' => 50],
        ['type' => 'PT', 'title' => 'Quarter Project', 'max_score' => 100],
    ];

    public function run(): void
    {
        if (AcademicYear::query()->count() === 0 || GradeLevel::query()->count() === 0) {
            $this->call(ProductionBaselineSeeder::class);
        }

        $currentUsers = (int) User::query()->count();
        if ($currentUsers >= self::TARGET_TOTAL_USERS) {
            $this->command?->info("HighVolumeDemoSeeder skipped: users={$currentUsers} already at/above target.");

            return;
        }

        $targetAdditionalUsers = self::TARGET_TOTAL_USERS - $currentUsers;
        $targetStudentCount = (int) ceil($targetAdditionalUsers / 2);

        $academicYears = AcademicYear::query()
            ->orderBy('start_date')
            ->get();

        if ($academicYears->isEmpty()) {
            throw new \RuntimeException('Cannot run HighVolumeDemoSeeder: no academic years found.');
        }

        $gradeLevels = GradeLevel::query()
            ->whereIn('level_order', [7, 8, 9, 10])
            ->orderBy('level_order')
            ->get();

        if ($gradeLevels->isEmpty()) {
            throw new \RuntimeException('Cannot run HighVolumeDemoSeeder: grade levels 7-10 missing.');
        }

        $this->command?->info('Ensuring high-volume sections and schedules across all years...');
        $sectionsByYear = collect();
        foreach ($academicYears as $year) {
            $sectionsByYear->put($year->id, $this->ensureHighVolumeSectionsAndSchedules($year, $gradeLevels, $targetStudentCount));
        }

        $this->seedHighVolumeStudents($academicYears, $sectionsByYear, $targetStudentCount);
        $this->backfillMissingTransactionCoverage($academicYears);
        $this->backfillMissingHistoricalGrades();
        $this->normalizeSeededEmailFormats();

        $this->command?->info('HighVolumeDemoSeeder completed. Total users: '.User::query()->count());
    }

    private function ensureHighVolumeSectionsAndSchedules(
        AcademicYear $academicYear,
        Collection $gradeLevels,
        int $targetStudentCount
    ): Collection {
        // Keep section/schedule generation bounded for high-volume performance.
        // Enrollment volume can exceed section "capacity" in this synthetic load profile.
        $sectionsNeededTotal = max(16, (int) ceil($targetStudentCount / 120));
        $sectionsPerGrade = max(4, (int) ceil($sectionsNeededTotal / max(1, $gradeLevels->count())));

        $now = now();
        $hashedPassword = Hash::make('password');
        $currentQuarter = (int) ($academicYear->current_quarter ?: 1);

        foreach ($gradeLevels as $gradeLevel) {
            $subjects = Subject::query()->where('grade_level_id', $gradeLevel->id)->orderBy('subject_code')->get();
            if ($subjects->isEmpty()) {
                continue;
            }

            for ($sectionIndex = 1; $sectionIndex <= $sectionsPerGrade; $sectionIndex++) {
                $nameIndex = (($gradeLevel->level_order - 7) * $sectionsPerGrade + ($sectionIndex - 1)) % count(self::SECTION_NAMES);
                $section = Section::query()->create([
                    'academic_year_id' => $academicYear->id,
                    'grade_level_id' => $gradeLevel->id,
                    'name' => self::SECTION_NAMES[$nameIndex],
                    'adviser_id' => null,
                ]);

                $gradedActivityRows = [];
                $classScheduleRows = [];
                $adviserId = null;

                foreach ($subjects->values() as $subjectIndex => $subject) {
                    $subjectTag = $this->subjectTag($subject);
                    $teacher = $this->createQualifiedDemoTeacherForSectionSubject(
                        $section,
                        $subjectTag,
                        $hashedPassword
                    );
                    $adviserId ??= $teacher->id;

                    $teacherSubject = TeacherSubject::query()->create([
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subject->id,
                        'qualification_status' => 'fully_qualified',
                    ]);

                    $assignment = SubjectAssignment::query()->create([
                        'section_id' => $section->id,
                        'teacher_subject_id' => $teacherSubject->id,
                    ]);

                    // For historical years (status = completed), we seed ALL quarters
                    $quartersToSeed = ($academicYear->status === 'completed') ? 4 : $currentQuarter;

                    for ($q = 1; $q <= $quartersToSeed; $q++) {
                        foreach (self::GRADED_ACTIVITY_BLUEPRINTS as $blueprint) {
                            $gradedActivityRows[] = [
                                'subject_assignment_id' => $assignment->id,
                                'quarter' => (string) $q,
                                'title' => $blueprint['title'],
                                'type' => $blueprint['type'],
                                'max_score' => $blueprint['max_score'],
                                'created_at' => $now,
                                'updated_at' => $now,
                            ];
                        }
                    }

                    $slot = self::SCHEDULE_SLOTS[$subjectIndex % count(self::SCHEDULE_SLOTS)];
                    foreach (self::SCHOOL_DAYS as $day) {
                        $classScheduleRows[] = [
                            'section_id' => $section->id,
                            'subject_assignment_id' => $assignment->id,
                            'day' => $day,
                            'type' => 'academic',
                            'start_time' => $slot['start_time'],
                            'end_time' => $slot['end_time'],
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }
                }

                if ($gradedActivityRows !== []) {
                    DB::table('graded_activities')->insert($gradedActivityRows);
                }
                if ($classScheduleRows !== []) {
                    DB::table('class_schedules')->insert($classScheduleRows);
                }

                if ($adviserId) {
                    $section->forceFill(['adviser_id' => $adviserId])->save();
                }
            }
        }

        return Section::query()->where('academic_year_id', $academicYear->id)->get();
    }

    private function createQualifiedDemoTeacherForSectionSubject(Section $section, string $subjectTag, string $hashedPassword): User
    {
        $identity = SeedNameBank::teacherIdentity(((int) $section->id * 97) + strlen($subjectTag));
        $subjectSuffix = Str::lower($subjectTag ?: 'subject');
        $emailBase = Str::of($identity['first_name'])
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9 ]+/', '')
            ->squish()
            ->explode(' ')
            ->filter()
            ->first() ?: 'teacher';
        $lastName = Str::of($identity['last_name'])
            ->ascii()
            ->lower()
            ->replaceMatches('/[^a-z0-9]+/', '')
            ->toString() ?: 'demo';

        $teacher = User::factory()->create([
            'email' => $this->generateUniqueEmailByBase("{$emailBase}.{$lastName}.{$subjectSuffix}{$section->id}"),
            'personal_email' => $this->generatePersonalEmail($identity['first_name'], $identity['last_name']),
            'first_name' => $identity['first_name'],
            'last_name' => $identity['last_name'],
            'name' => "{$identity['first_name']} {$identity['last_name']}",
            'role' => UserRole::TEACHER,
            'is_active' => true,
            'password' => $hashedPassword,
        ]);

        $labels = self::SUBJECT_LABELS[$subjectTag] ?? [
            'major' => $subjectTag ?: 'General Education',
            'degree' => 'Bachelor of Secondary Education',
        ];

        TeacherProfile::query()->create([
            'user_id' => $teacher->id,
            'qualification_status' => 'fully_qualified',
            'is_let_passer' => true,
            'prc_license_no' => sprintf('%07d', mt_rand(1000000, 9999999)),
            'license_valid_until' => '2029-12-31',
            'degree' => $labels['degree'],
            'major' => $labels['major'],
            'professional_education_units' => 18,
            'grade_band_eligibility' => ['junior_high'],
            'subject_competency_tags' => [$subjectTag],
        ]);

        return $teacher;
    }

    private function seedHighVolumeStudents(Collection $academicYears, Collection $sectionsByYear, int $targetStudentCount): void
    {
        $cashier = User::where('role', UserRole::FINANCE)->first();
        if (! $cashier) {
            $identity = SeedNameBank::teacherIdentity(mt_rand(1, 1000000));
            $cashier = User::factory()->create([
                'role' => UserRole::FINANCE,
                'first_name' => $identity['first_name'],
                'last_name' => $identity['last_name'],
                'name' => "{$identity['first_name']} {$identity['last_name']}",
                'email' => $this->generateUniqueEmail($identity['first_name'], $identity['last_name'], UserRole::FINANCE),
                'personal_email' => $this->generatePersonalEmail($identity['first_name'], $identity['last_name']),
            ]);
        }

        $allFeesByYearAndGrade = Fee::query()
            ->get()
            ->groupBy('academic_year_id')
            ->map(fn ($yearFees) => $yearFees->groupBy('grade_level_id'));
        $subjectAssignmentIdsBySection = SubjectAssignment::query()
            ->get(['id', 'section_id'])
            ->groupBy('section_id')
            ->map(fn (Collection $rows): array => $rows->pluck('id')->all());

        $now = now();
        $hashedPassword = Hash::make('password');
        $activeYear = $academicYears->firstWhere('status', 'ongoing') ?? $academicYears->last();
        $gradeLevelIdByOrder = GradeLevel::query()->pluck('id', 'level_order')->toArray();

        for ($offset = 0; $offset < $targetStudentCount; $offset++) {
            $isHeavyProfile = $offset < self::HEAVY_PROFILE_STUDENT_LIMIT;
            $identity = SeedNameBank::studentIdentity(mt_rand(1, 1000000));
            do {
                $lrn = sprintf('26%010d', mt_rand(1, 1000000000));
            } while (Student::query()->where('lrn', $lrn)->exists());

            $lastNameToken = Str::of((string) $identity['student_last_name'])
                ->ascii()
                ->lower()
                ->replaceMatches('/[^a-z0-9]+/', '')
                ->toString();
            $studentEmail = $this->generateUniqueEmailByBase("{$lastNameToken}.{$lrn}");
            $sharedPersonalEmail = $this->generatePersonalEmail($identity['student_first_name'], $identity['student_last_name']);

            $user = User::factory()->create([
                'email' => $studentEmail,
                'personal_email' => $sharedPersonalEmail,
                'first_name' => $identity['student_first_name'],
                'last_name' => $identity['student_last_name'],
                'name' => "{$identity['student_first_name']} {$identity['student_last_name']}",
                'role' => UserRole::STUDENT,
                'password' => $hashedPassword,
            ]);

            $student = Student::query()->create([
                'user_id' => $user->id,
                'lrn' => $lrn,
                'first_name' => $identity['student_first_name'],
                'middle_name' => $identity['student_middle_name'],
                'last_name' => $identity['student_last_name'],
                'gender' => mt_rand(0, 1) === 0 ? 'Male' : 'Female',
                'birthdate' => '2011-06-15',
                'guardian_name' => "{$identity['guardian_first_name']} {$identity['student_last_name']}",
            ]);

            $normalizedParentSurname = preg_replace('/[^a-z0-9]+/', '', strtolower($identity['student_last_name']));
            $normalizedParentSurname = $normalizedParentSurname !== '' ? $normalizedParentSurname : 'student';
            $parentEmail = $this->generateUniqueEmailByBase("parent.{$normalizedParentSurname}");

            $parent = User::factory()->create([
                'email' => $parentEmail,
                'personal_email' => $sharedPersonalEmail,
                'first_name' => $identity['guardian_first_name'],
                'last_name' => $identity['student_last_name'],
                'name' => "{$identity['guardian_first_name']} {$identity['student_last_name']}",
                'role' => UserRole::PARENT,
                'password' => $hashedPassword,
            ]);
            $student->parents()->attach($parent->id);

            // Distribution: 25% for each Grade 7, 8, 9, 10
            $target2025Grade = 7 + ($offset % 4);
            $carryOverBalance = 0.0;

            foreach ($academicYears as $yearIndex => $year) {
                if (! $isHeavyProfile && (int) $year->id !== (int) $activeYear->id) {
                    continue;
                }

                $activeIdx = $academicYears->search(fn ($y) => $y->id === $activeYear->id);
                $yearDiff = (int) $activeIdx - $yearIndex;
                $currentYearGrade = $target2025Grade - $yearDiff;

                if ($currentYearGrade < 7) {
                    continue;
                }
                if ($year->id !== $activeYear->id && mt_rand(1, 100) <= 5) {
                    continue;
                }

                $yearSections = $sectionsByYear->get($year->id);
                if (! $yearSections) {
                    continue;
                }

                $targetGradeLevelId = $gradeLevelIdByOrder[$currentYearGrade] ?? null;
                if (! $targetGradeLevelId) {
                    continue;
                }

                $possibleSections = $yearSections->filter(fn ($s) => (int) $s->grade_level_id === (int) $targetGradeLevelId);
                if ($possibleSections->isEmpty()) {
                    continue;
                }

                $section = $possibleSections->random();

                $enrollment = Enrollment::query()->create([
                    'student_id' => $student->id,
                    'academic_year_id' => $year->id,
                    'grade_level_id' => $section->grade_level_id,
                    'section_id' => $section->id,
                    'payment_term' => 'monthly',
                    'status' => 'enrolled',
                    'report_card_submitted' => true,
                    'birth_certificate_submitted' => true,
                    'created_at' => Carbon::parse($year->start_date)->addDays(mt_rand(1, 5)),
                ]);

                $feesForGrade = $allFeesByYearAndGrade->get($year->id)?->get($section->grade_level_id) ?? collect();
                $assessmentTotal = (float) $feesForGrade->sum('amount');
                if ($assessmentTotal <= 0) {
                    $assessmentTotal = 35000.00;
                }

                // Opening Balances
                if ($carryOverBalance > 0) {
                    $student->ledgerEntries()->create([
                        'academic_year_id' => $year->id,
                        'date' => $year->start_date,
                        'description' => 'Balance Forward from Previous Year',
                        'debit' => $carryOverBalance,
                        'running_balance' => $carryOverBalance,
                    ]);
                }

                $student->ledgerEntries()->create([
                    'academic_year_id' => $year->id,
                    'date' => $year->start_date,
                    'description' => 'Annual Assessment (Tuition & Misc Fees)',
                    'debit' => $assessmentTotal,
                    'running_balance' => $assessmentTotal + $carryOverBalance,
                ]);

                // Heavy profile: richer historical finance trail.
                // Light profile: minimal connected finance records for current-year load users.
                $percentToPay = $isHeavyProfile
                    ? (($year->status === 'completed') ? 1.0 : (mt_rand(60, 95) / 100))
                    : (mt_rand(20, 50) / 100);
                $totalToPayThisYear = round(($assessmentTotal + $carryOverBalance) * $percentToPay, 2);
                $paidSoFar = 0.0;

                if ($totalToPayThisYear > 0) {
                    $paymentEvents = $this->buildPaymentEvents($year, $totalToPayThisYear, $isHeavyProfile);
                    foreach ($paymentEvents as $event) {
                        $this->createPaymentTransaction(
                            $student,
                            $cashier,
                            $year,
                            $event['paid_at'],
                            $event['label'],
                            (float) $event['amount'],
                            $assessmentTotal + $carryOverBalance,
                            $paidSoFar,
                            $feesForGrade
                        );
                        $paidSoFar += (float) $event['amount'];
                    }
                }

                $carryOverBalance = round(max(($assessmentTotal + $carryOverBalance) - $paidSoFar, 0), 2);

                // Seed historical grades and permanent record for heavy profile only.
                if ($isHeavyProfile && $year->status === 'completed') {
                    $subjectGrades = [];
                    $subjectAssignmentIds = $subjectAssignmentIdsBySection->get($section->id, []);
                    $finalGradeRows = [];

                    foreach ($subjectAssignmentIds as $assignmentId) {
                        $q1 = rand(85, 98);
                        $q2 = rand(85, 98);
                        $q3 = rand(85, 98);
                        $q4 = rand(85, 98);
                        $final = round(($q1 + $q2 + $q3 + $q4) / 4);

                        $finalGradeRows[] = [
                            'enrollment_id' => $enrollment->id,
                            'subject_assignment_id' => $assignmentId,
                            'quarter' => '1',
                            'grade' => $q1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                        $finalGradeRows[] = [
                            'enrollment_id' => $enrollment->id,
                            'subject_assignment_id' => $assignmentId,
                            'quarter' => 'final',
                            'grade' => $final,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];

                        $subjectGrades[] = $final;
                    }

                    if ($finalGradeRows !== []) {
                        DB::table('final_grades')->insert($finalGradeRows);
                    }

                    $generalAverage = count($subjectGrades) > 0 ? round(array_sum($subjectGrades) / count($subjectGrades), 2) : 0;

                    PermanentRecord::query()->create([
                        'student_id' => $student->id,
                        'academic_year_id' => $year->id,
                        'grade_level_id' => $section->grade_level_id,
                        'school_name' => 'Marriott School',
                        'general_average' => $generalAverage,
                        'status' => 'promoted',
                    ]);
                }
            }
        }
    }

    private function createPaymentTransaction($student, $cashier, $year, Carbon $paidAt, $desc, $amount, $totalCharges, $previousPayments, $feesForGrade): void
    {
        $modes = ['Cash', 'GCash', 'Bank Transfer'];
        $mode = $modes[mt_rand(0, 2)];

        $transaction = Transaction::query()->create([
            'student_id' => $student->id,
            'cashier_id' => $cashier->id,
            'or_number' => self::OR_NUMBER_PREFIX.mt_rand(100000, 999999).'-'.$student->id.'-'.mt_rand(1, 100),
            'total_amount' => $amount,
            'payment_mode' => $mode,
            'created_at' => $paidAt,
            'updated_at' => $paidAt,
        ]);

        // Prioritize Tuition Fee for transaction item categorization
        $tuitionFee = $feesForGrade->firstWhere('type', 'tuition');

        $transaction->items()->create([
            'description' => $this->randomizedTransactionItemDescription($desc),
            'amount' => $amount,
            'fee_id' => $tuitionFee?->id,
        ]);

        $student->ledgerEntries()->create([
            'academic_year_id' => $year->id,
            'date' => $paidAt->toDateString(),
            'description' => "Payment received: {$desc} (OR: {$transaction->or_number})",
            'credit' => $amount,
            'reference_id' => $transaction->id,
            'running_balance' => $totalCharges - ($previousPayments + $amount),
            'created_at' => $paidAt,
            'updated_at' => $paidAt,
        ]);
    }

    /**
     * @return array<int, array{label:string, amount:float, paid_at:Carbon}>
     */
    private function buildPaymentEvents(AcademicYear $year, float $totalToPay, bool $isHeavyProfile): array
    {
        if ($totalToPay <= 0) {
            return [];
        }

        $start = Carbon::parse((string) $year->start_date);
        $end = Carbon::parse((string) $year->end_date)->endOfDay();
        if ($year->status !== 'completed' && now()->lt($end)) {
            $end = now()->copy();
        }

        $events = [];
        if ($isHeavyProfile) {
            $down = round($totalToPay * 0.35, 2);
            $mid = round($totalToPay * 0.35, 2);
            $last = round($totalToPay - $down - $mid, 2);
            $events = [
                ['label' => 'Enrollment Downpayment', 'due' => $start->copy()->addDays(3), 'amount' => $down, 'late' => 4],
                ['label' => '1st Monthly Due', 'due' => $start->copy()->addDays(35), 'amount' => $mid, 'late' => 8],
                ['label' => '2nd Monthly Due', 'due' => $start->copy()->addDays(65), 'amount' => $last, 'late' => 10],
            ];
        } else {
            $events = [
                ['label' => 'Enrollment Downpayment', 'due' => $start->copy()->addDays(5), 'amount' => $totalToPay, 'late' => 6],
            ];
        }

        $normalized = [];
        foreach ($events as $event) {
            $due = $event['due'] instanceof Carbon ? $event['due'] : $start->copy();
            $offset = mt_rand(-1, (int) $event['late']); // slight early or slight overdue
            $paidAt = $due->copy()->addDays($offset);
            if ($paidAt->lt($start)) {
                $paidAt = $start->copy();
            }
            if ($paidAt->gt($end)) {
                $paidAt = $end->copy();
            }
            $paidAt->setTime(mt_rand(8, 16), mt_rand(0, 59), mt_rand(0, 59));

            $normalized[] = [
                'label' => (string) $event['label'],
                'amount' => round((float) $event['amount'], 2),
                'paid_at' => $paidAt,
            ];
        }

        return array_values(array_filter($normalized, fn (array $e): bool => $e['amount'] > 0));
    }

    private function randomizedTransactionItemDescription(string $label): string
    {
        $variants = [
            "{$label} - Tuition",
            "{$label} - Assessment",
            "{$label} - Installment",
            "Payment applied: {$label}",
            "{$label} (Seeded Finance Trail)",
        ];

        return $variants[array_rand($variants)];
    }

    private function subjectTag(Subject $subject): string
    {
        return strtoupper((string) preg_replace('/\d+$/', '', (string) $subject->subject_code));
    }

    private function generateUniqueEmail(string $firstName, string $lastName, UserRole $role): string
    {
        $first = strtolower(explode(' ', $firstName)[0]);
        $last = preg_replace('/[^a-z0-9]+/', '', strtolower($lastName));

        if ($role === UserRole::STUDENT) {
            $base = "{$last}.{$first}";
        } elseif ($role === UserRole::PARENT) {
            $base = "parent.{$last}";
        } else {
            // Staff and Admins follow first.last format
            $base = "{$first}.{$last}";
        }

        $email = "{$base}@marriott.edu";

        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $email = "{$base}{$counter}@marriott.edu";
            $counter++;
        }

        return $email;
    }

    private function generateUniqueEmailByBase(string $base): string
    {
        $email = "{$base}@marriott.edu";

        $counter = 1;
        while (User::where('email', $email)->exists()) {
            $email = "{$base}.{$counter}@marriott.edu";
            $counter++;
        }

        return $email;
    }

    private function generatePersonalEmail(string $firstName, string $lastName): string
    {
        $first = strtolower(explode(' ', $firstName)[0]);
        $last = preg_replace('/[^a-z0-9]+/', '', strtolower($lastName));

        return "{$first}.{$last}@test.com";
    }

    private function backfillMissingTransactionCoverage(Collection $academicYears): void
    {
        $cashier = User::query()->where('role', UserRole::FINANCE)->orderBy('id')->first();
        if (! $cashier) {
            return;
        }

        $feesByYearAndGrade = Fee::query()
            ->get()
            ->groupBy('academic_year_id')
            ->map(fn ($yearFees) => $yearFees->groupBy('grade_level_id'));

        $studentIdsWithTransactions = Transaction::query()
            ->distinct()
            ->pluck('student_id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $studentsWithoutTransactions = Student::query()
            ->whereNotIn('id', $studentIdsWithTransactions)
            ->get(['id']);

        foreach ($studentsWithoutTransactions as $student) {
            $enrollment = Enrollment::query()
                ->where('student_id', $student->id)
                ->whereIn('academic_year_id', $academicYears->pluck('id')->all())
                ->orderByDesc('academic_year_id')
                ->first();

            if (! $enrollment) {
                continue;
            }

            $academicYear = $academicYears->firstWhere('id', $enrollment->academic_year_id);
            if (! $academicYear) {
                continue;
            }

            $feesForGrade = $feesByYearAndGrade->get($enrollment->academic_year_id)?->get($enrollment->grade_level_id) ?? collect();
            $assessmentTotal = (float) $feesForGrade->sum('amount');
            if ($assessmentTotal <= 0) {
                $assessmentTotal = 35000.00;
            }

            $paymentDate = Carbon::parse((string) $academicYear->start_date)->addDays(7)->setTime(10, 30, 0);
            $amount = round($assessmentTotal * 0.30, 2);

            $this->createPaymentTransaction(
                $student,
                $cashier,
                $academicYear,
                $paymentDate,
                'Backfilled Seed Payment',
                $amount,
                $assessmentTotal,
                0.0,
                $feesForGrade
            );
        }
    }

    private function backfillMissingHistoricalGrades(): void
    {
        $completedYearIds = AcademicYear::query()
            ->where('status', 'completed')
            ->pluck('id')
            ->all();

        if ($completedYearIds === []) {
            return;
        }

        $enrollmentsMissingGrades = Enrollment::query()
            ->whereIn('academic_year_id', $completedYearIds)
            ->whereNotExists(function ($query): void {
                $query->select(DB::raw(1))
                    ->from('final_grades')
                    ->whereColumn('final_grades.enrollment_id', 'enrollments.id');
            })
            ->get(['id', 'student_id', 'section_id', 'academic_year_id', 'grade_level_id']);

        if ($enrollmentsMissingGrades->isEmpty()) {
            return;
        }

        $subjectAssignmentIdsBySection = SubjectAssignment::query()
            ->get(['id', 'section_id'])
            ->groupBy('section_id')
            ->map(fn (Collection $rows): array => $rows->pluck('id')->all());

        $now = now();
        $finalGradeRows = [];
        $permanentRecordRows = [];

        foreach ($enrollmentsMissingGrades as $enrollment) {
            $assignmentIds = $subjectAssignmentIdsBySection->get($enrollment->section_id, []);
            if ($assignmentIds === []) {
                continue;
            }

            $subjectGrades = [];
            foreach ($assignmentIds as $assignmentId) {
                $q1 = rand(84, 96);
                $q2 = rand(84, 96);
                $q3 = rand(84, 96);
                $q4 = rand(84, 96);
                $final = (int) round(($q1 + $q2 + $q3 + $q4) / 4);
                $subjectGrades[] = $final;

                $finalGradeRows[] = [
                    'enrollment_id' => $enrollment->id,
                    'subject_assignment_id' => $assignmentId,
                    'quarter' => '1',
                    'grade' => $q1,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
                $finalGradeRows[] = [
                    'enrollment_id' => $enrollment->id,
                    'subject_assignment_id' => $assignmentId,
                    'quarter' => 'final',
                    'grade' => $final,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            $alreadyHasRecord = PermanentRecord::query()
                ->where('student_id', $enrollment->student_id)
                ->where('academic_year_id', $enrollment->academic_year_id)
                ->exists();

            if (! $alreadyHasRecord) {
                $permanentRecordRows[] = [
                    'student_id' => $enrollment->student_id,
                    'academic_year_id' => $enrollment->academic_year_id,
                    'grade_level_id' => $enrollment->grade_level_id,
                    'school_name' => 'Marriott School',
                    'general_average' => round(array_sum($subjectGrades) / max(1, count($subjectGrades)), 2),
                    'status' => 'promoted',
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }
        }

        if ($finalGradeRows !== []) {
            DB::table('final_grades')->insert($finalGradeRows);
        }

        if ($permanentRecordRows !== []) {
            DB::table('permanent_records')->insert($permanentRecordRows);
        }
    }

    private function normalizeSeededEmailFormats(): void
    {
        Student::query()
            ->with('user')
            ->orderBy('id')
            ->get()
            ->each(function (Student $student): void {
                $normalizedLrn = preg_replace('/\D/', '', (string) $student->lrn) ?? '';
                if ($normalizedLrn === '') {
                    return;
                }

                $lastNameToken = Str::of((string) $student->last_name)
                    ->ascii()
                    ->lower()
                    ->replaceMatches('/[^a-z0-9]+/', '')
                    ->toString();
                $studentEmail = "{$lastNameToken}.{$normalizedLrn}@marriott.edu";

                if ($student->user) {
                    $this->releaseConflictingEmail($studentEmail, (int) $student->user->id);
                    $student->user->update(['email' => $studentEmail]);
                }

                $parentIds = DB::table('parent_student')
                    ->where('student_id', $student->id)
                    ->pluck('parent_id')
                    ->values();

                foreach ($parentIds as $index => $parentId) {
                    $parentEmail = $index === 0
                        ? "parent.{$normalizedLrn}@marriott.edu"
                        : "parent.{$normalizedLrn}.{$parentId}@marriott.edu";

                    $this->releaseConflictingEmail($parentEmail, (int) $parentId);
                    User::query()->whereKey((int) $parentId)->update(['email' => $parentEmail]);
                }
            });
    }

    private function releaseConflictingEmail(string $email, int $intendedUserId): void
    {
        User::query()
            ->where('email', $email)
            ->whereKeyNot($intendedUserId)
            ->get(['id', 'email'])
            ->each(function (User $user): void {
                User::query()->whereKey($user->id)->update([
                    'email' => "archived.seed.{$user->id}.{$user->email}",
                ]);
            });
    }
}
