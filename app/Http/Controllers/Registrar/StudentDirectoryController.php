<?php

namespace App\Http\Controllers\Registrar;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\ExportSf1ReferenceRequest;
use App\Http\Requests\Registrar\UpdateStudentDirectoryRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Auth\EnrollmentAccountClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class StudentDirectoryController extends Controller
{
    public function __construct(
        private EnrollmentAccountClaimService $enrollmentAccountClaimService,
    ) {}

    public function index(Request $request): Response
    {
        $ongoingAcademicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first();
        $search = trim((string) $request->input('search', ''));
        $normalizedSearch = mb_strtolower($search);
        $selectedStatus = (string) $request->input('status', 'all');
        $allowedStatuses = [
            'all',
            'enrolled',
            'enrolled_with_missing_requirements',
            'not_enrolled',
            'not_currently_enrolled',
            'transferred_out',
            'dropped',
        ];
        if (! in_array($selectedStatus, $allowedStatuses, true)) {
            $selectedStatus = 'all';
        }
        $selectedSort = (string) $request->input('sort', 'a_z');
        if (! in_array($selectedSort, ['a_z', 'z_a', 'newest', 'oldest'], true)) {
            $selectedSort = 'a_z';
        }
        $selectedSectionIds = collect($request->input('section_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter(fn ($id) => $id > 0)
            ->values();

        $studentBaseQuery = Student::query()
            ->when($selectedSectionIds->isNotEmpty(), function ($query) use ($ongoingAcademicYear, $selectedSectionIds) {
                $query->whereHas('enrollments', function ($enrollmentQuery) use ($ongoingAcademicYear, $selectedSectionIds): void {
                    $enrollmentQuery
                        ->when($ongoingAcademicYear, fn ($q) => $q->where('academic_year_id', $ongoingAcademicYear->id))
                        ->whereIn('section_id', $selectedSectionIds->all());
                });
            })
            ->when($ongoingAcademicYear, function ($query) use ($ongoingAcademicYear) {
                $query->whereDoesntHave('enrollments', function ($enrollmentQuery) use ($ongoingAcademicYear): void {
                    $enrollmentQuery
                        ->where('academic_year_id', $ongoingAcademicYear->id)
                        ->where('status', 'for_cashier_payment');
                });
            })
            ->when($normalizedSearch !== '', function ($query) use ($normalizedSearch) {
                $searchPattern = "%{$normalizedSearch}%";

                $query->where(function ($studentQuery) use ($searchPattern) {
                    $studentQuery
                        ->whereRaw('LOWER(lrn) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(first_name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchPattern]);
                });
            })
            ->when($ongoingAcademicYear && $selectedStatus !== 'all', function ($query) use ($ongoingAcademicYear, $selectedStatus) {
                if ($selectedStatus === 'enrolled') {
                    $query->whereHas('enrollments', function ($enrollmentQuery) use ($ongoingAcademicYear): void {
                        $enrollmentQuery
                            ->where('academic_year_id', $ongoingAcademicYear->id)
                            ->where('status', 'enrolled')
                            ->where('report_card_submitted', true)
                            ->where('birth_certificate_submitted', true);
                    });

                    return;
                }

                if ($selectedStatus === 'enrolled_with_missing_requirements') {
                    $query->whereHas('enrollments', function ($enrollmentQuery) use ($ongoingAcademicYear): void {
                        $enrollmentQuery
                            ->where('academic_year_id', $ongoingAcademicYear->id)
                            ->where('status', 'enrolled')
                            ->where(function ($requirementsQuery): void {
                                $requirementsQuery
                                    ->where('report_card_submitted', false)
                                    ->orWhere('birth_certificate_submitted', false);
                            });
                    });

                    return;
                }

                if ($selectedStatus === 'not_enrolled') {
                    $query->whereHas('enrollments', function ($enrollmentQuery) use ($ongoingAcademicYear): void {
                        $enrollmentQuery
                            ->where('academic_year_id', $ongoingAcademicYear->id)
                            ->where('status', 'for_cashier_payment');
                    });

                    return;
                }

                if ($selectedStatus === 'transferred_out' || $selectedStatus === 'dropped') {
                    $query->whereHas('enrollments', function ($enrollmentQuery) use ($ongoingAcademicYear, $selectedStatus): void {
                        $enrollmentQuery
                            ->where('academic_year_id', $ongoingAcademicYear->id)
                            ->where('status', $selectedStatus);
                    });

                    return;
                }

                if ($selectedStatus === 'not_currently_enrolled') {
                    $query->whereDoesntHave('enrollments', function ($enrollmentQuery) use ($ongoingAcademicYear): void {
                        $enrollmentQuery
                            ->where('academic_year_id', $ongoingAcademicYear->id)
                            ->whereIn('status', ['for_cashier_payment', 'enrolled', 'transferred_out', 'dropped']);
                    });
                }
            });

        $students = (clone $studentBaseQuery)
            ->with([
                'user:id,email,role,must_change_password,password_updated_at',
                'parents:id,email,role,must_change_password,password_updated_at',
                'enrollments' => function ($query) {
                    $query
                        ->with(['academicYear:id,name,start_date,status', 'gradeLevel:id,name', 'section:id,name'])
                        ->orderByDesc(
                            AcademicYear::query()
                                ->select('start_date')
                                ->whereColumn('academic_years.id', 'enrollments.academic_year_id')
                                ->limit(1)
                        )
                        ->latest('id');
                },
            ])
            ->when(
                $selectedSort === 'newest',
                fn ($query) => $query->orderByDesc('created_at')->orderByDesc('id'),
                fn ($query) => $query->when(
                    $selectedSort === 'oldest',
                    fn ($oldestQuery) => $oldestQuery->orderBy('created_at')->orderBy('id'),
                    fn ($nameQuery) => $nameQuery->when(
                        $selectedSort === 'z_a',
                        fn ($zQuery) => $zQuery->orderByDesc('last_name')->orderByDesc('first_name'),
                        fn ($aQuery) => $aQuery->orderBy('last_name')->orderBy('first_name')
                    )
                )
            )
            ->paginate(15)
            ->withQueryString()
            ->through(function (Student $student) use ($ongoingAcademicYear) {
                $enrollment = $ongoingAcademicYear
                    ? $student->enrollments->firstWhere('academic_year_id', $ongoingAcademicYear->id)
                    : $student->enrollments->first();

                $gradeSection = 'Unassigned';
                if ($enrollment?->gradeLevel?->name && $enrollment?->section?->name) {
                    $gradeSection = "{$enrollment->gradeLevel->name} - {$enrollment->section->name}";
                } elseif ($enrollment?->gradeLevel?->name) {
                    $gradeSection = $enrollment->gradeLevel->name;
                }

                return [
                    'id' => $student->id,
                    'enrollment_id' => $enrollment?->id,
                    'lrn' => $student->lrn,
                    'first_name' => $student->first_name,
                    'middle_name' => $student->middle_name,
                    'last_name' => $student->last_name,
                    'gender' => $student->gender,
                    'birthdate' => $student->birthdate?->toDateString(),
                    'guardian_name' => $student->guardian_name,
                    'guardian_contact_number' => $student->contact_number,
                    'email' => $enrollment?->email,
                    'student_account_email' => $student->user?->email,
                    'parent_account_email' => $student->parents->first()?->email,
                    'report_card_submitted' => (bool) ($enrollment?->report_card_submitted ?? false),
                    'birth_certificate_submitted' => (bool) ($enrollment?->birth_certificate_submitted ?? false),
                    'student_name' => trim("{$student->first_name} {$student->last_name}"),
                    'grade_section' => $gradeSection,
                    'enrollment_status' => $enrollment?->status,
                    'student_account_claimed' => $this->isAccountClaimed($student->user),
                    'parent_account_claimed' => $this->isAccountClaimed($student->parents->first()),
                    'status' => $this->resolveDirectoryStatus(
                        $enrollment?->status,
                        (bool) ($enrollment?->report_card_submitted ?? false),
                        (bool) ($enrollment?->birth_certificate_submitted ?? false),
                    ),
                    'enrollment_history' => $student->enrollments
                        ->map(function (Enrollment $historyEnrollment): array {
                            $historyGradeSection = 'Unassigned';
                            if ($historyEnrollment->gradeLevel?->name && $historyEnrollment->section?->name) {
                                $historyGradeSection = "{$historyEnrollment->gradeLevel->name} - {$historyEnrollment->section->name}";
                            } elseif ($historyEnrollment->gradeLevel?->name) {
                                $historyGradeSection = $historyEnrollment->gradeLevel->name;
                            }

                            return [
                                'id' => (int) $historyEnrollment->id,
                                'school_year' => $historyEnrollment->academicYear?->name ?? 'N/A',
                                'grade_level' => $historyEnrollment->gradeLevel?->name ?? 'Unassigned',
                                'section' => $historyEnrollment->section?->name ?? 'Unassigned',
                                'grade_section' => $historyGradeSection,
                                'status' => (string) $historyEnrollment->status,
                                'status_label' => $this->formatEnrollmentStatus((string) $historyEnrollment->status),
                            ];
                        })
                        ->values()
                        ->all(),
                ];
            });

        $sectionOptions = collect();
        if ($ongoingAcademicYear instanceof AcademicYear) {
            $sectionOptions = Section::query()
                ->with('gradeLevel:id,name')
                ->where('academic_year_id', $ongoingAcademicYear->id)
                ->orderBy('grade_level_id')
                ->orderBy('name')
                ->get(['id', 'grade_level_id', 'name'])
                ->map(function (Section $section): array {
                    $gradeLevelName = trim((string) ($section->gradeLevel?->name ?? ''));
                    $sectionName = trim((string) $section->name);

                    return [
                        'id' => (int) $section->id,
                        'label' => $gradeLevelName !== ''
                            ? "{$gradeLevelName} - {$sectionName}"
                            : $sectionName,
                    ];
                })
                ->values();
        }

        return Inertia::render('registrar/student-directory/index', [
            'students' => $students,
            'section_options' => $sectionOptions->all(),
            'ongoing_academic_year_id' => $ongoingAcademicYear?->id,
            'filters' => [
                'search' => $search,
                'status' => $selectedStatus,
                'sort' => $selectedSort,
                'section_ids' => $selectedSectionIds->all(),
            ],
        ]);
    }

    public function update(UpdateStudentDirectoryRequest $request, Student $student): RedirectResponse
    {
        $validated = $request->validated();

        $normalizedGuardianContactNumber = $this->normalizeGuardianContactNumber(
            (string) $validated['guardian_contact_number']
        );

        $activeAcademicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first() ?? AcademicYear::query()
            ->latest('start_date')
            ->first();

        $syncableEnrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->whereIn('status', ['for_cashier_payment', 'enrolled'])
            ->when(
                $activeAcademicYear,
                fn ($query) => $query->where('academic_year_id', $activeAcademicYear->id)
            )
            ->latest('id')
            ->first();

        $normalizedIncomingEmail = trim((string) ($validated['email'] ?? ''));
        $normalizedExistingEmail = trim((string) ($syncableEnrollment?->email ?? ''));
        $emailChanged = Str::lower($normalizedIncomingEmail) !== Str::lower($normalizedExistingEmail);
        $emailPresent = $normalizedIncomingEmail !== '';
        $accountsUnclaimed = $this->areStudentAndParentAccountsUnclaimed($student);
        $requiresClaimEmailConfirmation = $syncableEnrollment instanceof Enrollment
            && (string) $syncableEnrollment->status === 'enrolled'
            && $emailChanged
            && $emailPresent
            && $accountsUnclaimed;
        $claimEmailConfirmed = (bool) ($validated['send_claim_email_confirmation'] ?? false);

        if ($requiresClaimEmailConfirmation && ! $claimEmailConfirmed) {
            return back()
                ->with('claim_email_confirmation_required', true)
                ->with('claim_email_confirmation_email', $normalizedIncomingEmail)
                ->with(
                    'claim_email_confirmation_message',
                    "Send account-claim email to {$normalizedIncomingEmail} now?"
                );
        }

        DB::transaction(function () use (
            $student,
            $validated,
            $normalizedGuardianContactNumber,
            $syncableEnrollment,
        ): void {
            $student->update([
                'first_name' => $validated['first_name'],
                'middle_name' => $validated['middle_name'] ?: null,
                'last_name' => $validated['last_name'],
                'gender' => $validated['gender'],
                'birthdate' => $validated['birthdate'],
                'guardian_name' => $validated['guardian_name'],
                'contact_number' => $normalizedGuardianContactNumber,
            ]);

            if ($syncableEnrollment) {
                $syncableEnrollment->update([
                    'email' => $validated['email'] ?: null,
                    'report_card_submitted' => (bool) ($validated['report_card_submitted'] ?? false),
                    'birth_certificate_submitted' => (bool) ($validated['birth_certificate_submitted'] ?? false),
                ]);
            }

            $parentContactEmail = Str::lower(trim((string) ($validated['email'] ?? ''))) ?: null;
            $student->loadMissing('parents');
            $student->parents
                ->filter(function (User $parent): bool {
                    $role = $parent->role instanceof UserRole
                        ? $parent->role->value
                        : (string) $parent->role;

                    return $role === UserRole::PARENT->value;
                })
                ->each(function (User $parent) use ($parentContactEmail): void {
                    $parent->forceFill(['personal_email' => $parentContactEmail])->save();
                });
        });

        if ($requiresClaimEmailConfirmation && $claimEmailConfirmed && $syncableEnrollment instanceof Enrollment) {
            $syncableEnrollment->refresh();
            $this->enrollmentAccountClaimService->issueForEnrollment($syncableEnrollment);

            return back()->with('success', 'Student details updated. Account-claim email sent.');
        }

        return back()->with('success', 'Student details updated.');
    }

    public function resendClaimEmail(Student $student): RedirectResponse
    {
        $activeAcademicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first() ?? AcademicYear::query()
            ->latest('start_date')
            ->first();

        $enrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->where('status', 'enrolled')
            ->when(
                $activeAcademicYear,
                fn ($query) => $query->where('academic_year_id', $activeAcademicYear->id)
            )
            ->latest('id')
            ->first();

        if (! $enrollment instanceof Enrollment) {
            return back()->with('error', 'Account-claim email can only be resent for currently enrolled students.');
        }

        $student->loadMissing([
            'user:id,role,email,personal_email,must_change_password,password_updated_at',
            'parents:id,role,email,personal_email,must_change_password,password_updated_at',
        ]);

        $studentUser = $student->user instanceof User ? $student->user : null;
        $parentUser = $student->parents->first(function (User $parent): bool {
            $role = $parent->role instanceof UserRole
                ? $parent->role->value
                : (string) $parent->role;

            return $role === UserRole::PARENT->value;
        }) ?? $student->parents->first();

        $issuedCount = 0;
        if ($studentUser instanceof User && ! $this->isAccountClaimed($studentUser)) {
            $issuedCount += $this->enrollmentAccountClaimService->issueForEnrollmentUser($enrollment, $studentUser) ? 1 : 0;
        }

        if ($parentUser instanceof User && ! $this->isAccountClaimed($parentUser)) {
            $issuedCount += $this->enrollmentAccountClaimService->issueForEnrollmentUser($enrollment, $parentUser) ? 1 : 0;
        }

        if ($issuedCount === 0) {
            if ($this->isAccountClaimed($studentUser) && $this->isAccountClaimed($parentUser instanceof User ? $parentUser : null)) {
                return back()->with('error', 'Student and parent accounts are already claimed.');
            }

            return back()->with('error', 'Unable to resend account-claim email. Check the student personal email or guardian contact email.');
        }

        return back()->with(
            'success',
            $issuedCount === 1
                ? 'Account-claim email resent.'
                : 'Account-claim emails resent.'
        );
    }

    private function areStudentAndParentAccountsUnclaimed(Student $student): bool
    {
        $student->loadMissing([
            'user:id,role,must_change_password,password_updated_at',
            'parents:id,role,must_change_password,password_updated_at',
        ]);

        $studentUnclaimed = ! $this->isAccountClaimed($student->user);

        $parent = $student->parents->first(function (User $parent): bool {
            $role = $parent->role instanceof UserRole
                ? $parent->role->value
                : (string) $parent->role;

            return $role === UserRole::PARENT->value;
        }) ?? $student->parents->first();

        $parentUnclaimed = ! $this->isAccountClaimed($parent instanceof User ? $parent : null);

        return $studentUnclaimed && $parentUnclaimed;
    }

    private function isAccountClaimed(?User $user): bool
    {
        if (! $user instanceof User) {
            return false;
        }

        return ! (bool) $user->must_change_password;
    }

    public function uploadSf1(Request $request): RedirectResponse
    {
        return back()->with(
            'error',
            'Inbound SF1 sync is disabled. Use Student Directory > Export SF1 Reference for LIS enrollment.'
        );
    }

    public function exportSf1Reference(ExportSf1ReferenceRequest $request): BinaryFileResponse|RedirectResponse|HttpResponse
    {
        $validated = $request->validated();

        $selectedAcademicYearId = (int) ($validated['academic_year_id'] ?? 0);
        $selectedSectionIds = collect($validated['section_ids'] ?? [])
            ->map(fn (mixed $id): int => (int) $id)
            ->filter(fn (int $id): bool => $id > 0)
            ->unique()
            ->values();

        $academicYear = $selectedAcademicYearId > 0
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : AcademicYear::query()->where('status', 'ongoing')->first();

        if (! $academicYear) {
            return back()->with('error', 'No academic year found for SF1 reference export.');
        }

        $headers = [
            'LRN',
            'First Name',
            'Middle Name',
            'Last Name',
            'Gender',
            'Birthdate',
            'Address',
            'Guardian Name',
            'Guardian Contact Number',
            'Grade Level',
            'Section',
            'Enrollment Status',
            'Promotion Status',
        ];

        $enrollments = Enrollment::query()
            ->with([
                'student:id,lrn,first_name,middle_name,last_name,gender,birthdate,address,guardian_name,contact_number',
                'gradeLevel:id,name',
                'section:id,name',
            ])
            ->where('academic_year_id', $academicYear->id)
            ->when(
                $selectedSectionIds->isNotEmpty(),
                fn ($query) => $query->whereIn('section_id', $selectedSectionIds->all())
            )
            ->whereIn('status', ['for_cashier_payment', 'enrolled'])
            ->get()
            ->sortBy(function (Enrollment $enrollment): string {
                return strtolower(trim("{$enrollment->student?->last_name} {$enrollment->student?->first_name}"));
            })
            ->values();

        $studentIds = $enrollments
            ->pluck('student_id')
            ->map(fn (mixed $id): int => (int) $id)
            ->unique()
            ->values();
        $promotionStatusByStudentId = collect();
        $isFinalizedYear = $academicYear->status === 'completed';

        if ($isFinalizedYear && $studentIds->isNotEmpty()) {
            $promotionStatusByStudentId = PermanentRecord::query()
                ->where('academic_year_id', $academicYear->id)
                ->whereIn('student_id', $studentIds->all())
                ->orderByDesc('id')
                ->get(['student_id', 'status'])
                ->groupBy('student_id')
                ->map(function ($records): string {
                    $status = (string) ($records->first()->status ?? '');

                    return match ($status) {
                        'promoted' => 'Promoted',
                        'conditional' => 'Conditional',
                        'retained' => 'Retained',
                        'completed' => 'Completed',
                        default => '',
                    };
                });
        }

        $rowsData = $enrollments
            ->map(fn (Enrollment $enrollment): array => [
                (string) ($enrollment->student?->lrn ?? ''),
                (string) ($enrollment->student?->first_name ?? ''),
                (string) ($enrollment->student?->middle_name ?? ''),
                (string) ($enrollment->student?->last_name ?? ''),
                (string) ($enrollment->student?->gender ?? ''),
                (string) ($enrollment->student?->birthdate?->toDateString() ?? ''),
                (string) ($enrollment->student?->address ?? ''),
                (string) ($enrollment->student?->guardian_name ?? ''),
                (string) ($enrollment->student?->contact_number ?? ''),
                (string) ($enrollment->gradeLevel?->name ?? ''),
                (string) ($enrollment->section?->name ?? ''),
                (string) $enrollment->status,
                (string) ($promotionStatusByStudentId->get((int) $enrollment->student_id, '')),
            ])
            ->all();

        $format = strtolower((string) ($validated['format'] ?? 'csv'));
        $sanitizedYear = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $academicYear->name));

        if ($format === 'xlsx') {
            $outputPath = storage_path('app/temp/'.uniqid('sf1-reference-', true).'.xlsx');
            if (! is_dir(dirname($outputPath))) {
                mkdir(dirname($outputPath), 0777, true);
            }
            $spreadsheet = new Spreadsheet;
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->fromArray($headers, null, 'A1');
            $sheet->fromArray($rowsData, null, 'A2');
            (new Xlsx($spreadsheet))->save($outputPath);
            $spreadsheet->disconnectWorksheets();

            return response()->download($outputPath, "sf1-reference-{$sanitizedYear}.xlsx")->deleteFileAfterSend(true);
        }

        $outputPath = storage_path('app/temp/'.uniqid('sf1-reference-', true).'.csv');
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }
        $handle = fopen($outputPath, 'w');
        if ($handle === false) {
            return back()->with('error', 'Unable to generate SF1 reference export.');
        }
        fputcsv($handle, $headers);
        foreach ($rowsData as $row) {
            fputcsv($handle, $row);
        }
        fclose($handle);

        return response()
            ->download($outputPath, "sf1-reference-{$sanitizedYear}.csv")
            ->deleteFileAfterSend(true);
    }

    private function resolveDirectoryStatus(
        ?string $enrollmentStatus,
        bool $reportCardSubmitted = false,
        bool $birthCertificateSubmitted = false,
    ): string {
        $hasMissingRequirements = ! $reportCardSubmitted || ! $birthCertificateSubmitted;

        return match ($enrollmentStatus) {
            'dropped' => 'dropped',
            'transferred_out' => 'transferred_out',
            'for_cashier_payment' => 'not_enrolled',
            'enrolled' => $hasMissingRequirements ? 'enrolled_with_missing_requirements' : 'enrolled',
            default => 'not_currently_enrolled',
        };
    }

    private function formatEnrollmentStatus(string $status): string
    {
        return match ($status) {
            'for_cashier_payment' => 'For Cashier Payment',
            'enrolled' => 'Enrolled',
            'dropped' => 'Dropped Out',
            'transferred_out' => 'Transferred Out',
            default => str($status)
                ->replace('_', ' ')
                ->title()
                ->toString(),
        };
    }

    private function normalizeGuardianContactNumber(string $phoneNumber): string
    {
        $digits = preg_replace('/\D+/', '', $phoneNumber) ?? '';

        if (str_starts_with($digits, '09') && strlen($digits) === 11) {
            return '+63'.substr($digits, 1);
        }

        if (str_starts_with($digits, '9') && strlen($digits) === 10) {
            return '+63'.$digits;
        }

        if (str_starts_with($digits, '63') && strlen($digits) === 12) {
            return '+'.$digits;
        }

        throw ValidationException::withMessages([
            'guardian_contact_number' => 'Guardian contact number must be a valid PH mobile number (e.g. +639XXXXXXXXX).',
        ]);
    }
}
