<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\Setting;
use App\Models\User;
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

        $staffRoles = [
            UserRole::SUPER_ADMIN->value,
            UserRole::ADMIN->value,
            UserRole::REGISTRAR->value,
            UserRole::FINANCE->value,
            UserRole::TEACHER->value,
        ];

        $recentLogs = AuditLog::query()
            ->with('user:id,name')
            ->latest()
            ->limit(8)
            ->get()
            ->map(function (AuditLog $log) {
                return [
                    'id' => $log->id,
                    'action' => $log->action,
                    'target' => $this->targetLabel($log->model_type, $log->model_id),
                    'user' => $log->user?->name ?? 'System',
                    'created_at' => $log->created_at?->toIso8601String(),
                ];
            })
            ->all();

        $roleDistribution = collect(UserRole::cases())
            ->map(function (UserRole $role) use ($roleTotals) {
                return [
                    'role' => $role->value,
                    'label' => $role->label(),
                    'count' => (int) ($roleTotals[$role->value] ?? 0),
                ];
            })
            ->values()
            ->all();

        return Inertia::render('super_admin/dashboard', [
            'metrics' => [
                'total_users' => (int) User::count(),
                'staff_users' => (int) collect($roleTotals)->only($staffRoles)->sum(),
                'active_users' => (int) User::query()->where('is_active', true)->count(),
                'announcements' => (int) Announcement::count(),
                'audit_logs_today' => (int) AuditLog::query()->whereDate('created_at', now()->toDateString())->count(),
                'maintenance_mode' => Setting::enabled('maintenance_mode'),
                'parent_portal_enabled' => Setting::enabled('parent_portal', true),
                'latest_backup_at' => Setting::get('latest_backup_at'),
            ],
            'role_distribution' => $roleDistribution,
            'recent_logs' => $recentLogs,
        ]);
    }

    private function targetLabel(?string $modelType, ?int $modelId): string
    {
        $modelName = $modelType ? class_basename($modelType) : 'System';
        $suffix = $modelId ? " #{$modelId}" : '';

        return "{$modelName}{$suffix}";
    }
}
