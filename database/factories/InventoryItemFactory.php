<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\InventoryItem>
 */
class InventoryItemFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement([
                'School Uniform (Male - Small)',
                'School Uniform (Female - Medium)',
                'PE Shirt (Large)',
                'Mathematics 7 Textbook',
                'School ID Lace',
            ]),
            'price' => fake()->randomFloat(2, 50, 2500),
            'type' => fake()->randomElement(['uniform', 'book', 'other']),
        ];
    }
}
