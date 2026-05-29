<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentPayment extends Model
{
    use HasFactory;

    public const METHOD_CASH = 'cash';
    public const METHOD_MMQR = 'mmqr';
    public const METHOD_KBZPAY = 'kbzpay';

    protected $fillable = [
        'receipt_no',
        'student_invoice_id',
        'student_id',
        'payment_date',
        'amount',
        'payment_method',
        'reference_no',
        'notes',
        'reversed_at',
        'reversal_reason',
    ];

    protected function casts(): array
    {
        return [
            'payment_date' => 'date',
            'amount' => 'decimal:2',
            'reversed_at' => 'datetime',
        ];
    }

    public static function methodOptions(): array
    {
        return [
            self::METHOD_CASH => 'Cash',
            self::METHOD_MMQR => 'MMQR',
            self::METHOD_KBZPAY => 'KBZPay',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(StudentInvoice::class, 'student_invoice_id');
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function isReversed(): bool
    {
        return $this->reversed_at !== null;
    }

    public function getStatusLabelAttribute(): string
    {
        return $this->isReversed() ? 'Reversed' : 'Posted';
    }
}
