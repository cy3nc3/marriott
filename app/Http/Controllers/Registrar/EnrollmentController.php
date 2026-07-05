<?php

namespace App\Http\Controllers\Registrar;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\ExportEnrollmentRequest;
use App\Http\Requests\Registrar\LookupEnrollmentRequest;
use App\Http\Requests\Registrar\StoreEnrollmentRequest;
use App\Http\Requests\Registrar\UpdateEnrollmentRequest;
use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\Enrollment;
use App\Models\GradeLevel;
use App\Models\PermanentRecord;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentDiscount;
use App\Models\User;
use App\Services\DashboardCacheService;
use App\Services\Finance\BillingScheduleService;
use App\Services\Registrar\RegistrationAssessmentBuilder;
use App\Services\SchoolForms\EnrollmentExportBuilder;
use App\Services\SchoolForms\EnrollmentTemplateAdapter;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

class EnrollmentController extends Controller
{
    private const DEFAULT_PARENT_BIRTHDAY = '1980-01-01';

    public function __construct(
        private BillingScheduleService $billingScheduleService,
    ) {}

    public function index(Request $request): Response
    {
        $activeAcademicYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->first() ?? AcademicYear::query()
            ->latest('start_date')
            ->first();

        $queueStatuses = ['for_cashier_payment', 'enrolled'];
        $selectedStatus = (string) $request->query('status', 'for_cashier_payment');
        if (! in_array($selectedStatus, $queueStatuses, true)) {
            $selectedStatus = 'for_cashier_payment';
        }
        $selectedSort = (string) $request->query('sort', 'newest');
        if (! in_array($selectedSort, ['newest', 'oldest'], true)) {
            $selectedSort = 'newest';
        }
        $selectedRequirements = (string) $request->query('requirements', 'all');
        if (! in_array($selectedRequirements, ['all', 'missing', 'complete'], true)) {
            $selectedRequirements = 'all';
        }

        $baseQuery = Enrollment::query()
            ->when($activeAcademicYear, function ($query) use ($activeAcademicYear) {
                $query->where('academic_year_id', $activeAcademicYear->id);
            })
            ->whereIn('status', $queueStatuses);

        $search = trim((string) $request->input('search', ''));
        $normalizedSearch = mb_strtolower($search);
        $searchPattern = "%{$normalizedSearch}%";

        $enrollments = (clone $baseQuery)
            ->with([
                'student:id,user_id,lrn,first_name,middle_name,last_name,gender,birthdate,guardian_name,contact_number',
                'student.user:id,personal_email',
                'student.studentDiscounts' => function ($query) use ($activeAcademicYear) {
                    $query->select(['id', 'student_id', 'discount_id', 'academic_year_id'])
                        ->with('discount:id,name')
                        ->when($activeAcademicYear, function ($innerQuery) use ($activeAcademicYear) {
                            $innerQuery->where('academic_year_id', $activeAcademicYear->id);
                        });
                },
                'section:id,grade_level_id,name',
                'section.gradeLevel:id,name',
            ])
            ->when($search !== '', function ($query) use ($searchPattern) {
                $query->whereHas('student', function ($studentQuery) use ($searchPattern) {
                    $studentQuery
                        ->whereRaw('LOWER(lrn) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(first_name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(middle_name) LIKE ?', [$searchPattern])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', [$searchPattern]);
                });
            })
            ->where('status', $selectedStatus)
            ->when($selectedRequirements !== 'all', function ($query) use ($selectedRequirements) {
                if ($selectedRequirements === 'missing') {
                    $query->where(function ($innerQuery) {
                        $innerQuery
                            ->where('report_card_submitted', false)
                            ->orWhere('birth_certificate_submitted', false);
                    });

                    return;
                }

                $query
                    ->where('report_card_submitted', true)
                    ->where('birth_certificate_submitted', true);
            })
            ->when($selectedSort === 'oldest', fn ($query) => $query->orderBy('created_at')->orderBy('id'))
            ->when($selectedSort === 'newest', fn ($query) => $query->orderByDesc('created_at')->orderByDesc('id'))
            ->when($selectedSort === 'grade', function ($query) {
                $query->join('grade_levels', 'enrollments.grade_level_id', '=', 'grade_levels.id')
                    ->orderBy('grade_levels.level_order')
                    ->orderBy('enrollments.created_at')
                    ->select('enrollments.*');
            })
            ->when($selectedSort === 'section', function ($query) {
                $query->leftJoin('sections', 'enrollments.section_id', '=', 'sections.id')
                    ->orderBy('sections.name')
                    ->orderBy('enrollments.created_at')
                    ->select('enrollments.*');
            })
            ->paginate(10)
            ->through(function (Enrollment $enrollment) {
                return [
                    'id' => $enrollment->id,
                    'lrn' => $enrollment->student?->lrn ?? '',
                    'email' => $enrollment->email,
                    'student_personal_email' => $enrollment->student?->user?->personal_email,
                    'first_name' => $enrollment->student?->first_name ?? '',
                    'middle_name' => $enrollment->student?->middle_name,
                    'last_name' => $enrollment->student?->last_name ?? '',
                    'gender' => $enrollment->student?->gender,
                    'birthdate' => $enrollment->student?->birthdate?->toDateString(),
                    'guardian_name' => $enrollment->student?->guardian_name ?? '',
                    'guardian_contact_number' => $enrollment->student?->contact_number ?? '',
                    'payment_term' => $enrollment->payment_term,
                    'downpayment' => (float) $enrollment->downpayment,
                    'report_card_submitted' => (bool) $enrollment->report_card_submitted,
                    'birth_certificate_submitted' => (bool) $enrollment->birth_certificate_submitted,
                    'status' => $enrollment->status,
                    'grade_level_id' => $enrollment->grade_level_id,
                    'section_id' => $enrollment->section_id,
                    'section_label' => $enrollment->section?->gradeLevel?->name && $enrollment->section?->name
                        ? "{$enrollment->section->gradeLevel->name} - {$enrollment->section->name}"
                        : null,
                    'discount_id' => $enrollment->student?->studentDiscounts?->first()?->discount_id,
                    'discount_name' => $enrollment->student?->studentDiscounts?->first()?->discount?->name,
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
            ->when($activeAcademicYear, function ($query) use ($activeAcademicYear) {
                $query->where('academic_year_id', $activeAcademicYear->id);
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
        $discountOptions = Discount::query()
            ->orderBy('name')
            ->get(['id', 'name'])
            ->map(fn (Discount $discount) => [
                'id' => $discount->id,
                'name' => $discount->name,
            ])
            ->values();

        return Inertia::render('registrar/enrollment/index', [
            'enrollments' => $enrollments,
            'grade_level_options' => $gradeLevelOptions,
            'section_options' => $sectionOptions,
            'discount_options' => $discountOptions,
            'active_school_year' => $activeAcademicYear ? [
                'id' => (int) $activeAcademicYear->id,
                'name' => $activeAcademicYear->name,
                'status' => $activeAcademicYear->status,
            ] : null,
            'summary' => [
                'for_cashier_payment' => (clone $baseQuery)->where('status', 'for_cashier_payment')->count(),
                'enrolled' => (clone $baseQuery)->where('status', 'enrolled')->count(),
            ],
            'filters' => [
                'search' => $search !== '' ? $search : null,
                'status' => $selectedStatus,
                'sort' => $selectedSort,
                'requirements' => $selectedRequirements,
            ],
        ]);
    }

    public function lookup(LookupEnrollmentRequest $request): JsonResponse
    {
        $validated = $request->validated();

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

        $policy = $this->buildEnrollmentPolicySnapshot($student, $activeAcademicYear);

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
                'student_personal_email' => $student->user?->personal_email,
                'recommended_grade_level_id' => $policy['recommended_grade_level_id'],
            ],
            'grade_prefill_mode' => $policy['grade_prefill_mode'],
            'grade_guardrail' => $policy['grade_guardrail'],
            'status_flags' => $policy['status_flags'],
            'source_context' => $policy['source_context'],
        ]);
    }

    public function export(
        ExportEnrollmentRequest $request,
        EnrollmentExportBuilder $enrollmentExportBuilder,
        EnrollmentTemplateAdapter $enrollmentTemplateAdapter,
    ): BinaryFileResponse|RedirectResponse|StreamedResponse|HttpResponse {
        $validated = $request->validated();

        $selectedAcademicYearId = (int) ($validated['academic_year_id'] ?? 0);

        $academicYear = $selectedAcademicYearId > 0
            ? AcademicYear::query()->find($selectedAcademicYearId)
            : AcademicYear::query()->where('status', 'ongoing')->first();

        if (! $academicYear) {
            return back()->with('error', 'No academic year found for enrollment export.');
        }

        $rows = $enrollmentExportBuilder->buildRows($academicYear);
        $format = strtolower((string) ($validated['format'] ?? 'xlsx'));
        $sanitizedYear = strtolower((string) preg_replace('/[^A-Za-z0-9]+/', '-', $academicYear->name));

        if ($format === 'csv') {
            $headers = [
                'Name', 'Grade Level', 'Section', 'OR Number', 'Date', 'Total', 'Misc', 'Misc Discount',
                'Misc Sibling Discount', 'Misc Mode', 'Tuition', 'Tuition Sibling Discount', 'Tuition Mode',
                'Payment Plan', 'Early Enrollment Discount', 'FAPE', 'FAPE Previous Year', 'Overall Discount',
                'Special Discount', 'Balance', 'Overpayment', 'Reservation Status', 'Old/New Status', 'Remarks',
            ];

            return response()->streamDownload(function () use ($rows, $headers): void {
                $handle = fopen('php://output', 'w');
                if ($handle === false) {
                    return;
                }

                fputcsv($handle, $headers);
                foreach ($rows as $row) {
                    fputcsv($handle, [
                        $row['name'] ?? '',
                        $row['grade_level'] ?? '',
                        $row['section'] ?? '',
                        $row['or_number'] ?? '',
                        $row['date'] ?? '',
                        $row['total'] ?? 0,
                        $row['misc'] ?? 0,
                        $row['misc_discount'] ?? 0,
                        $row['misc_sibling_discount'] ?? 0,
                        $row['misc_mode'] ?? '',
                        $row['tuition'] ?? 0,
                        $row['tuition_sibling_discount'] ?? 0,
                        $row['tuition_mode'] ?? '',
                        $row['payment_plan'] ?? '',
                        $row['early_enrollment_discount'] ?? 0,
                        $row['fape'] ?? 0,
                        $row['fape_previous_year'] ?? 0,
                        $row['overall_discount'] ?? 0,
                        $row['special_discount'] ?? 0,
                        $row['balance'] ?? 0,
                        $row['overpayment'] ?? 0,
                        $row['reservation_status'] ?? '',
                        $row['old_new_status'] ?? '',
                        $row['remarks'] ?? '',
                    ]);
                }

                fclose($handle);
            }, "enrollment-{$sanitizedYear}.csv");
        }

        if ($format === 'pdf') {
            $rowCollection = collect($rows);
            $pdf = Pdf::loadView('exports.enrollment-pdf', [
                'metadata' => [
                    'school_year' => $academicYear->name,
                    'generated_at' => now()->format('F j, Y h:i A'),
                ],
                'summary' => [
                    'total_count' => $rowCollection->count(),
                    'for_cashier_payment_count' => $rowCollection->where('reservation_status', 'for_cashier_payment')->count(),
                    'enrolled_count' => $rowCollection->where('reservation_status', 'enrolled')->count(),
                    'balance_total' => (float) $rowCollection->sum(fn (array $row) => (float) ($row['balance'] ?? 0)),
                ],
                'rows' => $rows,
            ])->setPaper('a4', 'landscape');

            return $pdf->download("enrollment-{$sanitizedYear}.pdf");
        }

        $templatePath = base_path('templates/_SY 26-27 Enrolment.xlsx');
        if (! is_file($templatePath)) {
            return back()->with('error', 'Enrollment export template is missing.');
        }

        $outputPath = storage_path('app/temp/'.uniqid('enrollment-', true).'.xlsx');
        if (! is_dir(dirname($outputPath))) {
            mkdir(dirname($outputPath), 0777, true);
        }

        $originalMemoryLimit = ini_get('memory_limit');
        ini_set('memory_limit', '512M');

        try {
            $enrollmentTemplateAdapter->exportRows(
                $templatePath,
                $outputPath,
                $enrollmentExportBuilder->buildMetadata($academicYear),
                $rows
            );
        } finally {
            if ($originalMemoryLimit !== false) {
                ini_set('memory_limit', $originalMemoryLimit);
            }
        }

        return response()
            ->download($outputPath, "enrollment-{$sanitizedYear}.xlsx")
            ->deleteFileAfterSend(true);
    }

    public function printAssessment(
        Request $request,
        Enrollment $enrollment,
        RegistrationAssessmentBuilder $builder,
    ): View {
        return view('registrar.enrollment-assessment', [
            'assessment' => $builder->build($enrollment),
            'autoprint' => $request->boolean('autoprint'),
        ]);
    }

    public function store(StoreEnrollmentRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $guardianContactNumber = $this->normalizeGuardianContactNumber(
            (string) ($validated['guardian_contact_number'] ?? $validated['emergency_contact'] ?? '')
        );

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
            return back()->with('error', 'Cannot create enrollment records for a completed school year.');
        }

        $gradeLevelId = GradeLevel::query()->orderBy('level_order')->value('id');
        if (! $gradeLevelId) {
            return back()->with('error', 'No grade levels found. Please set up grade levels first.');
        }

        $storedEnrollmentId = null;

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

                $this->ensureAccounts(
                    $student,
                    $this->normalizeNullableEmail($validated['student_personal_email'] ?? null),
                    $this->normalizeNullableEmail($validated['email'] ?? null)
                );

                $existingEnrollment = Enrollment::query()
                    ->where('student_id', $student->id)
                    ->where('academic_year_id', $activeAcademicYear->id)
                    ->first();

                if ($existingEnrollment && $existingEnrollment->status === 'enrolled') {
                    throw new \RuntimeException('Student is already fully enrolled for the active school year.');
                }

                $paymentTerm = $this->normalizePaymentTerm($validated['payment_term']);
                $downpayment = $this->normalizeDownpayment($paymentTerm, $validated['downpayment'] ?? null);
                $policy = $this->buildEnrollmentPolicySnapshot($student, $activeAcademicYear);
                $resolvedGradeLevelId = $this->resolveEnrollmentGradeLevelId(
                    $selectedSection,
                    $selectedGradeLevelId,
                    (int) ($policy['recommended_grade_level_id'] ?? $gradeLevelId)
                );
                $this->assertGradeSelectionWithinPolicy(
                    $resolvedGradeLevelId,
                    $policy,
                );
                $this->resolveOlderStatusesIfConfirmed($student, $validated, $policy);

                if ($existingEnrollment) {
                    $existingEnrollment->update([
                        'grade_level_id' => $resolvedGradeLevelId,
                        'email' => $validated['email'] ?? null,
                        'section_id' => $selectedSection?->id,
                        'payment_term' => $paymentTerm,
                        'downpayment' => $downpayment,
                        'report_card_submitted' => (bool) ($validated['report_card_submitted'] ?? false),
                        'birth_certificate_submitted' => (bool) ($validated['birth_certificate_submitted'] ?? false),
                        'status' => 'for_cashier_payment',
                    ]);

                    $this->syncStudentDiscountForAcademicYear(
                        $student,
                        $activeAcademicYear,
                        isset($validated['discount_id']) ? (int) $validated['discount_id'] : null
                    );
                    $existingEnrollment->refresh();
                    $this->billingScheduleService->syncForEnrollment($existingEnrollment);

                    return [
                        'enrollment_id' => (int) $existingEnrollment->id,
                    ];
                }

                $enrollment = Enrollment::query()->create([
                    'student_id' => $student->id,
                    'email' => $validated['email'] ?? null,
                    'academic_year_id' => $activeAcademicYear->id,
                    'grade_level_id' => $resolvedGradeLevelId,
                    'section_id' => $selectedSection?->id,
                    'payment_term' => $paymentTerm,
                    'downpayment' => $downpayment,
                    'report_card_submitted' => (bool) ($validated['report_card_submitted'] ?? false),
                    'birth_certificate_submitted' => (bool) ($validated['birth_certificate_submitted'] ?? false),
                    'status' => 'for_cashier_payment',
                ]);

                $this->syncStudentDiscountForAcademicYear(
                    $student,
                    $activeAcademicYear,
                    isset($validated['discount_id']) ? (int) $validated['discount_id'] : null
                );
                $enrollment->refresh();
                $this->billingScheduleService->syncForEnrollment($enrollment);

                return [
                    'enrollment_id' => (int) $enrollment->id,
                ];
            });

            $storedEnrollmentId = (int) ($storeResult['enrollment_id'] ?? 0);
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        } catch (QueryException $exception) {
            if ($exception->getCode() === '23000') {
                return back()->with('error', 'Student already has an enrollment record for the active school year.');
            }

            throw $exception;
        }

        DashboardCacheService::bust();

        $assessmentPrintParameters = [
            'enrollment' => $storedEnrollmentId,
            'autoprint' => 1,
        ];

        return back()
            ->with('success', 'Enrollment saved.')
            ->with(
                'assessment_print_url',
                $storedEnrollmentId > 0
                    ? route('registrar.enrollment.assessment', $assessmentPrintParameters)
                    : null
            );
    }

    public function update(UpdateEnrollmentRequest $request, Enrollment $enrollment): RedirectResponse
    {
        if ($enrollment->status === 'enrolled') {
            return back()->with('error', 'Enrolled students can no longer be edited in the enrollment queue.');
        }

        $validated = $request->validated();
        $guardianContactNumber = $this->normalizeGuardianContactNumber(
            (string) ($validated['guardian_contact_number'] ?? $validated['emergency_contact'] ?? '')
        );

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

                    $this->ensureAccounts(
                        $student,
                        $this->normalizeNullableEmail($validated['student_personal_email'] ?? null),
                        $this->normalizeNullableEmail($validated['email'] ?? null)
                    );
                }

                $enrollment->update([
                    'grade_level_id' => $resolvedGradeLevelId,
                    'email' => $validated['email'] ?? null,
                    'section_id' => $selectedSection?->id,
                    'payment_term' => $paymentTerm,
                    'downpayment' => $downpayment,
                    'report_card_submitted' => (bool) ($validated['report_card_submitted'] ?? false),
                    'birth_certificate_submitted' => (bool) ($validated['birth_certificate_submitted'] ?? false),
                    'status' => 'for_cashier_payment',
                ]);

                if ($student) {
                    $academicYear = AcademicYear::query()->find((int) $enrollment->academic_year_id);
                    if ($academicYear) {
                        $this->syncStudentDiscountForAcademicYear(
                            $student,
                            $academicYear,
                            isset($validated['discount_id']) ? (int) $validated['discount_id'] : null
                        );
                        $enrollment->refresh();
                        $this->billingScheduleService->syncForEnrollment($enrollment);
                    }
                }
            });
        } catch (\RuntimeException $exception) {
            return back()->with('error', $exception->getMessage());
        }

        DashboardCacheService::bust();

        return back()->with('success', 'Enrollment updated.');
    }

    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        if ($enrollment->status === 'enrolled') {
            return back()->with('error', 'Cannot remove a fully enrolled student from the enrollment queue.');
        }

        $enrollment->delete();

        DashboardCacheService::bust();

        return back()->with('success', 'Enrollment removed from queue.');
    }

    private function normalizePaymentTerm(string $paymentTerm): string
    {
        return $paymentTerm === 'full' ? 'cash' : $paymentTerm;
    }

    private function syncStudentDiscountForAcademicYear(
        Student $student,
        AcademicYear $academicYear,
        ?int $discountId
    ): void {
        StudentDiscount::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->delete();

        if ($discountId === null || $discountId <= 0) {
            return;
        }

        StudentDiscount::query()->create([
            'student_id' => $student->id,
            'academic_year_id' => $academicYear->id,
            'discount_id' => $discountId,
        ]);
    }

    private function normalizeDownpayment(string $paymentTerm, mixed $downpayment): float
    {
        if ($paymentTerm === 'cash') {
            return 0;
        }

        return round((float) ($downpayment ?? 0), 2);
    }

    private function buildEnrollmentPolicySnapshot(Student $student, AcademicYear $activeAcademicYear): array
    {
        $latestEnrollment = Enrollment::query()
            ->where('student_id', $student->id)
            ->with([
                'academicYear:id,name,start_date,end_date',
                'gradeLevel:id,name,level_order',
            ])
            ->whereNotNull('grade_level_id')
            ->whereHas('academicYear')
            ->get()
            ->sortBy(function (Enrollment $enrollment) {
                return [
                    (string) ($enrollment->academicYear?->end_date ?? '0000-00-00'),
                    (string) ($enrollment->academicYear?->start_date ?? '0000-00-00'),
                    (int) $enrollment->id,
                ];
            })
            ->last();

        $defaultResponse = [
            'grade_prefill_mode' => 'none',
            'recommended_grade_level_id' => null,
            'grade_guardrail' => [
                'allowed_exact_grade_level_id' => null,
                'min_allowed_grade_level_order' => null,
                'max_allowed_grade_level_order' => null,
            ],
            'status_flags' => [
                'has_previous_year_conditional' => false,
                'has_previous_year_retained' => false,
                'has_older_unresolved_conditional' => false,
                'has_older_unresolved_retained' => false,
            ],
            'source_context' => null,
            'meta' => [
                'latest_enrollment_year_id' => null,
                'older_year_ids' => [],
            ],
        ];

        if (! $latestEnrollment || ! $latestEnrollment->academicYear || ! $latestEnrollment->gradeLevel) {
            return $defaultResponse;
        }

        $latestYearId = (int) $latestEnrollment->academic_year_id;
        $latestGradeLevelId = (int) $latestEnrollment->grade_level_id;
        $latestGradeLevelOrder = (int) $latestEnrollment->gradeLevel->level_order;
        $immediatePreviousYear = AcademicYear::query()
            ->whereDate('end_date', '<', $activeAcademicYear->start_date)
            ->orderByDesc('end_date')
            ->orderByDesc('start_date')
            ->first();
        $latestStatusRecord = PermanentRecord::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $latestYearId)
            ->latest('id')
            ->first();
        $latestStatus = $latestStatusRecord?->status;

        $isLatestFromPreviousYear = $immediatePreviousYear
            && (int) $immediatePreviousYear->id === $latestYearId;
        $olderYearIds = $immediatePreviousYear
            ? AcademicYear::query()
                ->whereDate('end_date', '<', $immediatePreviousYear->start_date)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all()
            : [];

        $response = $defaultResponse;
        $response['meta']['latest_enrollment_year_id'] = $latestYearId;
        $response['meta']['older_year_ids'] = $olderYearIds;
        $response['source_context'] = [
            'academic_year_id' => $latestYearId,
            'academic_year_name' => $latestEnrollment->academicYear->name,
            'status' => $latestStatus,
            'grade_level_id' => $latestGradeLevelId,
            'grade_level_label' => $latestEnrollment->gradeLevel->name,
        ];

        if ($isLatestFromPreviousYear) {
            $allowedGradeLevelId = null;
            $prefillMode = 'none';

            if (in_array($latestStatus, ['promoted', 'conditional', 'completed'], true)) {
                $nextGradeLevelId = GradeLevel::query()
                    ->where('level_order', '>', $latestGradeLevelOrder)
                    ->orderBy('level_order')
                    ->value('id');
                $allowedGradeLevelId = (int) ($nextGradeLevelId ?: $latestGradeLevelId);
                $prefillMode = 'next_grade';
            } elseif ($latestStatus === 'retained') {
                $allowedGradeLevelId = $latestGradeLevelId;
                $prefillMode = 'same_grade';
            } else {
                $nextGradeLevelId = GradeLevel::query()
                    ->where('level_order', '>', $latestGradeLevelOrder)
                    ->orderBy('level_order')
                    ->value('id');
                $allowedGradeLevelId = (int) ($nextGradeLevelId ?: $latestGradeLevelId);
                $prefillMode = 'next_grade';
            }

            $allowedOrder = (int) (GradeLevel::query()->whereKey($allowedGradeLevelId)->value('level_order') ?? $latestGradeLevelOrder);
            $response['grade_prefill_mode'] = $prefillMode;
            $response['recommended_grade_level_id'] = $allowedGradeLevelId;
            $response['grade_guardrail'] = [
                'allowed_exact_grade_level_id' => $allowedGradeLevelId,
                'min_allowed_grade_level_order' => $allowedOrder,
                'max_allowed_grade_level_order' => $allowedOrder,
            ];
        } else {
            $response['grade_prefill_mode'] = 'none';
            $response['recommended_grade_level_id'] = null;
            $response['grade_guardrail'] = [
                'allowed_exact_grade_level_id' => null,
                'min_allowed_grade_level_order' => $latestGradeLevelOrder,
                'max_allowed_grade_level_order' => null,
            ];
        }

        $allRecords = PermanentRecord::query()
            ->where('student_id', $student->id)
            ->get();

        if ($immediatePreviousYear) {
            $previousYearRecord = $allRecords
                ->where('academic_year_id', (int) $immediatePreviousYear->id)
                ->sortByDesc('id')
                ->first();
            $response['status_flags']['has_previous_year_conditional'] = $previousYearRecord?->status === 'conditional'
                && $previousYearRecord->conditional_resolved_at === null;
            $response['status_flags']['has_previous_year_retained'] = $previousYearRecord?->status === 'retained'
                && $previousYearRecord->retained_resolved_at === null;
        }

        $olderRecords = $allRecords->filter(function (PermanentRecord $record) use ($olderYearIds): bool {
            return in_array((int) $record->academic_year_id, $olderYearIds, true);
        });
        $response['status_flags']['has_older_unresolved_conditional'] = $olderRecords->contains(function (PermanentRecord $record): bool {
            return $record->status === 'conditional' && $record->conditional_resolved_at === null;
        });
        $response['status_flags']['has_older_unresolved_retained'] = $olderRecords->contains(function (PermanentRecord $record): bool {
            return $record->status === 'retained' && $record->retained_resolved_at === null;
        });

        return $response;
    }

    private function assertGradeSelectionWithinPolicy(int $selectedGradeLevelId, array $policy): void
    {
        $guardrail = $policy['grade_guardrail'] ?? [];
        $selectedOrder = (int) (GradeLevel::query()->whereKey($selectedGradeLevelId)->value('level_order') ?? 0);
        $allowedExact = $guardrail['allowed_exact_grade_level_id'] ?? null;
        $minOrder = $guardrail['min_allowed_grade_level_order'] ?? null;
        $maxOrder = $guardrail['max_allowed_grade_level_order'] ?? null;

        if ($allowedExact !== null && (int) $allowedExact !== $selectedGradeLevelId) {
            throw ValidationException::withMessages([
                'grade_level_id' => 'Selected grade level is not allowed for this returning student.',
            ]);
        }

        if ($minOrder !== null && $selectedOrder < (int) $minOrder) {
            throw ValidationException::withMessages([
                'grade_level_id' => 'Selected grade level is below the allowed baseline for this student.',
            ]);
        }

        if ($maxOrder !== null && $selectedOrder > (int) $maxOrder) {
            throw ValidationException::withMessages([
                'grade_level_id' => 'Selected grade level is above the allowed progression for this student.',
            ]);
        }
    }

    private function resolveOlderStatusesIfConfirmed(Student $student, array $validated, array $policy): void
    {
        $olderYearIds = collect($policy['meta']['older_year_ids'] ?? [])
            ->map(fn ($id) => (int) $id)
            ->all();
        $now = Carbon::now();

        if (($validated['resolve_older_conditional'] ?? false) === true && $olderYearIds !== []) {
            PermanentRecord::query()
                ->where('student_id', $student->id)
                ->whereIn('academic_year_id', $olderYearIds)
                ->where('status', 'conditional')
                ->whereNull('conditional_resolved_at')
                ->update([
                    'conditional_resolved_at' => $now,
                    'conditional_resolution_notes' => ($validated['conditional_resolution_notes'] ?? null) ?: 'Resolved during enrollment confirmation.',
                    'updated_at' => $now,
                ]);
        }

        if (($validated['resolve_older_retained'] ?? false) === true && $olderYearIds !== []) {
            PermanentRecord::query()
                ->where('student_id', $student->id)
                ->whereIn('academic_year_id', $olderYearIds)
                ->where('status', 'retained')
                ->whereNull('retained_resolved_at')
                ->update([
                    'retained_resolved_at' => $now,
                    'retained_resolution_notes' => ($validated['retained_resolution_notes'] ?? null) ?: 'Resolved during enrollment confirmation.',
                    'updated_at' => $now,
                ]);
        }
    }

    private function ensureAccounts(
        Student $student,
        ?string $studentPersonalEmail = null,
        ?string $guardianContactEmail = null
    ): void
    {
        $studentEmail = $this->buildStudentEmail($student);
        $studentUser = $student->user;

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
            'personal_email' => $studentPersonalEmail,
        ]);

        if ($student->user_id !== $studentUser->id) {
            $student->update(['user_id' => $studentUser->id]);
        }

        $existingParent = $student->parents()->first();
        $parentEmail = $existingParent?->email ?? $this->buildParentEmail($student);

        $parentUser = User::query()->firstOrCreate(
            ['email' => $parentEmail],
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

        $parentUser->update([
            'first_name' => 'Parent',
            'last_name' => $student->last_name,
            'name' => "Parent {$student->last_name}",
            'birthday' => self::DEFAULT_PARENT_BIRTHDAY,
            'role' => UserRole::PARENT->value,
            'is_active' => true,
            'access_expires_at' => null,
            'personal_email' => $guardianContactEmail,
        ]);

        $pivotQuery = DB::table('parent_student')
            ->where('parent_id', $parentUser->id)
            ->where('student_id', $student->id);

        if ($pivotQuery->exists()) {
            $pivotQuery->update([
                'updated_at' => now(),
            ]);

            return;
        }

        DB::table('parent_student')->insert([
            'parent_id' => $parentUser->id,
            'student_id' => $student->id,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function buildStudentEmail(Student $student): string
    {
        $normalizedSurname = $this->normalizeSurnameForEmail((string) $student->last_name);

        return "{$normalizedSurname}.{$student->lrn}@marriott.edu";
    }

    private function buildParentEmail(Student $student): string
    {
        $normalizedSurname = $this->normalizeSurnameForEmail((string) $student->last_name);
        $base = "parent.{$normalizedSurname}";
        $email = "{$base}@marriott.edu";
        $counter = 1;

        while (
            User::query()
                ->where('role', UserRole::PARENT->value)
                ->where('email', $email)
                ->exists()
        ) {
            $counter++;
            $email = "{$base}.{$counter}@marriott.edu";
        }

        return $email;
    }

    private function normalizeSurnameForEmail(string $surname): string
    {
        $normalizedSurname = strtolower((string) preg_replace('/[^a-z0-9]/i', '', $surname));

        if ($normalizedSurname === '') {
            return 'student';
        }

        return $normalizedSurname;
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

    private function normalizeNullableEmail(mixed $email): ?string
    {
        $normalizedEmail = Str::lower(trim((string) $email));

        return $normalizedEmail !== '' ? $normalizedEmail : null;
    }
}
