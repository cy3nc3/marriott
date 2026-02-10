<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $roles = [
            'superadmin' => 'superAdmin',
            'admin' => 'admin',
            'registrar' => 'registrar',
            'finance' => 'finance',
            'teacher' => 'teacher',
            'student' => 'student',
            'parent' => 'parent',
        ];

        foreach ($roles as $emailPrefix => $factoryState) {
            User::factory()->$factoryState()->create([
                'name' => ucwords(str_replace('superadmin', 'Super Admin', $emailPrefix)),
                'email' => "{$emailPrefix}@marriott.edu",
            ]);
        }
    }
}
