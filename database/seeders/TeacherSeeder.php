<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;

class TeacherSeeder extends Seeder
{
    public function run(): void
    {
        $teachers = [
            ['name' => 'Arthur Santos', 'email' => 'arthur@marriott.edu'],
            ['name' => 'Liza Reyes', 'email' => 'liza@marriott.edu'],
            ['name' => 'Clara Oswald', 'email' => 'clara@marriott.edu'],
            ['name' => 'Rose Tyler', 'email' => 'rose@marriott.edu'],
            ['name' => 'Martha Jones', 'email' => 'martha@marriott.edu'],
            ['name' => 'Donna Noble', 'email' => 'donna@marriott.edu'],
            ['name' => 'Amy Pond', 'email' => 'amy@marriott.edu'],
            ['name' => 'Rory Williams', 'email' => 'rory@marriott.edu'],
            ['name' => 'River Song', 'email' => 'river@marriott.edu'],
            ['name' => 'Jack Harkness', 'email' => 'jack@marriott.edu'],
        ];

        foreach ($teachers as $teacher) {
            User::factory()->create([
                'name' => $teacher['name'],
                'email' => $teacher['email'],
                'password' => 'password',
                'role' => UserRole::TEACHER,
            ]);
        }
    }
}
