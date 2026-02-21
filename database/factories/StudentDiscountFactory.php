<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\Discount;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\StudentDiscount>
 */
class StudentDiscountFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'student_id' => function () {
                return Student::query()->create([
                    'lrn' => fake()->unique()->numerify('############'),
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                ])->id;
            },
            'discount_id' => Discount::factory(),
            'academic_year_id' => function () {
                return AcademicYear::query()->create([
                    'name' => fake()->randomElement([
                        '2025-2026',
                        '2026-2027',
                        '2027-2028',
                    ]),
                    'start_date' => '2025-06-01',
                    'end_date' => '2026-03-31',
                    'status' => 'ongoing',
                    'current_quarter' => '1',
                ])->id;
            },
        ];
    }
}
