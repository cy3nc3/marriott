<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AnnouncementAttachment>
 */
class AnnouncementAttachmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'announcement_id' => 1,
            'original_name' => fake()->word().'.txt',
            'stored_path' => 'announcements/'.fake()->uuid().'.txt',
            'mime_type' => 'text/plain',
            'file_size' => fake()->numberBetween(200, 100000),
        ];
    }
}
