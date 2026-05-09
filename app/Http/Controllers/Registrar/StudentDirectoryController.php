<?php

namespace App\Http\Controllers\Registrar;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Auth\EnrollmentAccountClaimService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
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

        $studentBaseQuery = Student::query()
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
            });

        $students = (clone $studentBaseQuery)
            ->with([
                'user:id,email,role,must_change_password,password_updated_at',
                'parents:id,email,role,must_change_password,password_updated_at',
                'enrollments' => function ($query) use ($ongoingAcademicYear) {
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
            ->orderBy('last_name')
            ->orderBy('first_name')
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
            ],
        ]);
    }

    public function update(Request $request, Student $student): RedirectResponse
    {
        $validated = $request->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'middle_name' => ['nullable', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', 'in:Male,Female'],
            'birthdate' => ['required', 'date', 'before_or_equal:today'],
            'guardian_name' => ['required', 'string', 'max:255'],
            'guardian_contact_number' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'report_card_submitted' => ['nullable', 'boolean'],
            'birth_certificate_submitted' => ['nullable', 'boolean'],
            'send_claim_email_confirmation' => ['nullable', 'boolean'],
        ]);

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
        });

        if ($requiresClaimEmailConfirmation && $claimEmailConfirmed && $syncableEnrollment instanceof Enrollment) {
            $syncableEnrollment->refresh();
            $this->enrollmentAccountClaimService->issueForEnrollment($syncableEnrollment);

            return back()->with('success', 'Student details updated. Account-claim email sent.');
        }

        return back()->with('success', 'Student details updated.');
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

    public function exportSf1Reference(Request $request): BinaryFileResponse|RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'section_ids' => ['nullable', 'array'],
            'section_ids.*' => ['integer', 'exists:sections,id'],
        ]);

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

        $outputPath = storage_path('app/temp/'.uniqid('sf1-reference-', true).'.csv');
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $rows = Enrollment::query()
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

        $handle = fopen($outputPath, 'w');
        if ($handle === false) {
            return back()->with('error', 'Unable to generate SF1 reference export.');
        }

        fputcsv($handle, [
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
        ]);

        foreach ($rows as $enrollment) {
            fputcsv($handle, [
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
            ]);
        }

        fclose($handle);

        $sanitizedYear = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $academicYear->name));

        return response()
            ->download($outputPath, "sf1-reference-{$sanitizedYear}.csv")
            ->deleteFileAfterSend(true);
    }

    private function resolveDirectoryStatus(
        ?string $enrollmentStatus,
        bool $reportCardSubmitted = false,
        bool $birthCertificateSubmitted = false,
    ): string
    {
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
