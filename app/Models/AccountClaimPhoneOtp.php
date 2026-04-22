<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountClaimPhoneOtp extends Model
{
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'account_claim_token_id',
        'user_id',
        'enrollment_id',
        'phone_number',
        'code_hash',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
            'verified_at' => 'datetime',
            'attempts' => 'int',
        ];
    }

    public function accountClaimToken(): BelongsTo
    {
        return $this->belongsTo(AccountClaimToken::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function scopeActive(Builder $query): Builder
    {
        return $query
            ->whereNull('verified_at')
            ->where('expires_at', '>', now());
    }
}
