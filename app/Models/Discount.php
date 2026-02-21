<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    /** @use HasFactory<\Database\Factories\DiscountFactory> */
    use HasFactory;

    protected $fillable = [
        'name',
        'type',
        'value',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    public function studentDiscounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }
}
