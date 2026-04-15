<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Discount extends Model
{
    /** @use HasFactory<\Database\Factories\DiscountFactory> */
    use HasFactory;

    public const DEFAULT_EXPORT_BUCKET = 'special_discount';

    /**
     * @return array<string, string>
     */
    public static function exportBucketLabels(): array
    {
        return [
            'misc_discount' => 'Misc Disc',
            'misc_sibling_discount' => 'Misc Disc Sib',
            'tuition_sibling_discount' => 'Tui Sib Dis',
            'early_enrollment_discount' => 'Early Enrlnt Disc',
            'fape' => 'Fape',
            'fape_previous_year' => 'FAPE 24-25',
            'overall_discount' => 'Over-All Discount',
            'special_discount' => 'Special Disc',
        ];
    }

    protected $fillable = [
        'name',
        'type',
        'value',
        'export_bucket',
    ];

    protected $casts = [
        'value' => 'decimal:2',
    ];

    protected static function booted(): void
    {
        static::creating(function (Discount $discount): void {
            if (! $discount->export_bucket) {
                $discount->export_bucket = self::DEFAULT_EXPORT_BUCKET;
            }
        });
    }

    public function studentDiscounts(): HasMany
    {
        return $this->hasMany(StudentDiscount::class);
    }

    public function exportBucketLabel(): string
    {
        return self::exportBucketLabels()[$this->export_bucket ?: self::DEFAULT_EXPORT_BUCKET]
            ?? self::exportBucketLabels()[self::DEFAULT_EXPORT_BUCKET];
    }
}
