<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    public function index(): Response
    {
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

        $totalUsers = (int) User::query()->count();
        $activeUsers = (int) User::query()->where('is_active', true)->count();
        $inactiveUsers = max($totalUsers - $activeUsers, 0);

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

        $alerts = [];

        if ($backupAgeHours === null || $backupAgeHours >= 72) {
            $alerts[] = [
                'id' => 'backup-stale',
                'title' => 'Backup freshness is critical',
                'message' => $backupAgeHours === null
                    ? 'No valid backup timestamp was found in system settings.'
                    : "Last backup is {$backupAgeHours} hours old.",
                'severity' => 'critical',
            ];
        } elseif ($backupAgeHours >= 24) {
            $alerts[] = [
                'id' => 'backup-warning',
                'title' => 'Backup freshness requires attention',
                'message' => "Last backup is {$backupAgeHours} hours old.",
                'severity' => 'warning',
            ];
        }

        if ($riskAuditLogsToday >= 10) {
            $alerts[] = [
                'id' => 'audit-risk',
                'title' => 'High audit risk activity today',
                'message' => "{$riskAuditLogsToday} high-risk audit events were detected.",
                'severity' => 'critical',
            ];
        } elseif ($riskAuditLogsToday >= 4) {
            $alerts[] = [
                'id' => 'audit-risk',
                'title' => 'Audit risk activity needs review',
                'message' => "{$riskAuditLogsToday} high-risk audit events were detected.",
                'severity' => 'warning',
            ];
        }

        if ($inactiveUsers >= 20) {
            $alerts[] = [
                'id' => 'inactive-users',
                'title' => 'Inactive account backlog is high',
                'message' => "{$inactiveUsers} accounts are currently inactive.",
                'severity' => 'warning',
            ];
        }

        if ($maintenanceMode) {
            $alerts[] = [
                'id' => 'maintenance',
                'title' => 'System is currently in maintenance mode',
                'message' => 'Only authorized users should be making configuration changes.',
                'severity' => 'warning',
            ];
        }

        if ($alerts === []) {
            $alerts[] = [
                'id' => 'super-admin-stable',
                'title' => 'System governance is stable',
                'message' => 'Backups, account governance, and audit risk are within target thresholds.',
                'severity' => 'info',
            ];
        }

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

        return Inertia::render('super_admin/dashboard', [
            'kpis' => [
                [
                    'id' => 'system-health',
                    'label' => 'System Health',
                    'value' => $maintenanceMode ? 'Maintenance Mode' : 'Operational',
                    'meta' => $parentPortalEnabled ? 'Parent portal enabled' : 'Parent portal disabled',
                ],
                [
                    'id' => 'account-governance',
                    'label' => 'Account Governance',
                    'value' => "{$activeUsers} / {$totalUsers}",
                    'meta' => 'Active user accounts',
                ],
                [
                    'id' => 'audit-risk',
                    'label' => 'Audit Risk (Today)',
                    'value' => $riskAuditLogsToday,
                    'meta' => "{$auditLogsToday} total audit events",
                ],
                [
                    'id' => 'backup-freshness',
                    'label' => 'Backup Freshness',
                    'value' => $backupAgeHours === null ? 'Unknown' : "{$backupAgeHours}h",
                    'meta' => $latestBackupAt ? (string) $latestBackupAt : 'No backup timestamp',
                ],
            ],
            'alerts' => array_values($alerts),
            'trends' => [
                [
                    'id' => 'role-distribution',
                    'label' => 'Role Distribution',
                    'summary' => 'Current user count by role',
                    'display' => 'bar',
                    'points' => array_map(function (array $point): array {
                        return [
                            'label' => $point['label'],
                            'value' => $point['count'],
                        ];
                    }, $roleDistributionPoints),
                    'chart' => [
                        'x_key' => 'role',
                        'rows' => collect($roleDistributionPoints)
                            ->map(function (array $point): array {
                                return [
                                    'role' => $point['label'],
                                    'users' => $point['count'],
                                ];
                            })
                            ->values()
                            ->all(),
                        'series' => [
                            [
                                'key' => 'users',
                                'label' => 'Users',
                            ],
                        ],
                    ],
                ],
                [
                    'id' => 'audit-activity',
                    'label' => 'Audit Activity (Last 7 Days)',
                    'summary' => 'Daily volume of recorded audit events',
                    'display' => 'line',
                    'points' => $auditTrendPoints,
                    'chart' => [
                        'x_key' => 'day',
                        'rows' => collect($auditTrendPoints)
                            ->map(function (array $point): array {
                                return [
                                    'day' => $point['label'],
                                    'events' => $point['value'],
                                ];
                            })
                            ->values()
                            ->all(),
                        'series' => [
                            [
                                'key' => 'events',
                                'label' => 'Events',
                            ],
                        ],
                    ],
                ],
            ],
            'action_links' => [
                [
                    'id' => 'manage-users',
                    'label' => 'Open User Manager',
                    'href' => route('super_admin.user_manager'),
                ],
                [
                    'id' => 'view-audit-logs',
                    'label' => 'Review Audit Logs',
                    'href' => route('super_admin.audit_logs'),
                ],
                [
                    'id' => 'open-system-settings',
                    'label' => 'Open System Settings',
                    'href' => route('super_admin.system_settings'),
                ],
            ],
        ]);
    }
}
