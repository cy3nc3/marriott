<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\InitializeAcademicYearRequest;
use App\Http\Requests\Admin\UpdateAcademicYearDatesRequest;
use App\Models\AcademicYear;
use App\Models\GradeLevel;
use App\Models\GradeSubmission;
use App\Models\Section;
use App\Models\Setting;
use App\Services\AuditLogService;
use App\Services\DashboardCacheService;
use App\Services\Registrar\BatchPromotionService;
use App\Services\SystemBackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class SchoolYearController extends Controller
{
    public function index(): Response
    {
        $this->syncUpcomingYearIfDue();

        $currentYear = AcademicYear::query()
            ->where('status', 'ongoing')
            ->orderByDesc('start_date')
            ->first();
        $upcomingYear = AcademicYear::query()
            ->where('status', 'upcoming')
            ->orderBy('start_date', 'asc')
            ->first();

        $nextYearName = null;
        $latestRecord = AcademicYear::query()
            ->orderByDesc('created_at')
            ->first();

        if ($latestRecord) {
            $nextYearName = $this->buildNextYearNameFromName($latestRecord->name);
        }

        return Inertia::render('admin/academic-controls/index', [
            'currentYear' => $currentYear,
            'upcomingYear' => $upcomingYear,
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
        $upcomingExists = AcademicYear::query()
            ->where('status', 'upcoming')
            ->exists();

        if ($upcomingExists) {
            return back()->with('error', 'An upcoming school year already exists.');
        }

        $validated = $request->validated();
        $yearName = $validated['name'];

        $academicYear = AcademicYear::query()->create([
            'name' => $yearName,
            'start_date' => null,
            'end_date' => null,
            'status' => 'upcoming',
            'current_quarter' => '1',
        ]);
        $this->seedDefaultSectionsForAcademicYear($academicYear);

        $auditLogService->log('academic_year.initialized', $academicYear, null, $academicYear->only([
            'id',
            'name',
            'status',
            'start_date',
            'end_date',
            'current_quarter',
        ]));

        DashboardCacheService::bust();

        return back()->with('success', "School year {$academicYear->name} initialized as pre-opening.");
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

        if (! $academicYear->start_date || ! $academicYear->end_date) {
            return back()->with('error', 'Set the school year dates before opening the cycle.');
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
            $targetAcademicYear = $this->createUpcomingAcademicYearFrom($academicYear);

            $auditLogService->log('academic_year.auto_initialized', $targetAcademicYear, null, $targetAcademicYear->only([
                'id',
                'name',
                'status',
                'start_date',
                'end_date',
                'current_quarter',
            ]));
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

    private function createUpcomingAcademicYearFrom(AcademicYear $academicYear): AcademicYear
    {
        $nextYearName = $this->buildNextYearNameFromName($academicYear->name);
        if (! $nextYearName) {
            $nextYearName = "{$academicYear->name}-NEXT";
        }

        $nextStartDate = null;
        $nextEndDate = null;

        if ($academicYear->start_date && $academicYear->end_date) {
            $nextStartDate = Carbon::parse($academicYear->start_date)->addYear()->toDateString();
            $nextEndDate = Carbon::parse($academicYear->end_date)->addYear()->toDateString();
        }

        $existingAcademicYear = AcademicYear::query()
            ->where('name', $nextYearName)
            ->first();

        if ($existingAcademicYear) {
            return $existingAcademicYear;
        }

        $nextAcademicYear = AcademicYear::query()->create([
            'name' => $nextYearName,
            'start_date' => $nextStartDate,
            'end_date' => $nextEndDate,
            'status' => 'upcoming',
            'current_quarter' => '1',
        ]);
        $this->seedDefaultSectionsForAcademicYear($nextAcademicYear);

        return $nextAcademicYear;
    }

    private function buildNextYearNameFromName(?string $name): ?string
    {
        if (! is_string($name) || preg_match('/^(\d{4})-(\d{4})$/', $name, $matches) !== 1) {
            return null;
        }

        $startYear = (int) $matches[1];
        $endYear = (int) $matches[2];

        if ($endYear !== $startYear + 1) {
            return null;
        }

        return ($startYear + 1).'-'.($endYear + 1);
    }

    private function syncUpcomingYearIfDue(): void
    {
        $ongoingYearExists = AcademicYear::query()
            ->where('status', 'ongoing')
            ->exists();

        if ($ongoingYearExists) {
            return;
        }

        $dueUpcomingYear = AcademicYear::query()
            ->where('status', 'upcoming')
            ->whereNotNull('start_date')
            ->whereDate('start_date', '<=', today()->toDateString())
            ->orderBy('start_date')
            ->first();

        if (! $dueUpcomingYear) {
            return;
        }

        $dueUpcomingYear->update([
            'status' => 'ongoing',
            'current_quarter' => '1',
        ]);

        DashboardCacheService::bust();
    }

    private function seedDefaultSectionsForAcademicYear(AcademicYear $academicYear): void
    {
        $defaultSectionNames = [
            'Rizal',
            'Bonifacio',
            'Mabini',
            'Del Pilar',
            'Luna',
            'Aguinaldo',
        ];

        $gradeLevels = GradeLevel::query()
            ->orderBy('level_order')
            ->get(['id']);

        foreach ($gradeLevels as $gradeLevel) {
            foreach ($defaultSectionNames as $sectionName) {
                Section::query()->updateOrCreate([
                    'academic_year_id' => $academicYear->id,
                    'grade_level_id' => $gradeLevel->id,
                    'name' => $sectionName,
                ]);
            }
        }
    }
}
