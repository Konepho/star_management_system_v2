<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreStudentInvoiceDiscountRequest;
use App\Models\DiscountDefinition;
use App\Models\StudentInvoice;
use App\Models\StudentInvoiceDiscount;
use App\Services\AuditLogService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class StudentInvoiceDiscountController extends Controller
{
    public function store(StoreStudentInvoiceDiscountRequest $request): RedirectResponse
    {
        /** @var StudentInvoice $invoice */
        $invoice = StudentInvoice::query()
            ->with(['items', 'discounts', 'payments'])
            ->findOrFail($request->validated('student_invoice_id'));

        /** @var DiscountDefinition $discountDefinition */
        $discountDefinition = DiscountDefinition::query()->findOrFail($request->validated('discount_definition_id'));
        $item = $invoice->items->firstWhere('id', (int) $request->validated('student_invoice_item_id'));
        $amount = $discountDefinition->discount_type === StudentInvoiceDiscount::TYPE_PERCENTAGE
            ? round(((float) $item->amount * (float) $discountDefinition->value) / 100, 2)
            : round((float) $discountDefinition->value, 2);

        $studentInvoiceDiscount = DB::transaction(function () use ($request, $invoice, $discountDefinition, $amount): StudentInvoiceDiscount {
            $studentInvoiceDiscount = StudentInvoiceDiscount::create([
                'student_invoice_id' => $invoice->id,
                'student_invoice_item_id' => $request->validated('student_invoice_item_id'),
                'discount_definition_id' => $discountDefinition->id,
                'discount_type' => $discountDefinition->discount_type,
                'value' => $discountDefinition->value,
                'amount' => $amount,
                'reason' => $discountDefinition->name,
                'notes' => $request->validated('notes'),
            ]);

            $invoice->load(['discounts.discountDefinition', 'payments', 'items']);
            $invoice->recalculateTotals();
            $invoice->refreshPaymentStatus();
            return $studentInvoiceDiscount;
        });

        app(AuditLogService::class)->log(
            'finance',
            'student_invoice_discounts',
            'created',
            $studentInvoiceDiscount,
            [],
            app(AuditLogService::class)->modelState($studentInvoiceDiscount),
            'Applied invoice discount #' . $studentInvoiceDiscount->id . '.',
            ['invoice_no' => $invoice->invoice_no],
        );

        return redirect()
            ->route('student-invoices.show', $invoice)
            ->with('status', 'Discount applied successfully.');
    }

    public function destroy(StudentInvoiceDiscount $studentInvoiceDiscount): RedirectResponse
    {
        $invoice = $studentInvoiceDiscount->invoice()->first();
        $auditLogService = app(AuditLogService::class);
        $beforeState = $auditLogService->modelState($studentInvoiceDiscount);

        DB::transaction(function () use ($studentInvoiceDiscount, $invoice): void {
            $studentInvoiceDiscount->delete();

            if ($invoice) {
                $invoice->load(['discounts', 'payments', 'items']);
                $invoice->recalculateTotals();
                $invoice->refreshPaymentStatus();
            }
        });

        $auditLogService->log(
            'finance',
            'student_invoice_discounts',
            'deleted',
            $studentInvoiceDiscount,
            $beforeState,
            [],
            'Removed invoice discount #' . $studentInvoiceDiscount->id . '.',
            ['invoice_no' => $invoice?->invoice_no],
        );

        return redirect()
            ->route('student-invoices.show', $invoice)
            ->with('status', 'Discount removed successfully.');
    }
}
