<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Permission;
use Illuminate\Database\Seeder;

class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        $matrix = [
            'Academic Controls' => [
                'School Year Manager' => [UserRole::ADMIN, UserRole::SUPER_ADMIN],
                'Curriculum Manager' => [UserRole::ADMIN, UserRole::SUPER_ADMIN],
                'Section Manager' => [UserRole::ADMIN, UserRole::SUPER_ADMIN],
                'Schedule Builder' => [UserRole::ADMIN, UserRole::SUPER_ADMIN],
                'Grade Verification' => [UserRole::ADMIN, UserRole::SUPER_ADMIN],
                'DepEd Reports' => [UserRole::ADMIN, UserRole::SUPER_ADMIN],
                'SF9 Generator' => [UserRole::ADMIN, UserRole::SUPER_ADMIN],
            ],
            'Student Management' => [
                'Student Directory' => [UserRole::REGISTRAR, UserRole::SUPER_ADMIN],
                'Enrollment' => [UserRole::REGISTRAR, UserRole::SUPER_ADMIN],
                'Class Lists' => [UserRole::REGISTRAR, UserRole::ADMIN, UserRole::SUPER_ADMIN],
                'Permanent Records' => [UserRole::REGISTRAR, UserRole::SUPER_ADMIN],
                'Data Import' => [UserRole::REGISTRAR, UserRole::FINANCE, UserRole::SUPER_ADMIN],
                'Batch Promotion' => [UserRole::REGISTRAR, UserRole::SUPER_ADMIN],
                'Remedial Entry' => [UserRole::REGISTRAR, UserRole::SUPER_ADMIN],
                'Student Departure' => [UserRole::REGISTRAR, UserRole::SUPER_ADMIN],
            ],
            'Financials' => [
                'Student Ledgers' => [UserRole::FINANCE, UserRole::SUPER_ADMIN],
                'Cashier Panel' => [UserRole::FINANCE, UserRole::SUPER_ADMIN],
                'Fee Structure' => [UserRole::FINANCE, UserRole::SUPER_ADMIN],
                'Transaction History' => [UserRole::FINANCE, UserRole::SUPER_ADMIN],
                'Product Inventory' => [UserRole::FINANCE, UserRole::SUPER_ADMIN],
                'Discount Manager' => [UserRole::FINANCE, UserRole::SUPER_ADMIN],
                'Daily Reports' => [UserRole::FINANCE, UserRole::SUPER_ADMIN],
                'Due Reminder Settings' => [UserRole::FINANCE, UserRole::SUPER_ADMIN],
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
                'Announcements' => [
                    UserRole::SUPER_ADMIN,
                    UserRole::ADMIN,
                    UserRole::REGISTRAR,
                    UserRole::FINANCE,
                    UserRole::TEACHER,
                ],
            ],
        ];

        foreach ($matrix as $module => $features) {
            foreach ($features as $feature => $allowedRoles) {
                // Initialize all roles for each feature
                foreach (UserRole::cases() as $role) {
                    $level = in_array($role, $allowedRoles) ? 2 : 0; // Default Full Access if in list, else None

                    Permission::updateOrCreate(
                        ['role' => $role->value, 'feature' => $feature],
                        ['module' => $module, 'access_level' => $level]
                    );
                }
            }
        }
    }
}
