<?php

namespace Database\Factories;

use App\Models\Announcement;
use App\Models\AnnouncementRecipient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AnnouncementRecipient>
 */
class AnnouncementRecipientFactory extends Factory
{
    protected $model = AnnouncementRecipient::class;

    public function definition(): array
    {
        $announcement = Announcement::query()->create([
            'user_id' => User::factory()->superAdmin()->create()->id,
            'title' => fake()->sentence(3),
            'content' => fake()->paragraph(),
            'type' => 'event',
            'response_mode' => 'ack_rsvp',
            'is_active' => true,
            'event_starts_at' => now()->addDay(),
            'expires_at' => now()->addDays(3),
        ]);

        $recipient = User::factory()->create();

        return [
            'announcement_id' => $announcement->id,
            'user_id' => $recipient->id,
            'role' => is_string($recipient->role) ? $recipient->role : $recipient->role->value,
        ];
    }
}
