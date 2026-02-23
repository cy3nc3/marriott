<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InitializeAcademicYearRequest;
use App\Http\Requests\Admin\UpdateAcademicYearDatesRequest;
use App\Models\AcademicYear;
use App\Models\GradeSubmission;
use App\Models\Setting;
use App\Services\AuditLogService;
use App\Services\DashboardCacheService;
use App\Services\Registrar\BatchPromotionService;
use App\Services\SystemBackupService;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class SchoolYearController extends Controller
{
    public function index(): Response
    {
        // Priority: 1. Ongoing year, 2. First Upcoming year, 3. Most recent Completed year
        $currentYear = AcademicYear::where('status', 'ongoing')->first()
            ?? AcademicYear::where('status', 'upcoming')->orderBy('start_date', 'asc')->first()
            ?? AcademicYear::orderBy('end_date', 'desc')->first();

        $nextYearName = null;

        // Find the absolute latest year record to determine what the 'next' one should be named
        $latestRecord = AcademicYear::orderBy('end_date', 'desc')->first();

        if ($latestRecord) {
            $years = explode('-', $latestRecord->name);
            if (count($years) === 2) {
                $nextStart = (int) $years[0] + 1;
                $nextEnd = (int) $years[1] + 1;
                $nextYearName = "{$nextStart}-{$nextEnd}";
            }
        }

        return Inertia::render('admin/academic-controls/index', [
            'currentYear' => $currentYear,
            'nextYearName' => $nextYearName,
            'allYears' => AcademicYear::orderBy('start_date', 'desc')->get(),
        ]);
    }

    public function updateDates(
        UpdateAcademicYearDatesRequest $request,
        AcademicYear $academicYear,
        AuditLogService $auditLogService,
    ): RedirectResponse {
        if ($academicYear->status === 'completed') {
            return back()->with('error', 'Completed school years can no longer be edited.');
        }

        $validated = $request->validated();
        $oldValues = $academicYear->only(['start_date', 'end_date']);

        $academicYear->update([
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
        ]);

        $auditLogService->log('academic_year.dates_updated', $academicYear, $oldValues, $academicYear->only([
            'id',
            'name',
            'start_date',
            'end_date',
        ]));

        DashboardCacheService::bust();

        return back()->with('success', 'School year dates updated successfully.');
    }

    public function initializeNext(
        InitializeAcademicYearRequest $request,
        AuditLogService $auditLogService,
    ): RedirectResponse {
        $validated = $request->validated();

        $academicYear = AcademicYear::query()->create([
            'name' => $validated['name'],
            'start_date' => $validated['start_date'],
            'end_date' => $validated['end_date'],
            'status' => 'upcoming',
            'current_quarter' => '1',
        ]);

        $auditLogService->log('academic_year.initialized', $academicYear, null, $academicYear->only([
            'id',
            'name',
            'status',
            'start_date',
            'end_date',
            'current_quarter',
        ]));

        DashboardCacheService::bust();

        return back()->with('success', 'Next school year initialized.');
    }

    public function simulateOpening(
        AcademicYear $academicYear,
        AuditLogService $auditLogService,
    ): RedirectResponse {
        if ($this->isSimulationBlocked()) {
            return back()->with('error', 'Simulation actions are disabled in production.');
        }

        if ($academicYear->status === 'completed') {
            return back()->with('error', 'Completed school years cannot be reopened through simulation.');
        }

        $otherOngoingYearExists = AcademicYear::query()
            ->where('status', 'ongoing')
            ->whereKeyNot($academicYear->id)
            ->exists();

        if ($otherOngoingYearExists) {
            return back()->with('error', 'Another school year is already marked as ongoing.');
        }

        $oldValues = $academicYear->only(['status']);
        $academicYear->update(['status' => 'ongoing']);

        $auditLogService->log('academic_year.simulation_opened', $academicYear, $oldValues, $academicYear->only([
            'id',
            'name',
            'status',
        ]));

        DashboardCacheService::bust();

        return back()->with('success', 'School year marked as ongoing.');
    }

    public function advanceQuarter(
        AcademicYear $academicYear,
        SystemBackupService $backupService,
        AuditLogService $auditLogService,
        BatchPromotionService $batchPromotionService,
    ): RedirectResponse {
        $oldQuarter = (string) $academicYear->current_quarter;
        $next = (int) $academicYear->current_quarter + 1;

        if ($next <= 4) {
            $academicYear->update(['current_quarter' => (string) $next]);

            $auditLogService->log('academic_year.quarter_advanced', $academicYear, [
                'current_quarter' => $oldQuarter,
            ], [
                'current_quarter' => (string) $next,
            ]);

            if (Setting::enabled('backup_on_quarter', true)) {
                $backup = $backupService->createBackup('quarter_advance', [
                    'academic_year_id' => $academicYear->id,
                    'academic_year' => $academicYear->name,
                    'new_quarter' => (string) $next,
                ]);

                $auditLogService->log('backup.created', $academicYear, null, [
                    'reason' => 'quarter_advance',
                    'file_name' => $backup['file_name'],
                    'academic_year' => $academicYear->name,
                    'new_quarter' => (string) $next,
                ]);
            }

            DashboardCacheService::bust();

            return back()->with('success', 'Quarter advanced successfully.');
        }

        $targetAcademicYear = AcademicYear::query()
            ->where('start_date', '>', $academicYear->start_date)
            ->orderBy('start_date')
            ->first();

        if (! $targetAcademicYear) {
            $auditLogService->log('academic_year.close_blocked', $academicYear, null, [
                'reason' => 'missing_upcoming_school_year',
            ]);

            return back()->with('error', 'Cannot close school year without an upcoming school year record.');
        }

        $pendingVerificationCount = GradeSubmission::query()
            ->where('academic_year_id', $academicYear->id)
            ->whereIn('status', [GradeSubmission::STATUS_SUBMITTED, GradeSubmission::STATUS_RETURNED])
            ->count();

        if ($pendingVerificationCount > 0) {
            $auditLogService->log('academic_year.close_blocked', $academicYear, null, [
                'reason' => 'grade_verification_pending',
                'pending_verification_count' => $pendingVerificationCount,
            ]);

            return back()->with('error', 'Cannot close school year. Resolve all grade verifications first.');
        }

        $promotionSummary = $batchPromotionService->run(
            $academicYear,
            $targetAcademicYear,
            auth()->user()
        );

        if (($promotionSummary['grade_completeness_issue_count'] ?? 0) > 0) {
            $auditLogService->log('academic_year.close_blocked', $academicYear, null, [
                'reason' => 'grade_completeness_issues',
                'grade_completeness_issue_count' => (int) ($promotionSummary['grade_completeness_issue_count'] ?? 0),
            ]);

            return back()->with('error', 'Cannot close school year. Complete and lock all annual grades first.');
        }

        $oldValues = $academicYear->only(['status']);
        $academicYear->update(['status' => 'completed']);

        $auditLogService->log('academic_year.closed', $academicYear, $oldValues, [
            'status' => 'completed',
            'target_academic_year_id' => (int) $targetAcademicYear->id,
            'target_academic_year' => $targetAcademicYear->name,
            'promotion_summary' => $promotionSummary,
        ]);

        if (Setting::enabled('backup_on_year_end', true)) {
            $backup = $backupService->createBackup('year_end', [
                'academic_year_id' => $academicYear->id,
                'academic_year' => $academicYear->name,
                'completed_at' => now()->toIso8601String(),
            ]);

            $auditLogService->log('backup.created', $academicYear, null, [
                'reason' => 'year_end',
                'file_name' => $backup['file_name'],
                'academic_year' => $academicYear->name,
            ]);
        }

        DashboardCacheService::bust();

        return back()->with('success', 'School year closed successfully.');
    }

    public function resetSimulation(AuditLogService $auditLogService): RedirectResponse
    {
        if ($this->isSimulationBlocked()) {
            return back()->with('error', 'Simulation actions are disabled in production.');
        }

        $deletedYearCount = AcademicYear::query()->count();
        AcademicYear::query()->truncate();

        $auditLogService->log('academic_year.simulation_reset', AcademicYear::class, [
            'deleted_count' => $deletedYearCount,
        ], [
            'deleted_count' => 0,
        ]);

        DashboardCacheService::bust();

        return back()->with('success', 'Simulation data reset complete.');
    }

    private function isSimulationBlocked(): bool
    {
        return app()->environment('production') || config('app.env') === 'production';
    }
}
