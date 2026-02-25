<?php

namespace Database\Factories;

use App\Models\AnnouncementRecipient;
use App\Models\AnnouncementReminderDispatch;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnouncementReminderDispatch>
 */
class AnnouncementReminderDispatchFactory extends Factory
{
    protected $model = AnnouncementReminderDispatch::class;

    public function definition(): array
    {
        $recipient = AnnouncementRecipient::factory()->create();

        return [
            'announcement_id' => $recipient->announcement_id,
            'user_id' => $recipient->user_id,
            'phase' => fake()->randomElement(['one_day_before', 'day_of']),
            'target_date' => now()->toDateString(),
            'sent_at' => now(),
        ];
    }
}
