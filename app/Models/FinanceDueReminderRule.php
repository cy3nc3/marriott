<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinanceDueReminderRule extends Model
{
    /** @use HasFactory<\Database\Factories\FinanceDueReminderRuleFactory> */
    use HasFactory;

    protected $fillable = [
        'days_before_due',
        'is_active',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'days_before_due' => 'integer',
            'is_active' => 'boolean',
        ];
    }

    public function dispatches(): HasMany
    {
        return $this->hasMany(FinanceDueReminderDispatch::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
