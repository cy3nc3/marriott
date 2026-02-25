<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AnnouncementReminderDispatch extends Model
{
    use HasFactory;

    protected $fillable = [
        'announcement_id',
        'user_id',
        'phase',
        'target_date',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'target_date' => 'date',
            'sent_at' => 'datetime',
        ];
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
