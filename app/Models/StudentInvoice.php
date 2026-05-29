<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StudentInvoice extends Model
{
    use HasFactory;

    public const STATUS_DRAFT = 'draft';
    public const STATUS_ISSUED = 'issued';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_PAID = 'paid';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_VOID = 'void';

    public const PERIOD_MONTHLY = 'monthly';
    public const PERIOD_QUARTERLY = 'quarterly';
    public const PERIOD_ACADEMIC_YEAR = 'academic_year';
    public const PERIOD_ONE_TIME = 'one_time';
    public const PERIOD_INSTALLMENT = 'installment';
    public const PERIOD_CUSTOM = 'custom';

    public static function statusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ISSUED => 'Issued',
            self::STATUS_PARTIAL => 'Partial',
            self::STATUS_PAID => 'Paid',
            self::STATUS_CANCELLED => 'Cancelled',
            self::STATUS_VOID => 'Void',
        ];
    }

    public static function creatableStatusOptions(): array
    {
        return [
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_ISSUED => 'Issued',
        ];
    }

    public static function billingPeriodTypeOptions(): array
    {
        return [
            self::PERIOD_MONTHLY => 'Monthly',
            self::PERIOD_QUARTERLY => 'Quarterly',
            self::PERIOD_ACADEMIC_YEAR => 'Academic Year',
            self::PERIOD_ONE_TIME => 'One-Time',
            self::PERIOD_INSTALLMENT => 'Installment',
            self::PERIOD_CUSTOM => 'Custom',
        ];
    }

    public static function paymentTimingStatusOptions(): array
    {
        return [
            'discount_eligible' => 'Paid By Due-Date Month End',
            'grace_period' => 'Paid During Grace Period After Due-Date Month',
            'late_fee_level_1' => 'Late Fee Applied (6th to 15th After Due-Date Month)',
            'late_fee_level_2' => 'Higher Late Fee Applied (After 15th After Due-Date Month)',
        ];
    }

    protected $fillable = [
        'invoice_no',
        'student_id',
        'academic_year_id',
        'enrollment_id',
        'fee_plan_id',
        'grade_id',
        'section_id',
        'issue_date',
        'due_date',
        'status',
        'billing_period_type',
        'billing_month',
        'billing_quarter',
        'billing_year_label',
        'payment_timing_status',
        'payment_timing_locked_on',
        'issued_at',
        'cancelled_at',
        'voided_at',
        'total_amount',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'issue_date' => 'date',
            'due_date' => 'date',
            'payment_timing_locked_on' => 'date',
            'issued_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'voided_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function feePlan(): BelongsTo
    {
        return $this->belongsTo(FeePlan::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(StudentInvoiceItem::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(StudentPayment::class)->orderByDesc('payment_date')->orderByDesc('id');
    }

    public function postedPayments(): HasMany
    {
        return $this->hasMany(StudentPayment::class)
            ->whereNull('reversed_at')
            ->orderByDesc('payment_date')
            ->orderByDesc('id');
    }

    public function discounts(): HasMany
    {
        return $this->hasMany(StudentInvoiceDiscount::class)->orderBy('id');
    }

    public function getSubtotalAmountAttribute(): float
    {
        if ($this->relationLoaded('items')) {
            return (float) $this->items->sum('amount');
        }

        return (float) $this->items()->sum('amount');
    }

    public function getDiscountAmountAttribute(): float
    {
        if ($this->relationLoaded('discounts')) {
            return (float) $this->discounts->sum('amount');
        }

        return (float) $this->discounts()->sum('amount');
    }

    public function getPaidAmountAttribute(): float
    {
        if ($this->relationLoaded('payments')) {
            return (float) $this->payments
                ->filter(fn (StudentPayment $payment) => ! $payment->isReversed())
                ->sum('amount');
        }

        return (float) $this->postedPayments()->sum('amount');
    }

    public function getBalanceDueAttribute(): float
    {
        return max(0, (float) $this->total_amount - $this->paid_amount);
    }

    public function recalculateTotals(): void
    {
        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_VOID], true)) {
            return;
        }

        $subtotal = $this->subtotal_amount;
        $discountAmount = $this->discount_amount;
        $netTotal = max(0, $subtotal - $discountAmount);

        $this->forceFill([
            'total_amount' => $netTotal,
        ])->save();
    }

    public function refreshPaymentStatus(): void
    {
        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_VOID], true)) {
            return;
        }

        $paidAmount = $this->paid_amount;
        $status = $this->status === self::STATUS_DRAFT ? self::STATUS_DRAFT : self::STATUS_ISSUED;

        if ($paidAmount <= 0) {
            $status = $this->status === self::STATUS_DRAFT ? self::STATUS_DRAFT : self::STATUS_ISSUED;
        } elseif ($paidAmount < (float) $this->total_amount) {
            $status = self::STATUS_PARTIAL;
        } else {
            $status = self::STATUS_PAID;
        }

        $this->forceFill([
            'status' => $status,
        ])->save();
    }

    public function getPaymentTimingStatusLabelAttribute(): ?string
    {
        if (! $this->payment_timing_status) {
            return null;
        }

        return self::paymentTimingStatusOptions()[$this->payment_timing_status] ?? ucfirst(str_replace('_', ' ', $this->payment_timing_status));
    }

    public function getBillingPeriodLabelAttribute(): string
    {
        return match ($this->billing_period_type) {
            self::PERIOD_MONTHLY => $this->billing_month ?: 'Monthly',
            self::PERIOD_QUARTERLY => $this->billing_quarter ?: 'Quarterly',
            self::PERIOD_ACADEMIC_YEAR => $this->billing_year_label ?: ($this->academicYear?->name ?? 'Academic Year'),
            self::PERIOD_ONE_TIME => 'One-Time',
            self::PERIOD_INSTALLMENT => $this->billing_year_label ?: 'Installment',
            self::PERIOD_CUSTOM => $this->billing_year_label ?: 'Custom',
            default => $this->billing_year_label ?: 'Billing Period',
        };
    }

    public function canCollectPayments(): bool
    {
        return in_array($this->status, [self::STATUS_ISSUED, self::STATUS_PARTIAL], true) && $this->balance_due > 0;
    }
}
