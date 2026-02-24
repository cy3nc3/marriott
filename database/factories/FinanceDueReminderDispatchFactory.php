<?php

namespace Database\Factories;

use App\Models\AcademicYear;
use App\Models\BillingSchedule;
use App\Models\FinanceDueReminderRule;
use App\Models\Student;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\FinanceDueReminderDispatch>
 */
class FinanceDueReminderDispatchFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'finance_due_reminder_rule_id' => FinanceDueReminderRule::factory(),
            'billing_schedule_id' => function (): int {
                $academicYear = AcademicYear::factory()->create();
                $student = Student::query()->create([
                    'lrn' => fake()->unique()->numerify('############'),
                    'first_name' => fake()->firstName(),
                    'last_name' => fake()->lastName(),
                ]);
                $billingSchedule = BillingSchedule::query()->create([
                    'student_id' => $student->id,
                    'academic_year_id' => $academicYear->id,
                    'description' => fake()->randomElement([
                        'Tuition Installment',
                        'Miscellaneous Fees',
                    ]),
                    'due_date' => now()->addDays(7)->toDateString(),
                    'amount_due' => 1000,
                    'amount_paid' => 0,
                    'status' => 'unpaid',
                ]);

                return (int) $billingSchedule->id;
            },
            'reminder_date' => now()->toDateString(),
            'announcement_id' => null,
            'sent_at' => now(),
        ];
    }
}
