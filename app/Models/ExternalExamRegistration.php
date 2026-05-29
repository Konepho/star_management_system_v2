<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ExternalExamRegistration extends Model
{
    use HasFactory;

    public const STATUS_REGISTERED = 'registered';
    public const STATUS_CONFIRMED = 'confirmed';
    public const STATUS_ATTENDED = 'attended';
    public const STATUS_ABSENT = 'absent';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    public const RESULT_PENDING = 'pending';
    public const RESULT_PASS = 'pass';
    public const RESULT_FAIL = 'fail';
    public const RESULT_ABSENT = 'absent';
    public const RESULT_WITHHELD = 'withheld';

    protected $fillable = [
        'student_id',
        'external_exam_session_id',
        'registration_date',
        'status',
        'fee_amount',
        'discount_amount',
        'total_amount',
        'candidate_no',
        'score',
        'grade',
        'result_status',
        'result_remarks',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'registration_date' => 'date',
            'fee_amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'total_amount' => 'decimal:2',
            'score' => 'decimal:2',
        ];
    }

    public static function statusOptions(): array
    {
        return [
            self::STATUS_REGISTERED => 'Registered',
            self::STATUS_CONFIRMED => 'Confirmed',
            self::STATUS_ATTENDED => 'Attended',
            self::STATUS_ABSENT => 'Absent',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    public static function resultStatusOptions(): array
    {
        return [
            self::RESULT_PENDING => 'Pending',
            self::RESULT_PASS => 'Pass',
            self::RESULT_FAIL => 'Fail',
            self::RESULT_ABSENT => 'Absent',
            self::RESULT_WITHHELD => 'Withheld',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function session(): BelongsTo
    {
        return $this->belongsTo(ExternalExamSession::class, 'external_exam_session_id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(ExternalExamPayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }

    public function postedPayments(): HasMany
    {
        return $this->hasMany(ExternalExamPayment::class)
            ->whereNull('reversed_at')
            ->orderByDesc('payment_date')
            ->orderByDesc('id');
    }

    public function getPaidAmountAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments
                ->filter(fn (ExternalExamPayment $payment) => ! $payment->isReversed())
                ->sum('amount');
        }

        return (float) $this->postedPayments()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->paid_amount);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        if ((float) $this->total_amount <= 0) {
            return 'No Fee';
        }

        if ($this->paid_amount <= 0) {
            return 'Unpaid';
        }

        if ($this->paid_amount < (float) $this->total_amount) {
            return 'Partial';
        }

        return 'Paid';
    }
}
