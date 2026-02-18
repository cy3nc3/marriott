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

        // Link Test Student and Test Parent
        $testStudentUser = User::where('email', 'student@marriott.edu')->first();
        $testParentUser = User::where('email', 'parent@marriott.edu')->first();

        if ($testStudentUser && $testParentUser) {
            $student = \App\Models\Student::firstOrCreate(
                ['user_id' => $testStudentUser->id],
                [
                    'lrn' => 'TEST0001',
                    'first_name' => 'Test',
                    'last_name' => 'Student',
                    'gender' => 'Male',
                    'birthdate' => '2010-01-01',
                ]
            );

            $exists = \Illuminate\Support\Facades\DB::table('parent_student')
                ->where('parent_id', $testParentUser->id)
                ->where('student_id', $student->id)
                ->exists();

            if (! $exists) {
                \Illuminate\Support\Facades\DB::table('parent_student')->insert([
                    'parent_id' => $testParentUser->id,
                    'student_id' => $student->id,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        $this->call([
            SuperAdminSeeder::class,
        ]);
    }
}
