<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use App\Services\DashboardCacheService;
use App\Services\DashboardDecisionService;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function __construct(
        private readonly DashboardDecisionService $dashboardDecisionService,
    ) {}

    public function index(): Response
    {
        $decisionService = $this->dashboardDecisionService;

        $payload = DashboardCacheService::remember('super_admin:dashboard:'.now()->toDateString(), function () use ($decisionService): array {
            $roleTotals = User::query()
                ->selectRaw('role, count(*) as total')
                ->groupBy('role')
                ->pluck('total', 'role');

            $roleDistributionPoints = collect(UserRole::cases())
                ->map(function (UserRole $role) use ($roleTotals) {
                    return [
                        'label' => $role->label(),
                        'count' => (int) ($roleTotals[$role->value] ?? 0),
                    ];
                })
                ->values()
                ->all();

            $today = now()->toDateString();
            $auditLogsToday = (int) AuditLog::query()->whereDate('created_at', $today)->count();
            $riskAuditLogsToday = (int) AuditLog::query()
                ->whereDate('created_at', $today)
                ->where(function ($query) {
                    $query
                        ->whereRaw('LOWER(action) like ?', ['%delete%'])
                        ->orWhereRaw('LOWER(action) like ?', ['%reset%'])
                        ->orWhereRaw('LOWER(action) like ?', ['%toggle%']);
                })
                ->count();

            $maintenanceMode = Setting::enabled('maintenance_mode');
            $parentPortalEnabled = Setting::enabled('parent_portal', true);

            $backupAgeHours = null;
            $latestBackupAt = Setting::get('latest_backup_at');
            if ($latestBackupAt) {
                try {
                    $backupAgeHours = Carbon::parse((string) $latestBackupAt)->diffInHours(now());
                } catch (\Throwable) {
                    $backupAgeHours = null;
                }
            }

            $alerts = $decisionService->superAdminAlerts(
                $backupAgeHours,
                $riskAuditLogsToday,
                $maintenanceMode,
                $parentPortalEnabled,
            );

            $auditTrendByDay = AuditLog::query()
                ->whereDate('created_at', '>=', now()->subDays(6)->toDateString())
                ->selectRaw('DATE(created_at) as day, count(*) as total')
                ->groupBy('day')
                ->orderBy('day')
                ->pluck('total', 'day');

            $auditTrendPoints = collect(range(6, 0, -1))
                ->map(function (int $daysAgo) use ($auditTrendByDay): array {
                    $day = now()->subDays($daysAgo)->toDateString();

                    return [
                        'label' => now()->subDays($daysAgo)->format('M d'),
                        'value' => (int) ($auditTrendByDay[$day] ?? 0),
                    ];
                })
                ->values()
                ->all();

            $decisionCards = [];

            $highRiskEventRatio = $auditLogsToday > 0
                ? round(($riskAuditLogsToday / $auditLogsToday) * 100, 2)
                : 0.0;
            $recoveryReadinessScore = min(100, max(0, (int) round(
                (($backupAgeHours === null ? 0 : max(100 - min($backupAgeHours, 100), 0)) * 0.7)
                + (($maintenanceMode ? 60 : 100) * 0.3)
            )));
            $auditRiskPatternRows = [
                ['type' => 'Important Actions', 'events' => $riskAuditLogsToday],
                ['type' => 'Other', 'events' => max($auditLogsToday - $riskAuditLogsToday, 0)],
            ];

            $kpis = [
                [
                    'id' => 'super-recovery-readiness',
                    'label' => 'Latest Backup',
                    'value' => $backupAgeHours === null ? 'Unknown' : number_format($backupAgeHours, 1).'h old',
                    'meta' => $latestBackupAt
                        ? 'Last backup: '.Carbon::parse((string) $latestBackupAt)->format('M d, Y h:i A')
                        : 'Backup timestamp missing',
                ],
                [
                    'id' => 'super-high-risk-ratio',
                    'label' => 'Important Actions Today',
                    'value' => $riskAuditLogsToday,
                    'meta' => "{$auditLogsToday} total action(s) logged today",
                ],
                [
                    'id' => 'super-high-risk-events-today',
                    'label' => 'Settings Changes Today',
                    'value' => AuditLog::query()
                        ->whereDate('created_at', $today)
                        ->where(function ($query) {
                            $query
                                ->whereRaw('LOWER(action) like ?', ['%setting%'])
                                ->orWhereRaw('LOWER(action) like ?', ['%permission%'])
                                ->orWhereRaw('LOWER(action) like ?', ['%role%']);
                        })
                        ->count(),
                    'meta' => 'Settings, permissions, or role changes',
                ],
            ];
            $kpis = $decisionService->prioritizeSuperAdminKpis($kpis, $backupAgeHours);

            $actionLinks = [
                [
                    'id' => 'view-audit-logs',
                    'label' => 'Review Important Actions',
                    'href' => route('super_admin.audit_logs'),
                ],
            ];
            $actionLinks = $decisionService->prioritizeSuperAdminActionLinks(
                $actionLinks,
                $backupAgeHours,
                $riskAuditLogsToday,
            );

            return [
                'kpis' => $kpis,
                'alerts' => array_values($alerts),
                'trends' => $decisionService->superAdminTrends(
                    $auditTrendPoints,
                    $auditRiskPatternRows,
                ),
                'action_links' => $actionLinks,
            'decision_cards' => $decisionCards,
                'action_queue' => [],
            ];
        });

        return Inertia::render('super_admin/dashboard', $payload);
    }
}
