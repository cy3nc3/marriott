<?php

namespace App\Models;

use App\Services\Scheduling\AnnouncementEventReminderPlanner;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Announcement extends Model
{
    public const DELIVERY_CHANNEL_IN_APP = 'in_app';

    public const DELIVERY_CHANNEL_EMAIL = 'email';

    public const DELIVERY_CHANNEL_SMS = 'sms';

    public const TYPE_NOTICE = 'notice';

    public const TYPE_EVENT = 'event';

    public const RESPONSE_MODE_NONE = 'none';

    public const RESPONSE_MODE_ACK_RSVP = 'ack_rsvp';

    protected $fillable = [
        'user_id',
        'title',
        'content',
        'type',
        'response_mode',
        'target_roles',
        'target_user_ids',
        'delivery_channels',
        'publish_at',
        'event_starts_at',
        'event_ends_at',
        'response_deadline_at',
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'type' => 'string',
        'response_mode' => 'string',
        'target_roles' => 'array',
        'target_user_ids' => 'array',
        'delivery_channels' => 'array',
        'publish_at' => 'datetime',
        'event_starts_at' => 'datetime',
        'event_ends_at' => 'datetime',
        'response_deadline_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    protected static function booted(): void
    {
        static::saved(function (Announcement $announcement): void {
            if (
                ! $announcement->wasRecentlyCreated
                && ! $announcement->wasChanged([
                    'type',
                    'response_mode',
                    'event_starts_at',
                    'response_deadline_at',
                    'cancelled_at',
                    'is_active',
                    'expires_at',
                    'publish_at',
                ])
            ) {
                return;
            }

            app(AnnouncementEventReminderPlanner::class)->reconcileAnnouncement($announcement);
        });

        static::deleted(function (Announcement $announcement): void {
            app(AnnouncementEventReminderPlanner::class)->cancelGroup($announcement, 'announcement_deleted');
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function reads(): HasMany
    {
        return $this->hasMany(AnnouncementRead::class);
    }

    public function attachments(): HasMany
    {
        return $this->hasMany(AnnouncementAttachment::class);
    }

    public function recipients(): HasMany
    {
        return $this->hasMany(AnnouncementRecipient::class);
    }

    public function eventResponses(): HasMany
    {
        return $this->hasMany(AnnouncementEventResponse::class);
    }

    public function reminderDispatches(): HasMany
    {
        return $this->hasMany(AnnouncementReminderDispatch::class);
    }

    public function cancelledBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function isEventType(): bool
    {
        return $this->type === self::TYPE_EVENT;
    }

    /**
     * @return array<int, string>
     */
    public static function allowedDeliveryChannels(): array
    {
        return [
            self::DELIVERY_CHANNEL_IN_APP,
            self::DELIVERY_CHANNEL_EMAIL,
            self::DELIVERY_CHANNEL_SMS,
        ];
    }

    /**
     * @return array<int, string>
     */
    public function normalizedDeliveryChannels(): array
    {
        $channels = collect($this->delivery_channels ?? [])
            ->filter(fn (mixed $channel): bool => is_string($channel))
            ->map(fn (string $channel): string => trim(strtolower($channel)))
            ->filter(fn (string $channel): bool => in_array($channel, self::allowedDeliveryChannels(), true))
            ->unique()
            ->values();

        if (! $channels->contains(self::DELIVERY_CHANNEL_IN_APP)) {
            $channels->prepend(self::DELIVERY_CHANNEL_IN_APP);
        }

        return $channels->all();
    }
}
