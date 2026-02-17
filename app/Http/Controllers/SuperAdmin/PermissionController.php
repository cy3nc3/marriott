<?php

namespace App\Http\Controllers\SuperAdmin;

use App\Enums\UserRole;
use App\Http\Controllers\Controller;
use Inertia\Inertia;
use Inertia\Response;

class PermissionController extends Controller
{
    public function index(): Response
    {
        $permissions = [
            'Academic Controls' => [
                'School Year Manager' => [UserRole::ADMIN, UserRole::SUPER_ADMIN],
                'Curriculum Manager' => [UserRole::ADMIN, UserRole::SUPER_ADMIN],
                'Section Manager' => [UserRole::ADMIN, UserRole::SUPER_ADMIN],
                'Schedule Builder' => [UserRole::ADMIN, UserRole::SUPER_ADMIN],
            ],
            'Student Management' => [
                'Student Directory' => [UserRole::REGISTRAR, UserRole::SUPER_ADMIN],
                'Enrollment' => [UserRole::REGISTRAR, UserRole::SUPER_ADMIN],
                'Class Lists' => [UserRole::REGISTRAR, UserRole::ADMIN, UserRole::SUPER_ADMIN],
                'Permanent Records' => [UserRole::REGISTRAR, UserRole::SUPER_ADMIN],
            ],
            'Financials' => [
                'Student Ledgers' => [UserRole::FINANCE, UserRole::SUPER_ADMIN],
                'Cashier Panel' => [UserRole::FINANCE, UserRole::SUPER_ADMIN],
                'Fee Structure' => [UserRole::FINANCE, UserRole::SUPER_ADMIN],
            ],
            'Instructional' => [
                'Grading Sheet' => [UserRole::TEACHER],
                'Advisory Board' => [UserRole::TEACHER],
                'My Schedule' => [UserRole::TEACHER, UserRole::STUDENT, UserRole::PARENT],
            ],
            'System' => [
                'User Manager' => [UserRole::SUPER_ADMIN],
                'System Configuration' => [UserRole::SUPER_ADMIN],
                'Audit Logs' => [UserRole::SUPER_ADMIN],
                'Database Backup' => [UserRole::SUPER_ADMIN],
            ]
        ];

        return Inertia::render('super_admin/permissions/index', [
            'permissions' => $permissions,
            'roles' => array_map(fn($role) => [
                'value' => $role->value,
                'label' => $role->label()
            ], UserRole::cases())
        ]);
    }
}
