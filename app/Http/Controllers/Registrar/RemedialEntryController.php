<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\StoreRemedialIntakeRequest;
use App\Models\AcademicYear;
use App\Models\Enrollment;
use App\Models\FinalGrade;
use App\Models\GradeLevel;
use App\Models\LedgerEntry;
use App\Models\PermanentRecord;
use App\Models\RemedialCase;
use App\Models\RemedialRecord;
use App\Models\RemedialSubjectFee;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Inertia\Inertia;
use Inertia\Response;

class RemedialEntryController extends Controller
{
    public function index(Request $request): Response
    {
        $academicYears = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['id', 'name']);

        $gradeLevels = GradeLevel::query()
            ->orderBy('level_order')
            ->get(['id', 'name']);

        $selectedAcademicYearId = (int) ($request->input('academic_year_id')
            ?: AcademicYear::query()->where('status', 'ongoing')->value('id')
            ?: $academicYears->first()?->id);

        $selectedGradeLevelId = $request->input('grade_level_id')
            ? (int) $request->input('grade_level_id')
            : null;

        $search = trim((string) $request->input('search', ''));

        $studentList = $this->resolveStudentLookup(
            $selectedAcademicYearId,
            $selectedGradeLevelId,
            $search,
        );

        $selectedStudentId = (int) ($request->input('student_id') ?: $studentList->first()['id'] ?? 0);

        $selectedStudent = $selectedStudentId
            ? Student::query()->find($selectedStudentId)
            : null;

        $selectedEnrollment = $selectedStudentId
            ? Enrollment::query()
                ->with(['gradeLevel:id,name', 'section:id,name'])
                ->where('student_id', $selectedStudentId)
                ->where('academic_year_id', $selectedAcademicYearId)
                ->first()
            : null;

        $effectiveGradeLevelId = $selectedGradeLevelId ?: $selectedEnrollment?->grade_level_id;

        $existingRecords = collect();
        if ($selectedStudentId) {
            $existingRecords = RemedialRecord::query()
                ->with('subject:id,subject_name')
                ->where('student_id', $selectedStudentId)
                ->where('academic_year_id', $selectedAcademicYearId)
                ->get()
                ->keyBy('subject_id');
        }

        $failedGradeMap = collect();
        if ($selectedEnrollment) {
            $failedGradeMap = $this->resolveAnnualFailedGradeMap($selectedEnrollment);
        }

        $remedialRows = collect();
        if ($effectiveGradeLevelId) {
            $subjects = Subject::query()
                ->where('grade_level_id', $effectiveGradeLevelId)
                ->orderBy('subject_name')
                ->get(['id', 'subject_name']);

            $remedialRows = $subjects
                ->map(function (Subject $subject) use ($existingRecords, $failedGradeMap) {
                    $record = $existingRecords->get($subject->id);

                    $finalRating = $record
                        ? (float) $record->final_rating
                        : ($failedGradeMap[$subject->id] ?? null);

                    $remedialMark = $record ? (float) $record->remedial_class_mark : null;
                    $recomputed = $record
                        ? (float) $record->recomputed_final_grade
                        : ($finalRating !== null && $remedialMark !== null
                            ? round(($finalRating + $remedialMark) / 2, 2)
                            : null);

                    return [
                        'record_id' => $record?->id,
                        'subject_id' => $subject->id,
                        'subject_name' => $subject->subject_name,
                        'final_rating' => $finalRating,
                        'remedial_class_mark' => $remedialMark,
                        'recomputed_final_grade' => $recomputed,
                        'status' => $record?->status
                            ?? ($recomputed !== null
                                ? ($recomputed >= 75 ? 'passed' : 'failed')
                                : 'for_encoding'),
                    ];
                })
                ->filter(function (array $row) {
                    if ($row['record_id']) {
                        return true;
                    }

                    return $row['final_rating'] !== null && $row['final_rating'] < 75;
                })
                ->values();
        }

        $recentEncodings = RemedialRecord::query()
            ->with([
                'student:id,lrn,first_name,last_name',
                'academicYear:id,name',
            ])
            ->latest('updated_at')
            ->get()
            ->groupBy(fn (RemedialRecord $record) => "{$record->student_id}-{$record->academic_year_id}")
            ->map(function ($group) {
                /** @var RemedialRecord $latest */
                $latest = $group->sortByDesc('updated_at')->first();
                $hasFailed = $group->contains(fn (RemedialRecord $record) => $record->status === 'failed');

                return [
                    'student_name' => trim("{$latest->student?->first_name} {$latest->student?->last_name}"),
                    'lrn' => $latest->student?->lrn,
                    'school_year' => $latest->academicYear?->name,
                    'updated_at' => $latest->updated_at?->toDateString(),
                    'status' => $hasFailed ? 'Draft' : 'Submitted',
                ];
            })
            ->sortByDesc('updated_at')
            ->take(10)
            ->values();

        $overallResult = 'No Remedial Subjects';
        if ($remedialRows->count() > 0) {
            $overallResult = $remedialRows->every(fn ($row) => $row['status'] === 'passed')
                ? 'Passed'
                : 'For Encoding';
        }

        $selectedRemedialCase = null;
        if ($selectedStudentId > 0 && $selectedAcademicYearId > 0) {
            $selectedRemedialCase = RemedialCase::query()
                ->where('student_id', $selectedStudentId)
                ->where('academic_year_id', $selectedAcademicYearId)
                ->first();
        }

        $failedSubjectIds = $failedGradeMap
            ->keys()
            ->map(fn ($subjectId) => (int) $subjectId)
            ->values();
        $failedSubjectCount = $failedSubjectIds->count();
        $feeSummary = $this->resolveRemedialFeeSummary(
            $selectedAcademicYearId,
            $failedSubjectIds
        );

        return Inertia::render('registrar/remedial-entry/index', [
            'academic_years' => $academicYears,
            'grade_levels' => $gradeLevels,
            'students' => $studentList,
            'selected_student' => $selectedStudent ? [
                'id' => $selectedStudent->id,
                'name' => trim("{$selectedStudent->first_name} {$selectedStudent->last_name}"),
                'lrn' => $selectedStudent->lrn,
                'grade_and_section' => $selectedEnrollment?->gradeLevel?->name && $selectedEnrollment?->section?->name
                    ? "{$selectedEnrollment->gradeLevel->name} - {$selectedEnrollment->section->name}"
                    : 'Unassigned',
                'overall_result' => $overallResult,
            ] : null,
            'remedial_case' => $selectedRemedialCase ? [
                'id' => (int) $selectedRemedialCase->id,
                'failed_subject_count' => (int) $selectedRemedialCase->failed_subject_count,
                'fee_per_subject' => (float) $selectedRemedialCase->fee_per_subject,
                'total_amount' => (float) $selectedRemedialCase->total_amount,
                'amount_paid' => (float) $selectedRemedialCase->amount_paid,
                'balance' => round(
                    max((float) $selectedRemedialCase->total_amount - (float) $selectedRemedialCase->amount_paid, 0),
                    2
                ),
                'status' => $selectedRemedialCase->status,
                'paid_at' => $selectedRemedialCase->paid_at?->toDateTimeString(),
            ] : null,
            'intake_meta' => [
                'failed_subject_count' => $failedSubjectCount,
                'fee_per_subject' => $feeSummary['fee_per_subject'],
                'estimated_total' => $feeSummary['total_amount'],
            ],
            'remedial_rows' => $remedialRows,
            'recent_encodings' => $recentEncodings,
            'filters' => [
                'academic_year_id' => $selectedAcademicYearId ?: null,
                'grade_level_id' => $selectedGradeLevelId,
                'search' => $search ?: null,
                'student_id' => $selectedStudentId ?: null,
            ],
        ]);
    }

    public function studentSuggestions(Request $request): JsonResponse
    {
        $search = trim((string) $request->input('search', ''));
        $academicYearId = (int) $request->input('academic_year_id');
        $gradeLevelId = $request->filled('grade_level_id')
            ? (int) $request->input('grade_level_id')
            : null;

        if ($search === '') {
            return response()->json([
                'students' => [],
            ]);
        }

        return response()->json([
            'students' => $this->resolveStudentLookup(
                $academicYearId,
                $gradeLevelId,
                $search,
                5,
            ),
        ]);
    }

    public function storeIntake(StoreRemedialIntakeRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $enrollment = Enrollment::query()
            ->where('student_id', $validated['student_id'])
            ->where('academic_year_id', $validated['academic_year_id'])
            ->first();

        if (! $enrollment) {
            return back()->with('error', 'Student has no enrollment context for the selected school year.');
        }

        $failedGradeMap = $this->resolveAnnualFailedGradeMap($enrollment);
        $failedSubjectIds = $failedGradeMap
            ->keys()
            ->map(fn ($subjectId) => (int) $subjectId)
            ->values();
        $failedSubjectCount = $failedSubjectIds->count();

        if ($failedSubjectCount <= 0) {
            return back()->with('error', 'No failed subjects found for remedial intake.');
        }

        $feeSummary = $this->resolveRemedialFeeSummary(
            (int) $validated['academic_year_id'],
            $failedSubjectIds
        );
        $feePerSubject = $feeSummary['fee_per_subject'];
        $totalAmount = $feeSummary['total_amount'];

        $remedialCase = RemedialCase::query()->firstOrNew([
            'student_id' => (int) $validated['student_id'],
            'academic_year_id' => (int) $validated['academic_year_id'],
        ]);

        $existingPaidAmount = round((float) ($remedialCase->amount_paid ?? 0), 2);
        $nextStatus = $this->resolveRemedialCaseStatus($existingPaidAmount, $totalAmount);

        $remedialCase->fill([
            'created_by' => $remedialCase->exists ? $remedialCase->created_by : auth()->id(),
            'failed_subject_count' => $failedSubjectCount,
            'fee_per_subject' => $feePerSubject,
            'total_amount' => $totalAmount,
            'amount_paid' => min($existingPaidAmount, $totalAmount),
            'status' => $nextStatus,
            'paid_at' => $nextStatus === 'paid' ? now() : null,
            'notes' => 'Created from registrar remedial entry.',
        ]);
        $remedialCase->save();

        $this->syncRemedialLedgerDebit(
            studentId: (int) $validated['student_id'],
            academicYearId: (int) $validated['academic_year_id'],
            remedialCaseId: (int) $remedialCase->id,
            amount: $totalAmount
        );

        Student::query()
            ->whereKey((int) $validated['student_id'])
            ->update([
                'is_for_remedial' => true,
            ]);

        return back()->with('success', 'Remedial intake created and queued for cashier payment.');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'academic_year_id' => 'required|exists:academic_years,id',
            'student_id' => 'required|exists:students,id',
            'save_mode' => 'required|string|in:draft,submitted',
            'records' => 'required|array|min:1',
            'records.*.subject_id' => 'required|exists:subjects,id',
            'records.*.final_rating' => 'nullable|numeric|min:0|max:100',
            'records.*.remedial_class_mark' => 'nullable|numeric|min:0|max:100',
        ]);

        if ($validated['save_mode'] === 'submitted') {
            $remedialCase = RemedialCase::query()
                ->where('student_id', $validated['student_id'])
                ->where('academic_year_id', $validated['academic_year_id'])
                ->first();

            if (! $remedialCase) {
                return back()->with('error', 'Create remedial intake first before submitting results.');
            }

            if ($remedialCase->status !== 'paid') {
                return back()->with('error', 'Remedial intake must be fully paid before submitting results.');
            }
        }

        $written = 0;
        $hasFailed = false;

        foreach ($validated['records'] as $record) {
            $finalRating = array_key_exists('final_rating', $record) && $record['final_rating'] !== null
                ? (float) $record['final_rating']
                : null;

            $remedialMark = array_key_exists('remedial_class_mark', $record) && $record['remedial_class_mark'] !== null
                ? (float) $record['remedial_class_mark']
                : null;

            if ($finalRating === null || $remedialMark === null) {
                continue;
            }

            $recomputed = round(($finalRating + $remedialMark) / 2, 2);
            $status = $recomputed >= 75 ? 'passed' : 'failed';

            RemedialRecord::query()->updateOrCreate(
                [
                    'student_id' => $validated['student_id'],
                    'subject_id' => $record['subject_id'],
                    'academic_year_id' => $validated['academic_year_id'],
                ],
                [
                    'final_rating' => $finalRating,
                    'remedial_class_mark' => $remedialMark,
                    'recomputed_final_grade' => $recomputed,
                    'status' => $status,
                ]
            );

            $written++;

            if ($status === 'failed') {
                $hasFailed = true;
            }
        }

        if ($written === 0) {
            return back()->with('error', 'No remedial rows were saved. Please enter at least one complete row.');
        }

        if ($validated['save_mode'] === 'submitted') {
            $this->resolveConditionalRecordIfCompleted(
                (int) $validated['student_id'],
                (int) $validated['academic_year_id']
            );
        }

        $hasUnresolvedConditionals = PermanentRecord::query()
            ->where('student_id', $validated['student_id'])
            ->where('status', 'conditional')
            ->whereNull('conditional_resolved_at')
            ->exists();

        $hasFailedRemedialRecords = RemedialRecord::query()
            ->where('student_id', $validated['student_id'])
            ->where('status', 'failed')
            ->exists();

        Student::query()
            ->whereKey($validated['student_id'])
            ->update([
                'is_for_remedial' => $validated['save_mode'] === 'draft'
                    || $hasFailed
                    || $hasFailedRemedialRecords
                    || $hasUnresolvedConditionals,
            ]);

        $message = $validated['save_mode'] === 'submitted'
            ? 'Remedial results submitted.'
            : 'Remedial draft saved.';

        return back()->with('success', $message);
    }

    /**
     * @return Collection<int, float>
     */
    private function resolveAnnualFailedGradeMap(Enrollment $enrollment): Collection
    {
        $quarterGrades = FinalGrade::query()
            ->with('subjectAssignment.teacherSubject.subject:id,subject_name')
            ->where('enrollment_id', $enrollment->id)
            ->whereIn('quarter', ['1', '2', '3', '4'])
            ->get();

        return $quarterGrades
            ->groupBy(function (FinalGrade $grade) {
                return $grade->subjectAssignment?->teacherSubject?->subject?->id;
            })
            ->mapWithKeys(function (Collection $grades, $subjectId): array {
                if (! $subjectId) {
                    return [];
                }

                $gradeByQuarter = $grades
                    ->keyBy(function (FinalGrade $grade): string {
                        return (string) $grade->quarter;
                    });

                $quarters = collect(['1', '2', '3', '4']);
                $hasAllQuarters = $quarters->every(function (string $quarter) use ($gradeByQuarter): bool {
                    return $gradeByQuarter->has($quarter);
                });

                if (! $hasAllQuarters) {
                    return [];
                }

                $allLocked = $quarters->every(function (string $quarter) use ($gradeByQuarter): bool {
                    return (bool) $gradeByQuarter->get($quarter)?->is_locked;
                });

                if (! $allLocked) {
                    return [];
                }

                $annualGrade = round((float) $quarters
                    ->map(function (string $quarter) use ($gradeByQuarter): float {
                        return (float) $gradeByQuarter->get($quarter)->grade;
                    })
                    ->avg(), 2);

                if ($annualGrade >= 75) {
                    return [];
                }

                return [(int) $subjectId => $annualGrade];
            });
    }

    private function resolveConditionalRecordIfCompleted(int $studentId, int $academicYearId): void
    {
        $conditionalRecord = PermanentRecord::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('status', 'conditional')
            ->whereNull('conditional_resolved_at')
            ->first();

        if (! $conditionalRecord) {
            return;
        }

        $enrollment = Enrollment::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->first();

        if (! $enrollment) {
            return;
        }

        $failedSubjectIds = $this->resolveAnnualFailedGradeMap($enrollment)
            ->keys()
            ->map(fn ($id) => (int) $id)
            ->values();

        if ($failedSubjectIds->isEmpty()) {
            return;
        }

        $passedCount = RemedialRecord::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('subject_id', $failedSubjectIds)
            ->where('status', 'passed')
            ->distinct('subject_id')
            ->count('subject_id');

        if ($passedCount !== $failedSubjectIds->count()) {
            return;
        }

        $conditionalRecord->update([
            'status' => 'promoted',
            'conditional_resolved_at' => now(),
            'conditional_resolution_notes' => 'Resolved through remedial completion.',
            'remarks' => 'Conditional status resolved after remedial completion.',
        ]);
    }

    /**
     * @param  Collection<int, int>  $subjectIds
     * @return array{fee_per_subject: float, total_amount: float}
     */
    private function resolveRemedialFeeSummary(int $academicYearId, Collection $subjectIds): array
    {
        $defaultFeePerSubject = $this->resolveDefaultRemedialFeePerSubject();
        $normalizedSubjectIds = $subjectIds
            ->map(fn ($subjectId) => (int) $subjectId)
            ->filter(fn (int $subjectId) => $subjectId > 0)
            ->values();

        if ($normalizedSubjectIds->isEmpty()) {
            return [
                'fee_per_subject' => $defaultFeePerSubject,
                'total_amount' => 0.0,
            ];
        }

        $customFeeMap = collect();
        if ($academicYearId > 0) {
            $customFeeMap = RemedialSubjectFee::query()
                ->where('academic_year_id', $academicYearId)
                ->whereIn('subject_id', $normalizedSubjectIds)
                ->pluck('amount', 'subject_id')
                ->map(fn ($amount) => (float) $amount);
        }

        $totalAmount = round((float) $normalizedSubjectIds->sum(function (int $subjectId) use (
            $customFeeMap,
            $defaultFeePerSubject
        ) {
            return (float) ($customFeeMap->get($subjectId) ?? $defaultFeePerSubject);
        }), 2);

        $subjectCount = $normalizedSubjectIds->count();
        $averageFeePerSubject = $subjectCount > 0
            ? round($totalAmount / $subjectCount, 2)
            : $defaultFeePerSubject;

        return [
            'fee_per_subject' => $averageFeePerSubject,
            'total_amount' => $totalAmount,
        ];
    }

    private function resolveDefaultRemedialFeePerSubject(): float
    {
        $rawValue = Setting::get('finance_remedial_fee_per_subject', '500');
        $parsedValue = (float) $rawValue;

        if ($parsedValue < 0) {
            return 0;
        }

        return round($parsedValue, 2);
    }

    private function resolveRemedialCaseStatus(float $amountPaid, float $totalAmount): string
    {
        if ($totalAmount <= 0) {
            return 'paid';
        }

        if ($amountPaid <= 0) {
            return 'for_cashier_payment';
        }

        if ($amountPaid >= $totalAmount) {
            return 'paid';
        }

        return 'partial_payment';
    }

    private function syncRemedialLedgerDebit(
        int $studentId,
        int $academicYearId,
        int $remedialCaseId,
        float $amount
    ): void {
        $description = "Remedial Intake Fee (Case {$remedialCaseId})";
        $previousDebit = (float) (LedgerEntry::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->where('description', $description)
            ->value('debit') ?? 0.0);

        if ($previousDebit > 0) {
            if (round($previousDebit, 2) === round($amount, 2)) {
                return;
            }

            LedgerEntry::query()
                ->where('student_id', $studentId)
                ->where('academic_year_id', $academicYearId)
                ->where('description', $description)
                ->delete();
        }

        $previousRunningBalance = (float) (LedgerEntry::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->latest('date')
            ->latest('id')
            ->value('running_balance') ?? 0.0);

        LedgerEntry::query()->create([
            'student_id' => $studentId,
            'academic_year_id' => $academicYearId,
            'date' => now()->toDateString(),
            'description' => $description,
            'debit' => $amount,
            'credit' => null,
            'running_balance' => round($previousRunningBalance + $amount, 2),
            'reference_id' => null,
        ]);
    }

    private function resolveStudentLookup(
        int $academicYearId,
        ?int $gradeLevelId,
        string $search,
        ?int $limit = null
    ): Collection {
        $normalizedSearch = strtolower($search);

        $query = Student::query()
            ->with([
                'enrollments' => function ($enrollmentQuery) use ($academicYearId): void {
                    $enrollmentQuery
                        ->where('academic_year_id', $academicYearId)
                        ->with(['gradeLevel:id,name', 'section:id,name']);
                },
            ])
            ->where(function (Builder $studentQuery) use ($academicYearId): void {
                $studentQuery
                    ->where('is_for_remedial', true)
                    ->orWhereHas('remedialRecords', function (Builder $recordQuery) use ($academicYearId): void {
                        $recordQuery->where('academic_year_id', $academicYearId);
                    });
            })
            ->when($gradeLevelId, function (Builder $studentQuery) use ($gradeLevelId, $academicYearId): void {
                $studentQuery->whereHas(
                    'enrollments',
                    function (Builder $enrollmentQuery) use ($gradeLevelId, $academicYearId): void {
                        $enrollmentQuery
                            ->where('academic_year_id', $academicYearId)
                            ->where('grade_level_id', $gradeLevelId);
                    }
                );
            })
            ->when($search !== '', function (Builder $studentQuery) use ($normalizedSearch): void {
                $studentQuery->where(function (Builder $searchQuery) use ($normalizedSearch): void {
                    $searchQuery
                        ->whereRaw('LOWER(lrn) LIKE ?', ["%{$normalizedSearch}%"])
                        ->orWhereRaw('LOWER(first_name) LIKE ?', ["%{$normalizedSearch}%"])
                        ->orWhereRaw('LOWER(last_name) LIKE ?', ["%{$normalizedSearch}%"]);
                });
            })
            ->orderBy('last_name')
            ->orderBy('first_name');

        if ($limit !== null) {
            $query->limit($limit);
        }

        return $query
            ->get()
            ->map(function (Student $student): array {
                $enrollment = $student->enrollments->first();

                return [
                    'id' => $student->id,
                    'lrn' => $student->lrn,
                    'name' => trim("{$student->first_name} {$student->last_name}"),
                    'grade_level_id' => $enrollment?->grade_level_id,
                    'grade_and_section' => $enrollment?->gradeLevel?->name && $enrollment?->section?->name
                        ? "{$enrollment->gradeLevel->name} - {$enrollment->section->name}"
                        : 'Unassigned',
                ];
            })
            ->values();
    }
}
