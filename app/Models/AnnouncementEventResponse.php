<?php

namespace App\Models;

use App\Services\Scheduling\AnnouncementEventReminderPlanner;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementEventResponse extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id',
        'user_id',
        'response',
        'responded_at',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'responded_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::saved(function (AnnouncementEventResponse $response): void {
            $response->loadMissing('announcement');

            if ($response->announcement) {
                app(AnnouncementEventReminderPlanner::class)
                    ->cancelRecipient($response->announcement, (int) $response->user_id, 'recipient_responded');
            }
        });
    }

    public function announcement(): BelongsTo
    {
        return $this->belongsTo(Announcement::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
