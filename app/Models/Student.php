<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    protected $fillable = [
        'user_id',
        'lrn',
        'first_name',
        'last_name',
        'gender',
        'birthdate',
        'contact_number',
        'address',
        'guardian_name',
        'is_lis_synced',
        'sync_error_flag',
        'sync_error_notes',
        'is_for_remedial',
    ];

    protected $casts = [
        'birthdate' => 'date',
        'is_lis_synced' => 'boolean',
        'sync_error_flag' => 'boolean',
        'is_for_remedial' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function currentEnrollment()
    {
        return $this->hasOne(Enrollment::class)->latestOfMany();
    }

    public function remedialRecords(): HasMany
    {
        return $this->hasMany(RemedialRecord::class);
    }

    public function studentDiscounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function ledgerEntries(): HasMany
    {
        return $this->hasMany(LedgerEntry::class);
    }

    public function billingSchedules(): HasMany
    {
        return $this->hasMany(BillingSchedule::class);
    }

    public function studentScores(): HasMany
    {
        return $this->hasMany(StudentScore::class);
    }

    public function parents(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'parent_student', 'student_id', 'parent_id');
    }
}
