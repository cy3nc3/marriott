<?php

namespace Database\Factories;

use App\Models\AnnouncementEventResponse;
use App\Models\AnnouncementRecipient;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnouncementEventResponse>
 */
class AnnouncementEventResponseFactory extends Factory
{
    protected $model = AnnouncementEventResponse::class;

    public function definition(): array
    {
        $recipient = AnnouncementRecipient::factory()->create();

        return [
            'announcement_id' => $recipient->announcement_id,
            'user_id' => $recipient->user_id,
            'response' => fake()->randomElement(['ack_only', 'yes', 'no', 'maybe']),
            'responded_at' => now(),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
