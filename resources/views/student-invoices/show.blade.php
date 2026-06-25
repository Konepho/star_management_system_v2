<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="text-xl font-semibold leading-tight text-slate-900">{{ $invoice->invoice_no }}</h2>
                <p class="mt-1 text-sm text-slate-700">Student invoice summary and generated line items.</p>
            </div>
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center">
                @if ($invoice->canCollectPayments())
                    <a href="#collect-payment" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                        Collect Payment
                    </a>
                @endif
                <a href="{{ route('student-invoices.print', $invoice) }}" target="_blank" rel="noopener" class="inline-flex items-center justify-center rounded-md bg-slate-900 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-slate-800">
                    Print Invoice
                </a>
                <a href="{{ route('student-invoices.index') }}" class="inline-flex items-center justify-center rounded-md border border-slate-300 px-4 py-2 text-sm font-medium text-slate-700 hover:bg-slate-50">
                    Back to Invoices
                </a>
            </div>
        </div>
</x-slot>

    @php($invoiceStudentName = trim(collect([
        trim((string) $invoice->student?->preferred_name),
        trim((string) ($invoice->student?->name_en ?: $invoice->student?->full_name)),
        trim((string) $invoice->student?->name_mm),
    ])->filter()->unique()->implode(' / ')) ?: '—')

    <div class="py-12">
        <div class="mx-auto max-w-7xl space-y-6 sm:px-6 lg:px-8">
            @if (session('status'))
                <div class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            @if ($errors->has('invoice'))
                <div class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-3 text-sm text-rose-700">{{ $errors->first('invoice') }}</div>
            @endif

            @if ($invoice->canCollectPayments())
                <div class="flex flex-col gap-3 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <div class="text-sm font-semibold text-emerald-900">This invoice is ready for payment collection.</div>
                        <div class="mt-1 text-sm text-emerald-700">Balance due: {{ number_format((float) $invoice->balance_due, 2) }}</div>
                    </div>
                    <a href="#collect-payment" class="inline-flex items-center justify-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">
                        Go to Payment Form
                    </a>
                </div>
            @endif

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-1">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Invoice Details</h3>
                        <div class="mt-4 flex flex-wrap gap-3">
                            @if ($invoice->status === \App\Models\StudentInvoice::STATUS_DRAFT)
                                <form method="POST" action="{{ route('student-invoices.update-status', $invoice) }}">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="issue">
                                    <button type="submit" class="inline-flex items-center rounded-md bg-emerald-600 px-4 py-2 text-sm font-semibold text-white shadow-sm hover:bg-emerald-500">Issue Invoice</button>
                                </form>
                                <form method="POST" action="{{ route('student-invoices.update-status', $invoice) }}" onsubmit="return confirm('Cancel this draft invoice?');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="cancel">
                                    <button type="submit" class="inline-flex items-center rounded-md border border-amber-300 px-4 py-2 text-sm font-semibold text-amber-800 hover:bg-amber-50">Cancel Draft</button>
                                </form>
                            @endif
                            @if ($invoice->status === \App\Models\StudentInvoice::STATUS_ISSUED && $invoice->payments->isEmpty())
                                <form method="POST" action="{{ route('student-invoices.update-status', $invoice) }}" onsubmit="return confirm('Void this issued invoice?');">
                                    @csrf
                                    @method('PATCH')
                                    <input type="hidden" name="action" value="void">
                                    <button type="submit" class="inline-flex items-center rounded-md border border-rose-300 px-4 py-2 text-sm font-semibold text-rose-800 hover:bg-rose-50">Void Invoice</button>
                                </form>
                            @endif
                        </div>
                        <dl class="mt-4 space-y-3 text-sm">
                            <div>
                                <dt class="font-medium text-slate-500">Student</dt>
                                <dd class="text-slate-900">{{ $invoiceStudentName }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Admission No</dt>
                                <dd class="text-slate-900">{{ $invoice->student?->admission_no }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Class</dt>
                                <dd class="text-slate-900">
                                    {{ $invoice->grade?->name ?? '—' }}
                                    @if ($invoice->section)
                                        - {{ $invoice->section->name }}
                                    @endif
                                </dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Fee Plan Snapshot</dt>
                                <dd class="text-slate-900">{{ $invoice->feePlan?->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Billing Period</dt>
                                <dd class="text-slate-900">{{ $invoice->billing_period_label }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Billing Period Type</dt>
                                <dd class="text-slate-900">{{ \App\Models\StudentInvoice::billingPeriodTypeOptions()[$invoice->billing_period_type] ?? ucfirst((string) $invoice->billing_period_type) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Academic Year</dt>
                                <dd class="text-slate-900">{{ $invoice->academicYear?->name }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Issue Date</dt>
                                <dd class="text-slate-900">{{ $invoice->issue_date?->format('Y-m-d') }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Due Date</dt>
                                <dd class="text-slate-900">{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Status</dt>
                                <dd class="text-slate-900">{{ ucfirst($invoice->status) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Issued At</dt>
                                <dd class="text-slate-900">{{ $invoice->issued_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Cancelled At</dt>
                                <dd class="text-slate-900">{{ $invoice->cancelled_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Voided At</dt>
                                <dd class="text-slate-900">{{ $invoice->voided_at?->format('Y-m-d H:i') ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Payment Timing Rule</dt>
                                <dd class="text-slate-900">{{ $invoice->payment_timing_status_label ?? 'Will be decided on first payment' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Subtotal</dt>
                                <dd class="text-slate-900">{{ number_format((float) $invoice->subtotal_amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Discounts</dt>
                                <dd class="text-slate-900">{{ number_format((float) $invoice->discount_amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Total Amount</dt>
                                <dd class="text-lg font-semibold text-slate-900">{{ number_format((float) $invoice->total_amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Paid Amount</dt>
                                <dd class="text-slate-900">{{ number_format((float) $invoice->paid_amount, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Balance Due</dt>
                                <dd class="text-lg font-semibold text-rose-700">{{ number_format((float) $invoice->balance_due, 2) }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-slate-500">Notes</dt>
                                <dd class="text-slate-900">{{ $invoice->notes ?: '—' }}</dd>
                            </div>
                        </dl>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-2">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Invoice Items</h3>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Description</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Category</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Cycle</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Qty</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Unit Price</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Due Date</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Discount</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Net</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @foreach ($invoice->items as $item)
                                        <tr>
                                            <td class="px-4 py-4 text-sm text-slate-900">
                                                {{ $item->description }}
                                                @if ($item->remarks)
                                                    <div class="text-xs text-slate-500">{{ $item->remarks }}</div>
                                                @endif
                                                @if ($item->feeItem)
                                                    <div class="text-xs text-slate-500">Material item</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $item->feeCategory?->name ?? '—' }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">
                                                {{ \App\Models\FeeStructure::billingCycleOptions()[$item->billing_cycle] ?? ucfirst($item->billing_cycle) }}
                                                @if ($item->installment_no)
                                                    <div class="text-xs text-slate-500">Installment {{ $item->installment_no }}</div>
                                                @endif
                                                @if ($item->feeCategory?->allow_discount)
                                                    <div class="text-xs text-emerald-600">Discount allowed</div>
                                                @else
                                                    <div class="text-xs text-rose-600">No discount</div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-right text-sm text-slate-600">{{ $item->quantity ?? 1 }}</td>
                                            <td class="px-4 py-4 text-right text-sm text-slate-600">{{ number_format((float) ($item->unit_price ?? $item->amount), 2) }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $item->due_date?->format('Y-m-d') ?? '—' }}</td>
                                            <td class="px-4 py-4 text-right text-sm font-medium text-emerald-700">
                                                {{ number_format((float) $item->discount_amount, 2) }}
                                                @if ($item->discounts->isNotEmpty())
                                                    <div class="text-xs text-slate-500">
                                                        @foreach ($item->discounts as $itemDiscount)
                                                            <div>
                                                                {{ $itemDiscount->discountDefinition?->name ?? $itemDiscount->reason }}
                                                                @if ($itemDiscount->is_auto_applied)
                                                                    <span class="text-emerald-600">(Auto)</span>
                                                                @endif
                                                            </div>
                                                        @endforeach
                                                    </div>
                                                @endif
                                            </td>
                                            <td class="px-4 py-4 text-right text-sm font-medium text-slate-900">{{ number_format((float) $item->amount, 2) }}</td>
                                            <td class="px-4 py-4 text-right text-sm font-semibold text-slate-900">{{ number_format((float) $item->net_amount, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                                <tfoot class="bg-slate-50">
                                    <tr>
                                        <td colspan="7" class="px-4 py-3 text-right text-sm font-semibold text-slate-900">Subtotal</td>
                                        <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">{{ number_format((float) $invoice->subtotal_amount, 2) }}</td>
                                        <td class="px-4 py-3"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="7" class="px-4 py-3 text-right text-sm font-semibold text-emerald-700">Total Discount</td>
                                        <td class="px-4 py-3 text-right text-sm font-semibold text-emerald-700">{{ number_format((float) $invoice->discount_amount, 2) }}</td>
                                        <td class="px-4 py-3"></td>
                                    </tr>
                                    <tr>
                                        <td colspan="8" class="px-4 py-3 text-right text-sm font-semibold text-slate-900">Net Total</td>
                                        <td class="px-4 py-3 text-right text-sm font-semibold text-slate-900">{{ number_format((float) $invoice->total_amount, 2) }}</td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-1">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Auto-Applied Student Discounts</h3>
                        <div class="mt-4 space-y-3">
                            @php($autoDiscounts = $invoice->discounts->where('is_auto_applied', true))
                            @forelse ($autoDiscounts->groupBy('discount_definition_id') as $discountGroup)
                                @php($firstDiscount = $discountGroup->first())
                                <div class="rounded-lg border border-emerald-200 bg-emerald-50 p-4">
                                    <div class="text-sm font-semibold text-slate-900">{{ $firstDiscount->discountDefinition?->name ?? $firstDiscount->reason }}</div>
                                    <div class="mt-1 text-xs text-slate-600">
                                        {{ ucfirst($firstDiscount->discount_type) }}:
                                        {{ number_format((float) $firstDiscount->value, 2) }}{{ $firstDiscount->discount_type === \App\Models\StudentInvoiceDiscount::TYPE_PERCENTAGE ? '%' : '' }}
                                    </div>
                                    <div class="mt-1 text-xs text-slate-600">{{ $discountGroup->count() }} invoice line(s)</div>
                                    <div class="mt-2 text-sm font-semibold text-emerald-700">{{ number_format((float) $discountGroup->sum('amount'), 2) }}</div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500">
                                    No recurring student discounts were auto-applied on this invoice.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-1">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Apply Discount</h3>
                        <form method="POST" action="{{ route('student-invoice-discounts.store') }}" class="mt-4 space-y-4">
                            @csrf
                            <input type="hidden" name="student_invoice_id" value="{{ $invoice->id }}">

                            <div>
                                <x-input-label for="student_invoice_item_id" :value="__('Discount Item')" />
                                <select id="student_invoice_item_id" name="student_invoice_item_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">Select eligible item</option>
                                    @foreach ($invoice->items->filter(fn ($item) => $item->feeCategory?->allow_discount) as $item)
                                        <option value="{{ $item->id }}" @selected((string) old('student_invoice_item_id') === (string) $item->id)>
                                            {{ $item->description }} - {{ number_format((float) $item->amount, 2) }}
                                        </option>
                                    @endforeach
                                </select>
                                <x-input-error class="mt-2" :messages="$errors->get('student_invoice_item_id')" />
                            </div>

                            <div>
                                <x-input-label for="discount_definition_id" :value="__('Discount Definition')" />
                                <select id="discount_definition_id" name="discount_definition_id" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                    <option value="">Select saved discount</option>
                                    @foreach ($discountDefinitions as $discountDefinition)
                                        <option value="{{ $discountDefinition->id }}" @selected((string) old('discount_definition_id') === (string) $discountDefinition->id)>
                                            {{ $discountDefinition->name }} - {{ \App\Models\DiscountDefinition::typeOptions()[$discountDefinition->discount_type] ?? ucfirst($discountDefinition->discount_type) }} ({{ number_format((float) $discountDefinition->value, 2) }}{{ $discountDefinition->discount_type === \App\Models\DiscountDefinition::TYPE_PERCENTAGE ? '%' : '' }})
                                        </option>
                                    @endforeach
                                </select>
                                <p class="mt-1 text-xs text-slate-500">Create reusable discounts from the Discount Definitions module, then choose them here.</p>
                                <x-input-error class="mt-2" :messages="$errors->get('discount_definition_id')" />
                            </div>

                            <div>
                                <x-input-label for="discount_notes" :value="__('Notes')" />
                                <textarea id="discount_notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                                <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                            </div>

                            <x-primary-button>Apply Discount</x-primary-button>
                        </form>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-1" id="collect-payment">
                    <div class="p-6">
                        <h3 class="text-base font-semibold text-slate-900">Collect Payment</h3>
                        @if ($invoice->balance_due <= 0)
                            <div class="mt-4 rounded-lg border border-emerald-200 bg-emerald-50 p-4 text-sm text-emerald-700">
                                This invoice is fully paid.
                            </div>
                        @elseif (in_array($invoice->status, ['draft', 'cancelled', 'void'], true))
                            <div class="mt-4 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm text-amber-700">
                                Payments are disabled for {{ $invoice->status }} invoices.
                            </div>
                        @else
                            <form method="POST" action="{{ route('student-payments.store') }}" class="mt-4 space-y-4">
                                @csrf
                                <input type="hidden" name="student_invoice_id" value="{{ $invoice->id }}">

                                <div>
                                    <x-input-label for="payment_date" :value="__('Payment Date')" />
                                    <x-text-input id="payment_date" name="payment_date" type="date" class="mt-1 block w-full" :value="old('payment_date', now()->format('Y-m-d'))" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('payment_date')" />
                                </div>

                                <div>
                                    <x-input-label for="amount" :value="__('Amount')" />
                                    <x-text-input id="amount" name="amount" type="number" min="0.01" step="0.01" class="mt-1 block w-full" :value="old('amount', number_format((float) $invoice->balance_due, 2, '.', ''))" required />
                                    <x-input-error class="mt-2" :messages="$errors->get('amount')" />
                                </div>

                                <div>
                                    <x-input-label for="payment_method" :value="__('Payment Method')" />
                                    @php($selectedMethod = old('payment_method', \App\Models\StudentPayment::METHOD_CASH))
                                    <select id="payment_method" name="payment_method" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500" required>
                                        @foreach (\App\Models\StudentPayment::methodOptions() as $methodValue => $methodLabel)
                                            <option value="{{ $methodValue }}" @selected($selectedMethod === $methodValue)>{{ $methodLabel }}</option>
                                        @endforeach
                                    </select>
                                    <x-input-error class="mt-2" :messages="$errors->get('payment_method')" />
                                </div>

                                <div>
                                    <x-input-label for="reference_no" :value="__('Reference No')" />
                                    <x-text-input id="reference_no" name="reference_no" type="text" class="mt-1 block w-full" :value="old('reference_no')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('reference_no')" />
                                </div>

                                <div>
                                    <x-input-label for="notes" :value="__('Notes')" />
                                    <textarea id="notes" name="notes" rows="3" class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('notes') }}</textarea>
                                    <x-input-error class="mt-2" :messages="$errors->get('notes')" />
                                    <x-input-error class="mt-2" :messages="$errors->get('student_invoice_id')" />
                                </div>

                                <x-primary-button>Collect Payment</x-primary-button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-1">
                    <div class="p-6">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold text-slate-900">Discount History</h3>
                        </div>
                        <div class="mt-4 space-y-3">
                            @forelse ($invoice->discounts as $discount)
                                <div class="rounded-lg border border-slate-200 p-4">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <div class="text-sm font-semibold text-slate-900">{{ $discount->item?->description ?? 'Invoice item' }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ ucfirst($discount->discount_type) }}: {{ number_format((float) $discount->value, 2) }}{{ $discount->discount_type === \App\Models\StudentInvoiceDiscount::TYPE_PERCENTAGE ? '%' : '' }}</div>
                                            <div class="mt-1 text-xs text-slate-500">{{ $discount->discountDefinition?->name ?? $discount->reason }}</div>
                                            @if ($discount->is_auto_applied)
                                                <div class="mt-1 text-xs font-medium text-emerald-600">Auto-applied from student discount assignment</div>
                                            @endif
                                            @if ($discount->discountDefinition?->code)
                                                <div class="mt-1 text-xs text-slate-500">Code: {{ $discount->discountDefinition->code }}</div>
                                            @endif
                                            @if ($discount->notes)
                                                <div class="mt-1 text-xs text-slate-500">{{ $discount->notes }}</div>
                                            @endif
                                        </div>
                                        <div class="text-right">
                                            <div class="text-sm font-semibold text-emerald-700">{{ number_format((float) $discount->amount, 2) }}</div>
                                            <form method="POST" action="{{ route('student-invoice-discounts.destroy', $discount) }}" class="mt-2" onsubmit="return confirm('Remove this discount?');">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="text-xs font-medium text-rose-700 hover:text-rose-600">Remove</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="rounded-lg border border-dashed border-slate-300 p-4 text-sm text-slate-500">
                                    No discounts applied yet.
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="overflow-hidden bg-white shadow-sm sm:rounded-lg lg:col-span-1">
                    <div class="p-6">
                        <div class="flex items-center justify-between gap-3">
                            <h3 class="text-base font-semibold text-slate-900">Payment History</h3>
                            <a href="{{ route('student-payments.index') }}" class="text-sm font-medium text-sky-700 hover:text-sky-600">View all payments</a>
                        </div>
                        <div class="mt-4 overflow-x-auto">
                            <table class="min-w-full divide-y divide-slate-200">
                                <thead class="bg-slate-50">
                                    <tr>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Receipt No</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Method</th>
                                        <th class="px-4 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Reference</th>
                                        <th class="px-4 py-3 text-right text-xs font-semibold uppercase tracking-wider text-slate-500">Amount</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-100 bg-white">
                                    @forelse ($invoice->payments as $payment)
                                        <tr>
                                            <td class="px-4 py-4 text-sm font-medium text-slate-900">
                                                <a href="{{ route('student-payments.show', $payment) }}" class="text-sky-700 hover:text-sky-600">{{ $payment->receipt_no }}</a>
                                            </td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $payment->payment_date?->format('Y-m-d') }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ \App\Models\StudentPayment::methodOptions()[$payment->payment_method] ?? strtoupper($payment->payment_method) }}</td>
                                            <td class="px-4 py-4 text-sm text-slate-600">{{ $payment->reference_no ?: '—' }}</td>
                                            <td class="px-4 py-4 text-right text-sm font-medium text-slate-900">{{ number_format((float) $payment->amount, 2) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="px-4 py-6 text-center text-sm text-slate-500">No payments collected yet.</td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
