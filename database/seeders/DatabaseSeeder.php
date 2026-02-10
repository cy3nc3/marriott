<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            $prefix = str_replace('_', '', $role->value);

            User::factory()->create([
                'name' => "Test {$role->label()}",
                'email' => "{$prefix}@marriott.edu",
                'password' => 'password',
                'role' => $role,
            ]);
        }
    }
}
