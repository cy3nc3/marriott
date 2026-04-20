<?php

namespace App\Http\Controllers\Registrar;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\Section;
use App\Models\Student;
use App\Models\User;
use App\Services\Auth\AccountActivationCodeManager;
use App\Services\DashboardCacheService;
use App\Services\Finance\BillingScheduleService;
use App\Services\Registrar\RegistrationAssessmentBuilder;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class EnrollmentController extends Controller
{
    private const DEFAULT_PARENT_BIRTHDAY = '1980-01-01';

    private const ASSESSMENT_CREDENTIAL_CACHE_TTL_MINUTES = 15;

    public function __construct(
        private BillingScheduleService $billingScheduleService,
        private AccountActivationCodeManager $accountActivationCodeManager,
    ) {}

    public function index(Request $request): Response
    {
        $schoolYearOptions = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'status', 'start_date'])
            ->map(function (AcademicYear $academicYear) {
                return [
                    'id' => (int) $academicYear->id,
                    'name' => $academicYear->name,
                    'status' => $academicYear->status,
                ];
            })
            ->values();

        $selectedAcademicYearId = $request->integer('academic_year_id');
        if (
            $selectedAcademicYearId <= 0
            || ! $schoolYearOptions->pluck('id')->contains($selectedAcademicYearId)
        ) {
            $selectedAcademicYearId = (int) ($schoolYearOptions->firstWhere('status', 'ongoing')['id']
                ?? ($schoolYearOptions->first()['id'] ?? 0));
        }

        $selectedAcademicYear = $selectedAcademicYearId > 0
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : null;

        $queueStatuses = ['for_cashier_payment'];

        $baseQuery = Enrollment::query()
            ->when($selectedAcademicYear, function ($query) use ($selectedAcademicYear) {
                $query->where('academic_year_id', $selectedAcademicYear->id);
            })
            ->whereIn('status', $queueStatuses);

        $search = $request->input('search');

        $enrollments = (clone $baseQuery)
            ->with([
                'student:id,lrn,first_name,middle_name,last_name,gender,birthdate,guardian_name,contact_number',
                'section:id,grade_level_id,name',
                'section.gradeLevel:id,name',
            ])
            ->when($search, function ($query, $search) {
                $query->whereHas('student', function ($studentQuery) use ($search) {
                    $studentQuery
                        ->where('lrn', 'like', "%{$search}%")
                        ->orWhere('first_name', 'like', "%{$search}%")
                        ->orWhere('middle_name', 'like', "%{$search}%")
                        ->orWhere('last_name', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate(10)
            ->through(function (Enrollment $enrollment) {
                return [
                    'id' => $enrollment->id,
                    'lrn' => $enrollment->student?->lrn ?? '',
                    'first_name' => $enrollment->student?->first_name ?? '',
                    'middle_name' => $enrollment->student?->middle_name,
                    'last_name' => $enrollment->student?->last_name ?? '',
                    'gender' => $enrollment->student?->gender,
                    'birthdate' => $enrollment->student?->birthdate?->toDateString(),
                    'guardian_name' => $enrollment->student?->guardian_name ?? '',
                    'guardian_contact_number' => $enrollment->student?->contact_number ?? '',
                    'payment_term' => $enrollment->payment_term,
                    'downpayment' => (float) $enrollment->downpayment,
                    'status' => $enrollment->status,
                    'grade_level_id' => $enrollment->grade_level_id,
                    'section_id' => $enrollment->section_id,
                    'section_label' => $enrollment->section?->gradeLevel?->name && $enrollment->section?->name
                        ? "{$enrollment->section->gradeLevel->name} - {$enrollment->section->name}"
                        : null,
                ];
            })
            ->withQueryString();

        $gradeLevelOptions = GradeLevel::query()
            ->orderBy('level_order')
            ->orderBy('id')
            ->get(['id', 'name'])
            ->map(function (GradeLevel $gradeLevel) {
                return [
                    'id' => $gradeLevel->id,
                    'name' => $gradeLevel->name,
                ];
            })
            ->values();

        $sectionOptions = Section::query()
            ->with('gradeLevel:id,name')
            ->when($selectedAcademicYear, function ($query) use ($selectedAcademicYear) {
                $query->where('academic_year_id', $selectedAcademicYear->id);
            })
            ->orderBy('grade_level_id')
            ->orderBy('name')
            ->get(['id', 'grade_level_id', 'name'])
            ->map(function (Section $section) {
                return [
                    'id' => $section->id,
                    'grade_level_id' => $section->grade_level_id,
                    'label' => $section->name,
                ];
            })
            ->values();

        return Inertia::render('registrar/enrollment/index', [
            'enrollments' => $enrollments,
            'grade_level_options' => $gradeLevelOptions,
            'section_options' => $sectionOptions,
            'school_year_options' => $schoolYearOptions->all(),
            'selected_school_year_id' => $selectedAcademicYear?->id,
            'selected_school_year_status' => $selectedAcademicYear?->status,
            'summary' => [
                'for_cashier_payment' => (clone $baseQuery)->where('status', 'for_cashier_payment')->count(),
            ],
            'filters' => [
                'search' => $search,
                'academic_year_id' => $selectedAcademicYear?->id,
            ],
        ]);
    }

    public function lookup(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'lrn' => ['required', 'string', 'digits:12'],
        ], [
            'lrn.digits' => 'LRN must be exactly 12 digits.',
        ]);

        $activeAcademicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first();

        if (! $activeAcademicYear) {
            return response()->json([
                'matched' => false,
                'academic_year_id' => null,
                'student' => null,
                'error' => 'No ongoing school year found.',
            ], 422);
        }

        $student = Student::query()
            ->where('lrn', $validated['lrn'])
            ->first();

        if (! $student) {
            return response()->json([
                'matched' => false,
                'academic_year_id' => (int) $activeAcademicYear->id,
                'student' => null,
            ]);
        }

        $fallbackGradeLevelId = (int) (GradeLevel::query()->orderBy('level_order')->value('id') ?? 0);
        $recommendedGradeLevelId = $fallbackGradeLevelId > 0
            ? $this->resolvePromotedGradeLevelId($student, $fallbackGradeLevelId)
            : null;

        return response()->json([
            'matched' => true,
            'academic_year_id' => (int) $activeAcademicYear->id,
            'student' => [
                'lrn' => $student->lrn,
                'first_name' => $student->first_name,
                'middle_name' => $student->middle_name,
                'last_name' => $student->last_name,
                'gender' => $student->gender,
                'birthdate' => $student->birthdate?->toDateString(),
                'guardian_name' => $student->guardian_name,
                'guardian_contact_number' => $student->contact_number,
                'recommended_grade_level_id' => $recommendedGradeLevelId,
            ],
        ]);
    }

    public function export(
        Request $request,
    ): BinaryFileResponse|RedirectResponse {
        $validated = $request->validate([
            'academic_year_id' => ['nullable', 'integer', 'exists:academic_years,id'],
            'section_ids' => ['nullable', 'array'],
            'section_ids.*' => ['integer', 'exists:sections,id'],
        ]);

        $selectedAcademicYearId = $request->integer('academic_year_id');
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

    public function printAssessment(
        Request $request,
        Enrollment $enrollment,
        RegistrationAssessmentBuilder $builder,
    ): View {
        $credentialToken = trim((string) $request->query('credential_token', ''));
        $credentialSnapshot = $this->pullAssessmentCredentialSnapshot(
            (int) $enrollment->id,
            $credentialToken !== '' ? $credentialToken : null
        );

        return view('registrar.enrollment-assessment', [
            'assessment' => $builder->build($enrollment, $credentialSnapshot),
            'autoprint' => $request->boolean('autoprint'),
        ]);
    }

    public function regenerateAssessmentCredentials(Enrollment $enrollment): RedirectResponse
    {
        $student = $enrollment->student;
        if (! $student instanceof Student) {
            return back()->with('error', 'Unable to regenerate activation codes for this enrollment.');
        }

        $assessmentCredentialToken = $this->storeAssessmentCredentialSnapshot(
            (int) $enrollment->id,
            $this->ensureAccounts($student, true)
        );

        $assessmentPrintParameters = [
            'enrollment' => (int) $enrollment->id,
            'autoprint' => 1,
        ];

        if ($assessmentCredentialToken) {
            $assessmentPrintParameters['credential_token'] = $assessmentCredentialToken;
        }

        return back()
            ->with('success', 'Activation codes regenerated.')
            ->with('assessment_print_url', route('registrar.enrollment.assessment', $assessmentPrintParameters));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'lrn' => ['required', 'string', 'digits:12'],
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'nullable|string|in:Male,Female',
            'birthdate' => 'required|date|before_or_equal:today',
            'guardian_name' => 'required|string|max:255',
            'guardian_contact_number' => 'required_without:emergency_contact|string|digits:11',
            'emergency_contact' => 'nullable|string|digits:11',
            'payment_term' => 'required|string|in:cash,full,monthly,quarterly,semi-annual',
            'downpayment' => 'nullable|numeric|min:0|max:999999.99',
            'section_id' => 'nullable|integer|exists:sections,id',
            'grade_level_id' => 'nullable|integer|exists:grade_levels,id',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
        ], [
            'lrn.digits' => 'LRN must be exactly 12 digits.',
            'guardian_contact_number.digits' => 'Guardian contact number must be exactly 11 digits.',
            'emergency_contact.digits' => 'Guardian contact number must be exactly 11 digits.',
        ]);
        $guardianContactNumber = (string) ($validated['guardian_contact_number'] ?? $validated['emergency_contact'] ?? '');

        $activeAcademicYear = isset($validated['academic_year_id'])
            ? AcademicYear::query()->find((int) $validated['academic_year_id'])
            : null;

        if (! $activeAcademicYear) {
            $activeAcademicYear = AcademicYear::query()
                ->where('status', 'ongoing')
                ->first() ?? AcademicYear::query()->latest('start_date')->first();
        }

        if (! $activeAcademicYear) {
            return back()->with('error', 'No academic year found. Please configure one first.');
        }

        if ($activeAcademicYear->status === 'completed') {
            return back()->with('error', 'Cannot create intake records for a completed school year.');
        }

        $gradeLevelId = GradeLevel::query()->orderBy('level_order')->value('id');
        if (! $gradeLevelId) {
            return back()->with('error', 'No grade levels found. Please set up grade levels first.');
        }

        $storedEnrollmentId = null;
        $assessmentCredentialToken = null;

        try {
            $storeResult = DB::transaction(function () use ($validated, $guardianContactNumber, $activeAcademicYear, $gradeLevelId): array {
                $selectedSection = $this->resolveSectionForIntake(
                    isset($validated['section_id']) ? (int) $validated['section_id'] : null,
                    (int) $activeAcademicYear->id
                );
                $selectedGradeLevelId = isset($validated['grade_level_id']) ? (int) $validated['grade_level_id'] : null;

                $student = Student::query()->firstOrNew([
                    'lrn' => $validated['lrn'],
                ]);

                $student->first_name = $validated['first_name'];
                $student->middle_name = $validated['middle_name'] ?? null;
                $student->last_name = $validated['last_name'];
                $student->guardian_name = $validated['guardian_name'];
                $student->contact_number = $guardianContactNumber;
                $student->birthdate = $validated['birthdate'];

                if (array_key_exists('gender', $validated)) {
                    $student->gender = $validated['gender'];
                }

                $student->save();

                $accountCredentials = $this->ensureAccounts($student, true);

                $existingEnrollment = Enrollment::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $activeAcademicYear->id)
                    ->first();

                if ($existingEnrollment && $existingEnrollment->status === 'enrolled') {
                    throw new \RuntimeException('Student is already fully enrolled for the active school year.');
                }

                $paymentTerm = $this->normalizePaymentTerm($validated['payment_term']);
                $downpayment = $this->normalizeDownpayment($paymentTerm, $validated['downpayment'] ?? null);
                $resolvedGradeLevelId = $this->resolveEnrollmentGradeLevelId(
                    $selectedSection,
                    $selectedGradeLevelId,
                    $this->resolvePromotedGradeLevelId($student, $gradeLevelId)
                );

                if ($existingEnrollment) {
                    $existingEnrollment->update([
                        'grade_level_id' => $resolvedGradeLevelId,
                        'section_id' => $selectedSection?->id,
                        'payment_term' => $paymentTerm,
                        'downpayment' => $downpayment,
                        'status' => 'for_cashier_payment',
                    ]);

                    $this->billingScheduleService->syncForEnrollment($existingEnrollment);

                    return [
                        'enrollment_id' => (int) $existingEnrollment->id,
                        'credentials' => $accountCredentials,
                    ];
                }

                $enrollment = Enrollment::query()->create([
                    'student_id' => $student->id,
                    'academic_year_id' => $activeAcademicYear->id,
                    'grade_level_id' => $resolvedGradeLevelId,
                    'section_id' => $selectedSection?->id,
                    'payment_term' => $paymentTerm,
                    'downpayment' => $downpayment,
                    'status' => 'for_cashier_payment',
                ]);

                $this->billingScheduleService->syncForEnrollment($enrollment);

                return [
                    'enrollment_id' => (int) $enrollment->id,
                    'credentials' => $accountCredentials,
                ];
            });

            $storedEnrollmentId = (int) ($storeResult['enrollment_id'] ?? 0);
            $assessmentCredentialToken = $this->storeAssessmentCredentialSnapshot(
                $storedEnrollmentId,
                is_array($storeResult['credentials'] ?? null)
                    ? $storeResult['credentials']
                    : []
            );
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                return back()->with('error', 'Student already has an intake record for the active school year.');
            }

            throw $exception;
        }

        DashboardCacheService::bust();

        $assessmentPrintParameters = [
            'enrollment' => $storedEnrollmentId,
            'autoprint' => 1,
        ];

        if ($assessmentCredentialToken) {
            $assessmentPrintParameters['credential_token'] = $assessmentCredentialToken;
        }

        return back()
            ->with('success', 'Enrollment intake saved.')
            ->with(
                'assessment_print_url',
                $storedEnrollmentId > 0
                    ? route('registrar.enrollment.assessment', $assessmentPrintParameters)
                    : null
            );
    }

    public function update(Request $request, Enrollment $enrollment): RedirectResponse
    {
        if ($enrollment->status === 'enrolled') {
            return back()->with('error', 'Enrolled students can no longer be edited in the intake queue.');
        }

        $validated = $request->validate([
            'first_name' => 'required|string|max:255',
            'middle_name' => 'nullable|string|max:255',
            'last_name' => 'required|string|max:255',
            'gender' => 'nullable|string|in:Male,Female',
            'birthdate' => 'required|date|before_or_equal:today',
            'guardian_name' => 'required|string|max:255',
            'guardian_contact_number' => 'required_without:emergency_contact|string|digits:11',
            'emergency_contact' => 'nullable|string|digits:11',
            'payment_term' => 'required|string|in:cash,full,monthly,quarterly,semi-annual',
            'downpayment' => 'nullable|numeric|min:0|max:999999.99',
            'section_id' => 'nullable|integer|exists:sections,id',
            'grade_level_id' => 'nullable|integer|exists:grade_levels,id',
        ], [
            'guardian_contact_number.digits' => 'Guardian contact number must be exactly 11 digits.',
            'emergency_contact.digits' => 'Guardian contact number must be exactly 11 digits.',
        ]);
        $guardianContactNumber = (string) ($validated['guardian_contact_number'] ?? $validated['emergency_contact'] ?? '');

        $paymentTerm = $this->normalizePaymentTerm($validated['payment_term']);
        $downpayment = $this->normalizeDownpayment($paymentTerm, $validated['downpayment'] ?? null);

        try {
            DB::transaction(function () use ($enrollment, $validated, $guardianContactNumber, $paymentTerm, $downpayment) {
                $student = $enrollment->student;
                $selectedSection = $this->resolveSectionForIntake(
                    isset($validated['section_id']) ? (int) $validated['section_id'] : null,
                    (int) $enrollment->academic_year_id
                );
                $selectedGradeLevelId = isset($validated['grade_level_id']) ? (int) $validated['grade_level_id'] : null;
                $resolvedGradeLevelId = $this->resolveEnrollmentGradeLevelId(
                    $selectedSection,
                    $selectedGradeLevelId,
                    (int) $enrollment->grade_level_id
                );

                if ($student) {
                    $studentAttributes = [
                        'first_name' => $validated['first_name'],
                        'middle_name' => $validated['middle_name'] ?? null,
                        'last_name' => $validated['last_name'],
                        'guardian_name' => $validated['guardian_name'],
                        'contact_number' => $guardianContactNumber,
                        'birthdate' => $validated['birthdate'],
                    ];

                    if (array_key_exists('gender', $validated)) {
                        $studentAttributes['gender'] = $validated['gender'];
                    }

                    $student->update($studentAttributes);

                    $this->ensureAccounts($student);
                }

                $enrollment->update([
                    'grade_level_id' => $resolvedGradeLevelId,
                    'section_id' => $selectedSection?->id,
                    'payment_term' => $paymentTerm,
                    'downpayment' => $downpayment,
                    'status' => 'for_cashier_payment',
                ]);

                $this->billingScheduleService->syncForEnrollment($enrollment);
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        DashboardCacheService::bust();

        return back()->with('success', 'Enrollment intake updated.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        if ($enrollment->status === 'enrolled') {
            return back()->with('error', 'Cannot remove a fully enrolled student from the intake queue.');
        }

        $enrollment->delete();

        DashboardCacheService::bust();

        return back()->with('success', 'Enrollment intake removed from queue.');
    }

    private function normalizePaymentTerm(string $paymentTerm): string
    {
        return $paymentTerm === 'full' ? 'cash' : $paymentTerm;
    }

    private function normalizeDownpayment(string $paymentTerm, mixed $downpayment): float
    {
        if ($paymentTerm === 'cash') {
            return 0;
        }

        return round((float) ($downpayment ?? 0), 2);
    }

    private function resolvePromotedGradeLevelId(Student $student, int $fallbackGradeLevelId): int
    {
        $lastEnrollmentGradeLevelId = Enrollment::query()
            ->where('student_id', $student->id)
            ->whereNotNull('grade_level_id')
            ->latest('id')
            ->value('grade_level_id');

        if (! $lastEnrollmentGradeLevelId) {
            return $fallbackGradeLevelId;
        }

        $currentGradeLevelOrder = GradeLevel::query()
            ->whereKey($lastEnrollmentGradeLevelId)
            ->value('level_order');

        if (! $currentGradeLevelOrder) {
            return (int) $lastEnrollmentGradeLevelId;
        }

        $nextGradeLevelId = GradeLevel::query()
            ->where('level_order', '>', $currentGradeLevelOrder)
            ->orderBy('level_order')
            ->value('id');

        return (int) ($nextGradeLevelId ?: $lastEnrollmentGradeLevelId);
    }

    /**
     * @return array{
     *     student_activation_code: string|null,
     *     parent_activation_code: string|null
     * }
     */
    private function ensureAccounts(Student $student, bool $issueActivationCodes = false): array
    {
        $studentEmail = $this->buildStudentEmail($student);
        $studentUser = $student->user;
        $studentWasCreated = false;

        if (! $studentUser) {
            $studentUser = User::query()->firstOrCreate(
                ['email' => $studentEmail],
                [
                    'first_name' => $student->first_name,
                    'last_name' => $student->last_name,
                    'name' => trim("{$student->first_name} {$student->last_name}"),
                    'birthday' => $student->birthdate,
                    'role' => UserRole::STUDENT->value,
                    'is_active' => true,
                    'password' => Hash::make(Str::random(40)),
                    'must_change_password' => true,
                ]
            );
            $studentWasCreated = $studentUser->wasRecentlyCreated;
        }

        if ($studentUser->email !== $studentEmail) {
            $existingStudentUser = User::query()
                ->where('email', $studentEmail)
                ->first();

            if ($existingStudentUser && $existingStudentUser->id !== $studentUser->id) {
                $studentUser = $existingStudentUser;
            }
        }

        $studentUser->update([
            'email' => $studentEmail,
            'first_name' => $student->first_name,
            'last_name' => $student->last_name,
            'name' => trim("{$student->first_name} {$student->last_name}"),
            'birthday' => $student->birthdate,
            'role' => UserRole::STUDENT->value,
            'is_active' => true,
            'access_expires_at' => null,
        ]);

        if ($student->user_id !== $studentUser->id) {
            $student->update(['user_id' => $studentUser->id]);
        }

        $parentUser = User::query()->firstOrCreate(
            ['email' => "parent.{$student->lrn}@marriott.edu"],
            [
                'first_name' => 'Parent',
                'last_name' => $student->last_name,
                'name' => "Parent {$student->last_name}",
                'birthday' => self::DEFAULT_PARENT_BIRTHDAY,
                'role' => UserRole::PARENT->value,
                'is_active' => true,
                'password' => Hash::make(Str::random(40)),
                'must_change_password' => true,
            ]
        );
        $parentWasCreated = $parentUser->wasRecentlyCreated;

        $parentUser->update([
            'first_name' => 'Parent',
            'last_name' => $student->last_name,
            'name' => "Parent {$student->last_name}",
            'birthday' => self::DEFAULT_PARENT_BIRTHDAY,
            'role' => UserRole::PARENT->value,
            'is_active' => true,
            'access_expires_at' => null,
        ]);

        $pivotQuery = DB::table('parent_student')
            ->where('parent_id', $parentUser->id)
            ->where('student_id', $student->id);

        if ($pivotQuery->exists()) {
            $pivotQuery->update([
                'updated_at' => now(),
            ]);

            $issuedCodes = $this->issueActivationCodesForAssessment(
                $studentUser,
                $parentUser,
                $issueActivationCodes,
                $studentWasCreated,
                $parentWasCreated
            );

            return $issuedCodes;
        }

        DB::table('parent_student')->insert([
            'parent_id' => $parentUser->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return $this->issueActivationCodesForAssessment(
            $studentUser,
            $parentUser,
            $issueActivationCodes,
            $studentWasCreated,
            $parentWasCreated
        );
    }

    private function buildStudentEmail(Student $student): string
    {
        $normalizedSurname = $this->normalizeSurnameForEmail((string) $student->last_name);

        return "{$normalizedSurname}.{$student->lrn}@marriott.edu";
    }

    private function normalizeSurnameForEmail(string $surname): string
    {
        $normalizedSurname = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $surname));

        if ($normalizedSurname === '') {
            return 'student';
        }

        return $normalizedSurname;
    }

    /**
     * @return array{
     *     student_activation_code: string|null,
     *     parent_activation_code: string|null
     * }
     */
    private function issueActivationCodesForAssessment(
        User $studentUser,
        User $parentUser,
        bool $issueActivationCodes,
        bool $studentWasCreated,
        bool $parentWasCreated,
    ): array {
        if (! $issueActivationCodes) {
            return [
                'student_activation_code' => null,
                'parent_activation_code' => null,
            ];
        }

        $issueStudentCode = $studentWasCreated || (bool) $studentUser->must_change_password;
        $issueParentCode = $parentWasCreated || (bool) $parentUser->must_change_password;

        if ($issueStudentCode && ! $studentUser->must_change_password) {
            $studentUser->forceFill(['must_change_password' => true])->save();
        }

        if ($issueParentCode && ! $parentUser->must_change_password) {
            $parentUser->forceFill(['must_change_password' => true])->save();
        }

        return [
            'student_activation_code' => $issueStudentCode
                ? $this->accountActivationCodeManager->issueForUser($studentUser)
                : null,
            'parent_activation_code' => $issueParentCode
                ? $this->accountActivationCodeManager->issueForUser($parentUser)
                : null,
        ];
    }

    /**
     * @param  array{
     *     student_activation_code?: string|null,
     *     parent_activation_code?: string|null
     * }  $credentials
     */
    private function storeAssessmentCredentialSnapshot(int $enrollmentId, array $credentials): ?string
    {
        if (
            $enrollmentId <= 0
            || empty($credentials['student_activation_code'])
            && empty($credentials['parent_activation_code'])
        ) {
            return null;
        }

        $token = (string) Str::uuid();
        $cacheKey = $this->assessmentCredentialCacheKey($enrollmentId, $token);

        Cache::put($cacheKey, [
            'student_activation_code' => $credentials['student_activation_code'] ?? null,
            'parent_activation_code' => $credentials['parent_activation_code'] ?? null,
        ], now()->addMinutes(self::ASSESSMENT_CREDENTIAL_CACHE_TTL_MINUTES));

        return $token;
    }

    /**
     * @return array{
     *     student_activation_code?: string|null,
     *     parent_activation_code?: string|null
     * }
     */
    private function pullAssessmentCredentialSnapshot(int $enrollmentId, ?string $token): array
    {
        if ($enrollmentId <= 0 || ! $token) {
            return [];
        }

        $cachedPayload = Cache::pull($this->assessmentCredentialCacheKey($enrollmentId, $token));

        return is_array($cachedPayload) ? $cachedPayload : [];
    }

    private function assessmentCredentialCacheKey(int $enrollmentId, string $token): string
    {
        return "registration-assessment:{$enrollmentId}:{$token}";
    }

    private function resolveSectionForIntake(?int $sectionId, int $academicYearId): ?Section
    {
        if (! $sectionId) {
            return null;
        }

        $section = Section::query()
            ->whereKey($sectionId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        if (! $section) {
            throw new \RuntimeException('Selected section is not available for the active school year.');
        }

        return $section;
    }

    private function resolveEnrollmentGradeLevelId(?Section $selectedSection, ?int $selectedGradeLevelId, int $fallbackGradeLevelId): int
    {
        if ($selectedSection) {
            if (
                $selectedGradeLevelId
                && (int) $selectedSection->grade_level_id !== $selectedGradeLevelId
            ) {
                throw new \RuntimeException('Selected section does not match the selected grade level.');
            }

            return (int) $selectedSection->grade_level_id;
        }

        if ($selectedGradeLevelId) {
            return $selectedGradeLevelId;
        }

        return $fallbackGradeLevelId;
    }
}
