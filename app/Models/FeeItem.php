<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FeeItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_category_id',
        'name',
        'code',
        'variant',
        'price',
        'status',
        'description',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
        ];
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function getDiscountPolicyLabelAttribute(): string
    {
        return $this->feeCategory?->allow_discount ? 'Allowed' : 'Not Allowed';
    }
}
