<?php

namespace App\Services;

use App\Models\DiscountDefinition;
use App\Models\StudentDiscount;
use App\Models\StudentInvoice;
use App\Models\StudentInvoiceDiscount;
use Carbon\Carbon;

class InvoicePaymentTimingService
{
    public const STATUS_DISCOUNT_ELIGIBLE = 'discount_eligible';
    public const STATUS_GRACE_PERIOD = 'grace_period';
    public const STATUS_LATE_FEE_LEVEL_1 = 'late_fee_level_1';
    public const STATUS_LATE_FEE_LEVEL_2 = 'late_fee_level_2';

    public function resolveForPayment(StudentInvoice $invoice, string $paymentDate): void
    {
        $invoice->loadMissing(['student.discounts.discountDefinition', 'items.feeCategory', 'discounts', 'payments']);

        if ($invoice->payment_timing_locked_on) {
            return;
        }

        $timingStatus = $this->determineStatus($invoice, $paymentDate);

        $this->clearDynamicAdjustments($invoice);

        if ($timingStatus === self::STATUS_DISCOUNT_ELIGIBLE) {
            $this->reapplyAutoStudentDiscounts($invoice);
        } elseif ($timingStatus === self::STATUS_LATE_FEE_LEVEL_1) {
            $this->applyLateFee($invoice, (float) config('finance.payment_timing_policy.late_fee_level_1_amount'));
        } elseif ($timingStatus === self::STATUS_LATE_FEE_LEVEL_2) {
            $this->applyLateFee($invoice, (float) config('finance.payment_timing_policy.late_fee_level_2_amount'));
        }

        $invoice->forceFill([
            'payment_timing_status' => $timingStatus,
            'payment_timing_locked_on' => $paymentDate,
        ])->save();

        $invoice->load(['items.feeCategory', 'discounts', 'payments']);
        $invoice->recalculateTotals();
        $invoice->refreshPaymentStatus();
    }

    public function resetIfNoPaymentsRemain(StudentInvoice $invoice): void
    {
        $invoice->loadMissing(['payments', 'student.discounts.discountDefinition', 'items.feeCategory', 'discounts']);

        if ($invoice->payments->contains(fn ($payment) => ! $payment->isReversed())) {
            return;
        }

        $this->clearDynamicAdjustments($invoice);
        $this->reapplyAutoStudentDiscounts($invoice);

        $invoice->forceFill([
            'payment_timing_status' => null,
            'payment_timing_locked_on' => null,
        ])->save();

        $invoice->load(['items.feeCategory', 'discounts', 'payments']);
        $invoice->recalculateTotals();
        $invoice->refreshPaymentStatus();
    }

    protected function determineStatus(StudentInvoice $invoice, string $paymentDate): string
    {
        $paymentAt = Carbon::parse($paymentDate);
        $referenceDate = ($invoice->due_date ?? $invoice->issue_date)->copy();
        $referenceMonthEnd = $referenceDate->copy()->endOfMonth();
        $nextMonthStart = $referenceDate->copy()->addMonthNoOverflow()->startOfMonth();
        $graceCutoff = $nextMonthStart->copy()->day((int) config('finance.payment_timing_policy.grace_cutoff_day'));
        $lateFeeLevelOneCutoff = $nextMonthStart->copy()->day((int) config('finance.payment_timing_policy.late_fee_level_1_cutoff_day'));

        if ($paymentAt->lessThanOrEqualTo($referenceMonthEnd)) {
            return self::STATUS_DISCOUNT_ELIGIBLE;
        }

        if ($paymentAt->lessThanOrEqualTo($graceCutoff)) {
            return self::STATUS_GRACE_PERIOD;
        }

        if ($paymentAt->lessThanOrEqualTo($lateFeeLevelOneCutoff)) {
            return self::STATUS_LATE_FEE_LEVEL_1;
        }

        return self::STATUS_LATE_FEE_LEVEL_2;
    }

    protected function clearDynamicAdjustments(StudentInvoice $invoice): void
    {
        $invoice->discounts()
            ->where('notes', 'like', StudentInvoiceDiscount::AUTO_APPLIED_NOTE_PREFIX . '%')
            ->delete();

        $invoice->items()
            ->where('is_system_adjustment', true)
            ->whereIn('adjustment_code', ['late_fee_level_1', 'late_fee_level_2'])
            ->delete();
    }

    protected function reapplyAutoStudentDiscounts(StudentInvoice $invoice): void
    {
        $invoice->loadMissing(['student.discounts.discountDefinition', 'items.feeCategory', 'discounts']);

        $studentDiscounts = $invoice->student?->discounts
            ->filter(fn (StudentDiscount $studentDiscount) => $studentDiscount->status === 'active')
            ->filter(function (StudentDiscount $studentDiscount) use ($invoice): bool {
                if (! $studentDiscount->discountDefinition || $studentDiscount->discountDefinition->status !== 'active') {
                    return false;
                }

                return $studentDiscount->start_date?->lessThanOrEqualTo($invoice->issue_date)
                    && ($studentDiscount->end_date === null || $studentDiscount->end_date->greaterThanOrEqualTo($invoice->issue_date));
            })
            ->values();

        if (! $studentDiscounts || $studentDiscounts->isEmpty()) {
            return;
        }

        $eligibleItems = $invoice->items
            ->filter(fn ($item) => $item->feeCategory?->allow_discount && ! $item->is_system_adjustment)
            ->values();

        if ($eligibleItems->isEmpty()) {
            return;
        }

        foreach ($studentDiscounts as $studentDiscount) {
            $definition = $studentDiscount->discountDefinition;

            if (! $definition) {
                continue;
            }

            if ($definition->discount_type === DiscountDefinition::TYPE_PERCENTAGE) {
                foreach ($eligibleItems as $item) {
                    $existingDiscountAmount = (float) $invoice->discounts()
                        ->where('student_invoice_item_id', $item->id)
                        ->sum('amount');
                    $remainingAmount = max(0, (float) $item->amount - $existingDiscountAmount);

                    if ($remainingAmount <= 0) {
                        continue;
                    }

                    $amount = round(((float) $remainingAmount * (float) $definition->value) / 100, 2);

                    if ($amount <= 0) {
                        continue;
                    }

                    StudentInvoiceDiscount::create([
                        'student_invoice_id' => $invoice->id,
                        'student_invoice_item_id' => $item->id,
                        'discount_definition_id' => $definition->id,
                        'discount_type' => $definition->discount_type,
                        'value' => $definition->value,
                        'amount' => min($amount, $remainingAmount),
                        'reason' => $definition->name,
                        'notes' => $this->buildAutoDiscountNotes($studentDiscount),
                    ]);
                }

                continue;
            }

            $remainingFixedAmount = round((float) $definition->value, 2);

            foreach ($eligibleItems as $item) {
                if ($remainingFixedAmount <= 0) {
                    break;
                }

                $existingDiscountAmount = (float) $invoice->discounts()
                    ->where('student_invoice_item_id', $item->id)
                    ->sum('amount');
                $remainingItemAmount = max(0, (float) $item->amount - $existingDiscountAmount);

                if ($remainingItemAmount <= 0) {
                    continue;
                }

                $appliedAmount = min($remainingFixedAmount, $remainingItemAmount);

                StudentInvoiceDiscount::create([
                    'student_invoice_id' => $invoice->id,
                    'student_invoice_item_id' => $item->id,
                    'discount_definition_id' => $definition->id,
                    'discount_type' => $definition->discount_type,
                    'value' => $definition->value,
                    'amount' => $appliedAmount,
                    'reason' => $definition->name,
                    'notes' => $this->buildAutoDiscountNotes($studentDiscount),
                ]);

                $remainingFixedAmount = round($remainingFixedAmount - $appliedAmount, 2);
            }
        }
    }

    protected function applyLateFee(StudentInvoice $invoice, float $amount): void
    {
        $hasDiscountableItems = $invoice->items
            ->contains(fn ($item) => $item->feeCategory?->allow_discount && ! $item->is_system_adjustment);

        if (! $hasDiscountableItems || $amount <= 0) {
            return;
        }

        $invoice->items()->create([
            'fee_structure_id' => null,
            'fee_item_id' => null,
            'fee_category_id' => null,
            'description' => 'Late Fee',
            'billing_cycle' => 'one-time',
            'installment_no' => null,
            'quantity' => 1,
            'unit_price' => $amount,
            'amount' => $amount,
            'due_date' => $invoice->due_date,
            'remarks' => 'System-generated late fee based on invoice payment timing.',
            'is_system_adjustment' => true,
            'adjustment_code' => $amount === (float) config('finance.payment_timing_policy.late_fee_level_2_amount')
                ? 'late_fee_level_2'
                : 'late_fee_level_1',
        ]);
    }

    protected function buildAutoDiscountNotes(StudentDiscount $studentDiscount): string
    {
        $note = StudentInvoiceDiscount::AUTO_APPLIED_NOTE_PREFIX;

        if ($studentDiscount->notes) {
            return $note . ' ' . $studentDiscount->notes;
        }

        return $note;
    }
}
