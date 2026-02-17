<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        for ($i = 1; $i <= 20; $i++) {
            User::updateOrCreate(
                ['email' => "teacher{$i}@marriott.edu"],
                [
                    'first_name' => 'Teacher',
                    'last_name' => (string) $i,
                    'name' => "Teacher {$i}",
                    'password' => Hash::make('password'),
                    'birthday' => '1980-01-01',
                    'role' => UserRole::TEACHER,
                ]
            );
        }
    }
}
