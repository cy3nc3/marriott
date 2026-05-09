<?php

namespace App\Http\Controllers\Registrar;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\Setting;
use App\Services\Registrar\BatchPromotionService;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Support\Collection;

class BatchPromotionController extends Controller
{
    public function index(BatchPromotionService $batchPromotionService): Response
    {
        $latestRun = json_decode((string) Setting::get('registrar_batch_promotion_last_run', '[]'), true);
        if (! is_array($latestRun)) {
            $latestRun = [];
        }

        $academicYears = AcademicYear::query()
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get(['id', 'name', 'status']);

        $selectedYearId = (int) request()->integer('academic_year_id');
        $selectedYear = $selectedYearId > 0
            ? $academicYears->firstWhere('id', $selectedYearId)
            : null;

        if (! $selectedYear instanceof AcademicYear) {
            $selectedYear = $academicYears->firstWhere('status', 'completed')
                ?? $academicYears->first();
        }

        $statusBreakdown = collect([
            'passed' => collect(),
            'conditional' => collect(),
            'retained' => collect(),
        ]);

        if ($selectedYear instanceof AcademicYear) {
            $rawRecords = collect($batchPromotionService->buildPromotionStatusBreakdown($selectedYear));
            $statusBreakdown = collect([
                'passed' => $rawRecords->where('promotion_group', 'passed')->values(),
                'conditional' => $rawRecords->where('promotion_group', 'conditional')->values(),
                'retained' => $rawRecords->where('promotion_group', 'retained')->values(),
            ]);
        }

        $runProcessedLearners = (int) data_get($latestRun, 'processed_learners', 0);
        $totalLearners = $statusBreakdown
            ->reduce(fn (int $carry, Collection $rows): int => $carry + $rows->count(), 0);

        return Inertia::render('registrar/batch-promotion/index', [
            'run_summary' => [
                'run_at' => data_get($latestRun, 'run_at'),
                'processed_learners' => $runProcessedLearners > 0 ? $runProcessedLearners : $totalLearners,
                'passed' => $statusBreakdown->get('passed', collect())->count(),
                'conditional' => $statusBreakdown->get('conditional', collect())->count(),
                'retained' => $statusBreakdown->get('retained', collect())->count(),
            ],
            'school_years' => $academicYears->map(fn (AcademicYear $year): array => [
                'id' => (int) $year->id,
                'name' => $year->name,
                'status' => $year->status,
            ])->values(),
            'selected_year' => $selectedYear ? [
                'id' => (int) $selectedYear->id,
                'name' => $selectedYear->name,
                'status' => $selectedYear->status,
            ] : null,
            'status_breakdown' => [
                'passed' => $statusBreakdown->get('passed', collect())->values(),
                'conditional' => $statusBreakdown->get('conditional', collect())->values(),
                'retained' => $statusBreakdown->get('retained', collect())->values(),
            ],
        ]);
    }

}
