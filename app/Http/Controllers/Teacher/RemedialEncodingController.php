<?php

namespace App\Http\Controllers\Teacher;

use App\Http\Controllers\Controller;
use App\Http\Requests\Teacher\StoreRemedialEncodingRequest;
use App\Models\AcademicYear;
use App\Models\PermanentRecord;
use App\Models\RemedialCaseSubject;
use App\Models\RemedialRecord;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class RemedialEncodingController extends Controller
{
    public function index(Request $request): Response
    {
        $teacherId = (int) auth()->id();
        $academicYears = AcademicYear::query()
            ->orderByDesc('start_date')
            ->get(['id', 'name', 'status']);

        $selectedAcademicYearId = (int) ($request->input('academic_year_id')
            ?: AcademicYear::query()->where('status', 'ongoing')->value('id')
            ?: $academicYears->first()?->id);

        $caseSubjects = RemedialCaseSubject::query()
            ->with([
                'student:id,lrn,first_name,last_name',
                'subject:id,subject_name',
                'academicYear:id,name',
                'remedialCase:id,status',
            ])
            ->where('assigned_teacher_id', $teacherId)
            ->where('academic_year_id', $selectedAcademicYearId)
            ->orderBy('student_id')
            ->orderBy('subject_id')
            ->get();

        $records = RemedialRecord::query()
            ->whereIn('student_id', $caseSubjects->pluck('student_id')->unique()->values())
            ->whereIn('subject_id', $caseSubjects->pluck('subject_id')->unique()->values())
            ->where('academic_year_id', $selectedAcademicYearId)
            ->get()
            ->keyBy(fn (RemedialRecord $record): string => "{$record->student_id}-{$record->subject_id}-{$record->academic_year_id}");

        $rows = $caseSubjects
            ->map(function (RemedialCaseSubject $caseSubject) use ($records): array {
                $record = $records->get("{$caseSubject->student_id}-{$caseSubject->subject_id}-{$caseSubject->academic_year_id}");
                $caseStatus = (string) ($caseSubject->remedialCase?->status ?? 'for_cashier_payment');
                $isPaid = $caseStatus === 'paid';

                return [
                    'case_subject_id' => (int) $caseSubject->id,
                    'student_name' => trim("{$caseSubject->student?->first_name} {$caseSubject->student?->last_name}"),
                    'lrn' => (string) ($caseSubject->student?->lrn ?? ''),
                    'subject_name' => (string) ($caseSubject->subject?->subject_name ?? 'Subject'),
                    'school_year' => (string) ($caseSubject->academicYear?->name ?? 'N/A'),
                    'final_rating' => $caseSubject->final_rating !== null ? (float) $caseSubject->final_rating : null,
                    'remedial_class_mark' => $record?->remedial_class_mark !== null ? (float) $record->remedial_class_mark : null,
                    'recomputed_final_grade' => $record?->recomputed_final_grade !== null ? (float) $record->recomputed_final_grade : null,
                    'case_status' => $caseStatus,
                    'can_encode' => $isPaid,
                    'status' => $record?->status ?? ($isPaid ? 'for_encoding' : 'for_cashier_payment'),
                ];
            })
            ->values();

        return Inertia::render('teacher/remedial-encoding/index', [
            'academic_years' => $academicYears->map(fn (AcademicYear $year): array => [
                'id' => (int) $year->id,
                'name' => (string) $year->name,
                'status' => (string) $year->status,
            ])->values(),
            'selected_academic_year_id' => $selectedAcademicYearId > 0 ? $selectedAcademicYearId : null,
            'rows' => $rows,
        ]);
    }

    public function store(StoreRemedialEncodingRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        /** @var RemedialCaseSubject|null $caseSubject */
        $caseSubject = RemedialCaseSubject::query()
            ->with('remedialCase:id,status')
            ->find((int) $validated['case_subject_id']);

        if (! $caseSubject) {
            return back()->with('error', 'Remedial assignment not found.');
        }

        if ((int) $caseSubject->assigned_teacher_id !== (int) auth()->id()) {
            abort(403);
        }

        if (($caseSubject->remedialCase?->status ?? 'for_cashier_payment') !== 'paid') {
            return back()->with('error', 'Cashier payment is required before remedial grade encoding.');
        }

        if ($caseSubject->final_rating === null) {
            return back()->with('error', 'Final rating for this remedial subject is missing.');
        }

        $finalRating = (float) $caseSubject->final_rating;
        $remedialMark = (float) $validated['remedial_class_mark'];
        $recomputedGrade = round(($finalRating + $remedialMark) / 2, 2);
        $status = $recomputedGrade >= 75 ? 'passed' : 'failed';

        RemedialRecord::query()->updateOrCreate(
            [
                'student_id' => (int) $caseSubject->student_id,
                'subject_id' => (int) $caseSubject->subject_id,
                'academic_year_id' => (int) $caseSubject->academic_year_id,
            ],
            [
                'final_rating' => $finalRating,
                'remedial_class_mark' => $remedialMark,
                'recomputed_final_grade' => $recomputedGrade,
                'status' => $status,
            ]
        );

        $this->resolveConditionalRecordIfCompleted(
            (int) $caseSubject->student_id,
            (int) $caseSubject->academic_year_id
        );
        $this->refreshStudentRemedialFlag((int) $caseSubject->student_id);

        return back()->with('success', 'Remedial grade encoded successfully.');
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

        $subjectIds = RemedialCaseSubject::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->pluck('subject_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values();

        if ($subjectIds->isEmpty()) {
            return;
        }

        $passedCount = RemedialRecord::query()
            ->where('student_id', $studentId)
            ->where('academic_year_id', $academicYearId)
            ->whereIn('subject_id', $subjectIds)
            ->where('status', 'passed')
            ->distinct('subject_id')
            ->count('subject_id');

        if ($passedCount !== $subjectIds->count()) {
            return;
        }

        $conditionalRecord->update([
            'status' => 'promoted',
            'conditional_resolved_at' => now(),
            'conditional_resolution_notes' => 'Resolved through remedial completion.',
            'remarks' => 'Conditional status resolved after remedial completion.',
        ]);
    }

    private function refreshStudentRemedialFlag(int $studentId): void
    {
        $hasUnresolvedConditionals = PermanentRecord::query()
            ->where('student_id', $studentId)
            ->where('status', 'conditional')
            ->whereNull('conditional_resolved_at')
            ->exists();

        $hasFailedRemedialRecords = RemedialRecord::query()
            ->where('student_id', $studentId)
            ->where('status', 'failed')
            ->exists();

        Student::query()
            ->whereKey($studentId)
            ->update([
                'is_for_remedial' => $hasUnresolvedConditionals || $hasFailedRemedialRecords,
            ]);
    }
}
