<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use App\Models\Permission;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        if (! $user) {
            return redirect()->route('login');
        }

        $authenticatedRole = $user->role->value;
        $effectiveRole = $this->resolveEffectiveRole($request, $authenticatedRole);

        if ($authenticatedRole === UserRole::SUPER_ADMIN->value && $effectiveRole === UserRole::SUPER_ADMIN->value) {
            return $next($request);
        }

        // Basic traditional role check (if the route is strictly for specific roles)
        if (! empty($roles) && ! in_array($effectiveRole, $roles, true) && ! in_array($authenticatedRole, $roles, true)) {
            abort(403, 'Unauthorized action.');
        }

        // Dynamic Permission Enforcement
        $routeName = $request->route()->getName();
        if ($routeName) {
            $feature = $this->resolveFeature($routeName);
            if ($feature) {
                $permission = Permission::where('role', $effectiveRole)
                    ->where('feature', $feature)
                    ->first();

                $level = $permission ? $permission->access_level : 0;

                // Level 0: No Access
                if ($level === 0) {
                    abort(403, "Access denied: {$feature}");
                }

                // Level 1: Read-Only
                // Blocks POST, PUT, PATCH, DELETE
                if ($level === 1 && ! $request->isMethod('GET') && ! $request->isMethod('HEAD')) {
                    abort(403, "Unauthorized: You have Read-Only access to {$feature}");
                }
            }
        }

        return $next($request);
    }

    private function resolveEffectiveRole(Request $request, string $authenticatedRole): string
    {
        if ($authenticatedRole !== UserRole::SUPER_ADMIN->value) {
            return $authenticatedRole;
        }

        $viewAsRole = (string) ($request->session()->get('view_as_role') ?? '');
        if ($viewAsRole === '' || $viewAsRole === UserRole::SUPER_ADMIN->value) {
            return UserRole::SUPER_ADMIN->value;
        }

        $allowed = [
            UserRole::ADMIN->value,
            UserRole::REGISTRAR->value,
            UserRole::FINANCE->value,
            UserRole::TEACHER->value,
            UserRole::STUDENT->value,
            UserRole::PARENT->value,
        ];

        return in_array($viewAsRole, $allowed, true)
            ? $viewAsRole
            : UserRole::SUPER_ADMIN->value;
    }

    /**
     * Map logical route names to board features
     */
    private function resolveFeature(string $routeName): ?string
    {
        $map = [
            'admin.academic_controls*' => 'School Year Manager',
            'admin.curriculum_manager*' => 'Curriculum Manager',
            'admin.section_manager*' => 'Section Manager',
            'admin.teacher_profiles*' => 'Section Manager',
            'admin.schedule_builder*' => 'Schedule Builder',
            'admin.grade_verification*' => 'Grade Verification',

            'registrar.student_directory*' => 'Student Directory',
            'registrar.enrollment*' => 'Enrollment',
            'admin.class_lists*' => 'Class Lists',
            'registrar.permanent_records*' => 'Permanent Records',
            'registrar.data_import*' => 'Data Import',
            'registrar.batch_promotion*' => 'Batch Promotion',
            'registrar.remedial_entry*' => 'Remedial Entry',
            'registrar.student_departure*' => 'Student Departure',

            'finance.student_ledgers*' => 'Student Ledgers',
            'finance.cashier_panel*' => 'Cashier Panel',
            'finance.fee_structure*' => 'Fee Structure',
            'finance.transaction_history*' => 'Transaction History',
            'finance.product_inventory*' => 'Product Inventory',
            'finance.discount_manager*' => 'Discount Manager',
            'finance.daily_reports*' => 'Daily Reports',
            'finance.due_reminder_settings*' => 'Due Reminder Settings',
            'finance.data_import*' => 'Data Import',

            'announcements*' => 'Announcements',

            'super_admin.user_manager*' => 'User Manager',
            'super_admin.system_settings*' => 'System Configuration',
            'super_admin.audit_logs*' => 'Audit Logs',
            'super_admin.database_backup*' => 'Database Backup',
            'super_admin.announcements*' => 'Announcements',

            'teacher.grading*' => 'Grading Sheet',
            'teacher.remedial_encoding*' => 'Grading Sheet',
            'teacher.advisory*' => 'Advisory Board',
            'teacher.schedule*' => 'My Schedule',
            'student.schedule*' => 'My Schedule',
            'parent.schedule*' => 'My Schedule',
        ];

        foreach ($map as $pattern => $feature) {
            if (Str::is($pattern, $routeName)) {
                return $feature;
            }
        }

        return null;
    }
}
