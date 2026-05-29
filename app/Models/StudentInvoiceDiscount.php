<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentInvoiceDiscount extends Model
{
    use HasFactory;

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED = 'fixed';
    public const AUTO_APPLIED_NOTE_PREFIX = 'Auto-applied from student discount assignment.';

    protected $fillable = [
        'student_invoice_id',
        'student_invoice_item_id',
        'discount_definition_id',
        'discount_type',
        'value',
        'amount',
        'reason',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'decimal:2',
            'amount' => 'decimal:2',
        ];
    }

    public static function typeOptions(): array
    {
        return [
            self::TYPE_PERCENTAGE => 'Percentage',
            self::TYPE_FIXED => 'Fixed Amount',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function item(): BelongsTo
    {
        return $this->belongsTo(StudentInvoiceItem::class, 'student_invoice_item_id');
    }

    public function discountDefinition(): BelongsTo
    {
        return $this->belongsTo(DiscountDefinition::class);
    }

    public function getIsAutoAppliedAttribute(): bool
    {
        return str_starts_with((string) $this->notes, self::AUTO_APPLIED_NOTE_PREFIX);
    }
}
