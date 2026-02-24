<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransactionDueAllocation extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionDueAllocationFactory> */
    use HasFactory;

    protected $fillable = [
        'transaction_id',
        'billing_schedule_id',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];

    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }

    public function billingSchedule(): BelongsTo
    {
        return $this->belongsTo(BillingSchedule::class);
    }
}
