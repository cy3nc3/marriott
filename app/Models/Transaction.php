<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Transaction extends Model
{
    /** @use HasFactory<\Database\Factories\TransactionFactory> */
    use HasFactory;

    protected $fillable = [
        'or_number',
        'student_id',
        'cashier_id',
        'total_amount',
        'payment_mode',
        'reference_no',
        'remarks',
        'status',
        'void_reason',
        'voided_at',
        'voided_by',
        'refund_reason',
        'refunded_at',
        'refunded_by',
        'reissue_reason',
        'reissued_at',
        'reissued_by',
        'reissued_transaction_id',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'voided_at' => 'datetime',
        'refunded_at' => 'datetime',
        'reissued_at' => 'datetime',
    ];

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function cashier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cashier_id');
    }

    public function voidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'voided_by');
    }

    public function refundedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }

    public function reissuedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reissued_by');
    }

    public function reissuedTransaction(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reissued_transaction_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(TransactionItem::class);
    }

    public function dueAllocations(): HasMany
    {
        return $this->hasMany(TransactionDueAllocation::class);
    }
}
