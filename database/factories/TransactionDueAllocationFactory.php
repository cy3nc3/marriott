<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\TransactionDueAllocation>
 */
class TransactionDueAllocationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'transaction_id' => 1,
            'billing_schedule_id' => 1,
            'amount' => $this->faker->randomFloat(2, 100, 10000),
        ];
    }
}
