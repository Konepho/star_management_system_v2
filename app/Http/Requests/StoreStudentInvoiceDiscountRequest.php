<?php

namespace App\Http\Requests;

use App\Models\DiscountDefinition;
use App\Models\StudentInvoice;
use App\Models\StudentInvoiceDiscount;
use App\Models\StudentInvoiceItem;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreStudentInvoiceDiscountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'student_invoice_id' => ['required', 'exists:student_invoices,id'],
            'student_invoice_item_id' => ['required', 'exists:student_invoice_items,id'],
            'discount_definition_id' => ['required', 'exists:discount_definitions,id'],
            'notes' => ['nullable', 'string'],
        ];
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            /** @var StudentInvoice|null $invoice */
            $invoice = StudentInvoice::query()
                ->with(['items.feeCategory', 'discounts', 'payments'])
                ->find($this->input('student_invoice_id'));

            /** @var StudentInvoiceItem|null $item */
            $item = StudentInvoiceItem::query()
                ->with('feeCategory')
                ->find($this->input('student_invoice_item_id'));

            /** @var DiscountDefinition|null $discountDefinition */
            $discountDefinition = DiscountDefinition::query()->find($this->input('discount_definition_id'));

            if (! $invoice || ! $item || ! $discountDefinition) {
                return;
            }

            if ((int) $item->student_invoice_id !== (int) $invoice->id) {
                $validator->errors()->add('student_invoice_item_id', 'The selected invoice item does not belong to this invoice.');
                return;
            }

            if (! $item->feeCategory?->allow_discount) {
                $validator->errors()->add('student_invoice_item_id', 'Discounts are not allowed for this invoice item.');
                return;
            }

            $existingItemDiscounts = $invoice->discounts
                ->where('student_invoice_item_id', $item->id)
                ->sum('amount');

            if ($discountDefinition->status !== 'active') {
                $validator->errors()->add('discount_definition_id', 'Only active discount definitions can be applied.');
                return;
            }

            $requestedAmount = $discountDefinition->discount_type === StudentInvoiceDiscount::TYPE_PERCENTAGE
                ? round(((float) $item->amount * (float) $discountDefinition->value) / 100, 2)
                : round((float) $discountDefinition->value, 2);

            if (($existingItemDiscounts + $requestedAmount) > (float) $item->amount) {
                $validator->errors()->add('discount_definition_id', 'Discount exceeds the remaining discountable amount for this item.');
                return;
            }

            $projectedNetTotal = $invoice->subtotal_amount - ($invoice->discount_amount + $requestedAmount);
            if ($projectedNetTotal < $invoice->paid_amount) {
                $validator->errors()->add('discount_definition_id', 'Discount would reduce the invoice below the amount already paid.');
            }
        });
    }
}
