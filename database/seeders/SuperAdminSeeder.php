<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Announcement;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $superAdmin = User::where('role', UserRole::SUPER_ADMIN)->first();
        if (!$superAdmin) return;

        // 1. Create mock announcements
        Announcement::create([
            'user_id' => $superAdmin->id,
            'title' => 'System Maintenance: February 20, 2026',
            'content' => 'The system will be undergoing scheduled maintenance this coming Friday from 10:00 PM to 2:00 AM. Please save all your work before the maintenance period starts.',
            'priority' => 'high',
            'target_roles' => null,
            'expires_at' => '2026-02-21 00:00:00',
        ]);

        Announcement::create([
            'user_id' => $superAdmin->id,
            'title' => 'Welcome to the New School Management System!',
            'content' => 'We are excited to launch our upgraded portal. Feel free to explore the new features and reach out to the IT department for any assistance.',
            'priority' => 'normal',
            'target_roles' => null,
        ]);

        // 2. Create mock audit logs
        $actions = [
            ['action' => 'login', 'model' => 'User', 'desc' => 'User logged in'],
            ['action' => 'update', 'model' => 'AcademicYear', 'desc' => 'Updated school year dates'],
            ['action' => 'create', 'model' => 'User', 'desc' => 'Created new teacher account'],
            ['action' => 'deactivate', 'model' => 'User', 'desc' => 'Deactivated staff account'],
            ['action' => 'restore', 'model' => 'Database', 'desc' => 'Restored database snapshot'],
        ];

        foreach (range(1, 15) as $i) {
            $act = $actions[array_rand($actions)];
            AuditLog::create([
                'user_id' => $superAdmin->id,
                'action' => $act['action'],
                'model_type' => 'App\\Models\\' . $act['model'],
                'model_id' => rand(1, 100),
                'ip_address' => '192.168.1.' . rand(1, 254),
                'user_agent' => 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36',
                'created_at' => now()->subHours(rand(1, 72)),
            ]);
        }
    }
}
