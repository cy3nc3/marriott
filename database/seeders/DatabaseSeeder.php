<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            AcademicYearSeeder::class,
            GradeLevelSeeder::class,
            SubjectSeeder::class,
            TeacherSeeder::class,
            SectionSeeder::class,
            StudentSeeder::class,
            StudentSeeder::class,
        ]);

        foreach (UserRole::cases() as $role) {
            $prefix = str_replace('_', '', $role->value);

            User::updateOrCreate(
                ['email' => "{$prefix}@marriott.edu"],
                [
                    'first_name' => 'Test',
                    'last_name' => $role->label(),
                    'name' => "Test {$role->label()}",
                    'password' => Hash::make('password'),
                    'birthday' => '1990-01-01',
                    'role' => $role,
                ]
            );
        }

        $this->call([
            SuperAdminSeeder::class,
        ]);
    }
}
