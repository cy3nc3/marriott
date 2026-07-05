<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TeacherSeeder extends Seeder
{
    private const TEACHERS = [
        ['first_name' => 'Rowell', 'last_name' => 'Almonte'],
        ['first_name' => 'Rocelle', 'last_name' => 'De la Cruz'],
        ['first_name' => 'Fe Mercedes', 'last_name' => 'Cavitt'],
        ['first_name' => 'Elenor', 'last_name' => 'Cendana'],
        ['first_name' => 'Ma Nimfa', 'last_name' => 'Guinacaran'],
        ['first_name' => 'Mary Joyce', 'last_name' => 'Guira'],
        ['first_name' => 'Racquel', 'last_name' => 'Vergara'],
        ['first_name' => 'Beronica', 'last_name' => 'Renton'],
    ];

    public function run(): void
    {
        foreach (self::TEACHERS as $teacher) {
            $firstName = $teacher['first_name'];
            $lastName = $teacher['last_name'];

            $firstToken = Str::of($firstName)->ascii()->lower()->replaceMatches('/[^a-z0-9 ]+/', '')->squish()->explode(' ')->filter()->first();
            $lastToken = Str::of($lastName)->ascii()->lower()->replaceMatches('/[^a-z0-9]+/', '');

            $email = "{$firstToken}.{$lastToken}@marriott.edu";
            $personalEmail = "{$firstToken}.{$lastToken}@test.com";

            User::updateOrCreate(
                ['email' => $email],
                [
                    'first_name' => $firstName,
                    'last_name' => $lastName,
                    'name' => "{$firstName} {$lastName}",
                    'personal_email' => $personalEmail,
                    'password' => Hash::make('password'),
                    'birthday' => '1980-01-01',
                    'role' => UserRole::TEACHER,
                ]
            );
        }
    }
}
