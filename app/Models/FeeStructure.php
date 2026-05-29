<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FeeStructure extends Model
{
    use HasFactory;

    protected $fillable = [
        'academic_year_id',
        'grade_id',
        'grade_group',
        'fee_category_id',
        'amount',
        'billing_cycle',
        'is_optional',
        'status',
        'remarks',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'is_optional' => 'boolean',
        ];
    }

    public static function billingCycleOptions(): array
    {
        return [
            'monthly' => 'Monthly',
            'quarterly' => 'Quarterly',
            'annual' => 'Annual',
            'one-time' => 'One-Time',
            'installment' => 'Installment Plan',
        ];
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function grade(): BelongsTo
    {
        return $this->belongsTo(Grade::class);
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class);
    }

    public function installments(): HasMany
    {
        return $this->hasMany(FeeInstallment::class)->orderBy('installment_no');
    }

    public function feePlans(): BelongsToMany
    {
        return $this->belongsToMany(FeePlan::class, 'fee_plan_fee_structure')->withTimestamps();
    }

    public function getScopeLabelAttribute(): string
    {
        if ($this->grade) {
            return $this->grade->name;
        }

        if ($this->grade_group) {
            return Grade::groupOptions()[$this->grade_group] ?? ucfirst($this->grade_group);
        }

        return 'All Grades';
    }

    public function getBillingCycleLabelAttribute(): string
    {
        return self::billingCycleOptions()[$this->billing_cycle] ?? ucfirst(str_replace('-', ' ', $this->billing_cycle));
    }
}
