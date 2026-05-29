<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentInvoiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_invoice_id',
        'fee_structure_id',
        'fee_item_id',
        'fee_category_id',
        'description',
        'billing_cycle',
        'installment_no',
        'quantity',
        'unit_price',
        'amount',
        'due_date',
        'remarks',
        'is_system_adjustment',
        'adjustment_code',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'integer',
            'unit_price' => 'decimal:2',
            'amount' => 'decimal:2',
            'due_date' => 'date',
            'is_system_adjustment' => 'boolean',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class);
    }

    public function feeItem(): BelongsTo
    {
        return $this->belongsTo(FeeItem::class);
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(StudentInvoiceDiscount::class, 'student_invoice_item_id')->orderBy('id');
    }

    public function getDiscountAmountAttribute(): float
    {
        if ($this->relationLoaded('discounts')) {
            return (float) $this->discounts->sum('amount');
        }

        return (float) $this->discounts()->sum('amount');
    }

    public function getNetAmountAttribute(): float
    {
        return max(0, (float) $this->amount - $this->discount_amount);
    }
}
