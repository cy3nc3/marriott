<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Http\Requests\Registrar\ResolveBatchPromotionReviewRequest;
use App\Models\AcademicYear;
use App\Models\PermanentRecord;
use App\Models\Setting;
use App\Models\Student;
use App\Services\Registrar\BatchPromotionService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class BatchPromotionController extends Controller
{
    public function index(BatchPromotionService $batchPromotionService): Response
    {
        $latestRun = json_decode((string) Setting::get('registrar_batch_promotion_last_run', '[]'), true);
        if (! is_array($latestRun)) {
            $latestRun = [];
        }

        $sourceYearIdFromRun = data_get($latestRun, 'source_year.id');
        $sourceYear = $sourceYearIdFromRun
            ? AcademicYear::query()->find($sourceYearIdFromRun)
            : AcademicYear::query()
                ->where('status', 'ongoing')
                ->first();

        if (! $sourceYear) {
            $sourceYear = AcademicYear::query()
                ->orderByDesc('start_date')
                ->first();
        }

        $targetYear = null;
        if ($sourceYear) {
            $targetYear = AcademicYear::query()
                ->where('start_date', '>', $sourceYear->start_date)
                ->orderBy('start_date')
                ->first();
        }

        $conditionalQueue = collect();
        if ($sourceYear) {
            $conditionalQueue = PermanentRecord::query()
                ->with([
                    'student:id,lrn,first_name,last_name',
                    'gradeLevel:id,name',
                    'academicYear:id,name',
                ])
                ->where('academic_year_id', $sourceYear->id)
                ->where('status', 'conditional')
                ->whereNull('conditional_resolved_at')
                ->orderBy('id')
                ->get()
                ->map(function (PermanentRecord $record): array {
                    return [
                        'permanent_record_id' => (int) $record->id,
                        'student_id' => (int) $record->student_id,
                        'student_name' => trim("{$record->student?->first_name} {$record->student?->last_name}"),
                        'lrn' => $record->student?->lrn,
                        'failed_subject_count' => (int) $record->failed_subject_count,
                        'school_year' => $record->academicYear?->name,
                        'grade_level' => $record->gradeLevel?->name,
                    ];
                })
                ->values();
        }

        $heldForReviewQueue = $sourceYear
            ? collect($batchPromotionService->buildHeldForReviewQueue($sourceYear))
            : collect();

        return Inertia::render('registrar/batch-promotion/index', [
            'run_summary' => [
                'run_at' => data_get($latestRun, 'run_at'),
                'processed_learners' => (int) data_get($latestRun, 'processed_learners', 0),
                'promoted' => (int) data_get($latestRun, 'promoted', 0),
                'conditional' => (int) data_get($latestRun, 'conditional', 0),
                'retained' => (int) data_get($latestRun, 'retained', 0),
                'completed' => (int) data_get($latestRun, 'completed', 0),
                'conflicts' => (int) data_get($latestRun, 'conflicts', 0),
                'grade_completeness_issue_count' => (int) data_get($latestRun, 'grade_completeness_issue_count', 0),
            ],
            'conditional_queue' => $conditionalQueue,
            'held_for_review_queue' => $heldForReviewQueue,
            'grade_completeness_issues' => data_get($latestRun, 'grade_completeness_issues', []),
            'source_year' => $sourceYear ? [
                'id' => (int) $sourceYear->id,
                'name' => $sourceYear->name,
            ] : null,
            'target_year' => $targetYear ? [
                'id' => (int) $targetYear->id,
                'name' => $targetYear->name,
            ] : null,
        ]);
    }

    public function resolveReviewCase(ResolveBatchPromotionReviewRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        $record = PermanentRecord::query()
            ->whereKey($validated['permanent_record_id'])
            ->where('status', 'conditional')
            ->whereNull('conditional_resolved_at')
            ->first();

        if (! $record) {
            return back()->with('error', 'Selected review case is no longer available.');
        }

        $record->update([
            'status' => $validated['decision'],
            'conditional_resolved_at' => now(),
            'conditional_resolution_notes' => $validated['note'],
            'remarks' => "Registrar decision: {$validated['decision']}",
        ]);

        $hasUnresolvedConditionals = PermanentRecord::query()
            ->where('student_id', $record->student_id)
            ->where('status', 'conditional')
            ->whereNull('conditional_resolved_at')
            ->exists();

        Student::query()
            ->whereKey($record->student_id)
            ->update([
                'is_for_remedial' => $hasUnresolvedConditionals,
            ]);

        return back()->with('success', 'Review case resolved.');
    }
}
