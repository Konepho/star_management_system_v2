<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $invoice->invoice_no }} - Printable Invoice</title>
    <style>
        :root {
            color-scheme: light;
            --paper: #ffffff;
            --page: #f3f4f6;
            --ink: #111111;
            --muted: #5f5f5f;
            --line: #cfcfcf;
            --line-strong: #999999;
            --soft: #f7f7f7;
        }

        * {
            box-sizing: border-box;
        }

        @page {
            size: A5 portrait;
            margin: 8mm;
        }

        body {
            margin: 0;
            background: var(--page);
            color: var(--ink);
            font-family: Arial, Helvetica, sans-serif;
            line-height: 1.35;
        }

        .screen-toolbar {
            max-width: 168mm;
            margin: 18px auto 0;
            padding: 0 12px;
            display: flex;
            justify-content: flex-end;
            gap: 10px;
        }

        .screen-toolbar a,
        .screen-toolbar button {
            border: 1px solid var(--line-strong);
            background: #fff;
            color: #111;
            border-radius: 8px;
            padding: 9px 14px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
        }

        .sheet {
            width: 148mm;
            min-height: 210mm;
            margin: 12px auto 24px;
            background: var(--paper);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.08);
            padding: 10mm 10mm 8mm;
        }

        .header {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            align-items: flex-start;
            border-bottom: 2px solid #111;
            padding-bottom: 8px;
        }

        .school {
            display: flex;
            gap: 10px;
            align-items: flex-start;
        }

        .school-logo {
            width: 38px;
            height: 38px;
            object-fit: contain;
        }

        .school-badge {
            width: 38px;
            height: 38px;
            border: 1px solid #111;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 13px;
            font-weight: 700;
        }

        .school-name {
            margin: 0;
            font-size: 19px;
            font-weight: 700;
            letter-spacing: 0.01em;
        }

        .school-meta {
            margin-top: 3px;
            font-size: 11px;
            color: var(--muted);
        }

        .invoice-box {
            min-width: 44mm;
            text-align: right;
        }

        .invoice-label {
            margin: 0;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
            color: var(--muted);
        }

        .invoice-no {
            margin: 4px 0 6px;
            font-size: 18px;
            font-weight: 700;
            word-break: break-word;
        }

        .status {
            display: inline-block;
            border: 1px solid #111;
            padding: 3px 8px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .top-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 10px;
            margin-top: 10px;
        }

        .box {
            border: 1px solid var(--line);
            padding: 8px 9px;
        }

        .box-title {
            margin: 0 0 6px;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.12em;
        }

        .meta-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 5px 10px;
        }

        .meta-item {
            min-width: 0;
        }

        .meta-key {
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            color: var(--muted);
            letter-spacing: 0.08em;
        }

        .meta-value {
            margin-top: 2px;
            font-size: 12px;
            color: var(--ink);
            word-break: break-word;
        }

        .items {
            width: 100%;
            margin-top: 10px;
            border-collapse: collapse;
        }

        .items th,
        .items td {
            border: 1px solid var(--line);
            padding: 6px 7px;
            text-align: left;
            vertical-align: top;
            font-size: 11px;
        }

        .items thead th {
            background: #efefef;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.08em;
        }

        .items .right {
            text-align: right;
            white-space: nowrap;
        }

        .item-desc {
            font-weight: 700;
        }

        .item-note {
            margin-top: 2px;
            font-size: 10px;
            color: var(--muted);
        }

        .bottom-grid {
            display: grid;
            grid-template-columns: 1.2fr 0.8fr;
            gap: 10px;
            margin-top: 10px;
            align-items: start;
        }

        .discount-list {
            display: grid;
            gap: 6px;
        }

        .discount-row {
            display: flex;
            justify-content: space-between;
            gap: 10px;
            border: 1px solid var(--line);
            padding: 6px 7px;
            font-size: 11px;
        }

        .discount-name {
            font-weight: 700;
        }

        .discount-note {
            margin-top: 2px;
            font-size: 10px;
            color: var(--muted);
        }

        .empty-note {
            font-size: 11px;
            color: var(--muted);
        }

        .totals-table {
            width: 100%;
            border-collapse: collapse;
        }

        .totals-table td {
            border: 1px solid var(--line);
            padding: 7px 8px;
            font-size: 11px;
        }

        .totals-table td:last-child {
            text-align: right;
            white-space: nowrap;
            font-weight: 700;
        }

        .totals-table .grand td {
            border-width: 2px;
            font-size: 13px;
            font-weight: 700;
        }

        .footer {
            margin-top: 10px;
            padding-top: 7px;
            border-top: 1px solid var(--line);
            display: flex;
            justify-content: space-between;
            gap: 10px;
            font-size: 10px;
            color: var(--muted);
        }

        @media print {
            body {
                background: #fff;
            }

            .screen-toolbar {
                display: none;
            }

            .sheet {
                margin: 0;
                width: auto;
                min-height: auto;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="screen-toolbar">
        <a href="{{ route('student-invoices.show', $invoice) }}">Back to Invoice</a>
        <button type="button" onclick="window.print()">Print Invoice</button>
    </div>

    <div class="sheet">
        <section class="header">
            <div class="school">
                @if ($invoiceSettings['school_logo_data_url'])
                    <img src="{{ $invoiceSettings['school_logo_data_url'] }}" alt="School Logo" class="school-logo">
                @else
                    <div class="school-badge">SS</div>
                @endif
                <div>
                    <p class="school-name">{{ $invoiceSettings['school_name'] }}</p>
                    <div class="school-meta">
                        @if ($invoiceSettings['school_phone'])
                            <div>{{ $invoiceSettings['school_phone'] }}</div>
                        @endif
                        @if ($invoiceSettings['school_email'])
                            <div>{{ $invoiceSettings['school_email'] }}</div>
                        @endif
                        @if ($invoiceSettings['school_address'])
                            <div>{{ $invoiceSettings['school_address'] }}</div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="invoice-box">
                <p class="invoice-label">Official Student Invoice</p>
                <div class="invoice-no">{{ $invoice->invoice_no }}</div>
                <span class="status">{{ \App\Models\StudentInvoice::statusOptions()[$invoice->status] ?? ucfirst($invoice->status) }}</span>
            </div>
        </section>

        <section class="top-grid">
            <div class="box">
                <p class="box-title">Student Details</p>
                <div class="meta-grid">
                    <div class="meta-item">
                        <div class="meta-key">Student Name</div>
                        <div class="meta-value">{{ $invoiceSettings['student_display_name'] }}</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-key">Student ID</div>
                        <div class="meta-value">{{ $invoice->student?->admission_no ?? '—' }}</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-key">Class</div>
                        <div class="meta-value">
                            {{ $invoice->grade?->name ?? '—' }}
                            @if ($invoice->section)
                                - {{ $invoice->section->name }}
                            @endif
                        </div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-key">Fee Plan</div>
                        <div class="meta-value">{{ $invoice->feePlan?->name ?? '—' }}</div>
                    </div>
                </div>
            </div>

            <div class="box">
                <p class="box-title">Invoice Details</p>
                <div class="meta-grid">
                    <div class="meta-item">
                        <div class="meta-key">Academic Year</div>
                        <div class="meta-value">{{ $invoice->academicYear?->name ?? '—' }}</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-key">Billing Period</div>
                        <div class="meta-value">{{ $invoice->billing_period_label }}</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-key">Issue Date</div>
                        <div class="meta-value">{{ $invoice->issue_date?->format('Y-m-d') ?? '—' }}</div>
                    </div>
                    <div class="meta-item">
                        <div class="meta-key">Due Date</div>
                        <div class="meta-value">{{ $invoice->due_date?->format('Y-m-d') ?? '—' }}</div>
                    </div>
                </div>
            </div>
        </section>

        <table class="items">
            <thead>
                <tr>
                    <th>Description</th>
                    <th>Category</th>
                    <th class="right">Qty</th>
                    <th class="right">Unit Price</th>
                    <th class="right">Discount</th>
                    <th class="right">Amount</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($invoice->items as $item)
                    <tr>
                        <td>
                            <div class="item-desc">{{ $item->description }}</div>
                            @if ($item->remarks)
                                <div class="item-note">{{ $item->remarks }}</div>
                            @endif
                            @if ($item->installment_no)
                                <div class="item-note">Installment {{ $item->installment_no }}</div>
                            @endif
                        </td>
                        <td>{{ $item->feeCategory?->name ?? '—' }}</td>
                        <td class="right">{{ $item->quantity ?? 1 }}</td>
                        <td class="right">{{ number_format((float) ($item->unit_price ?? $item->amount), 2) }}</td>
                        <td class="right">{{ number_format((float) $item->discount_amount, 2) }}</td>
                        <td class="right">{{ number_format((float) $item->net_amount, 2) }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <section class="bottom-grid">
            <div class="box">
                <p class="box-title">Discounts & Notes</p>
                @if ($invoice->discounts->isNotEmpty())
                    <div class="discount-list">
                        @foreach ($invoice->discounts as $discount)
                            <div class="discount-row">
                                <div>
                                    <div class="discount-name">{{ $discount->discountDefinition?->name ?? $discount->reason }}</div>
                                    <div class="discount-note">
                                        @if ($discount->item)
                                            {{ $discount->item->description }}
                                        @else
                                            Invoice adjustment
                                        @endif
                                    </div>
                                </div>
                                <div>{{ number_format((float) $discount->amount, 2) }}</div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-note">No discounts applied.</div>
                @endif

                @if ($invoice->notes)
                    <div style="margin-top: 8px;">
                        <div class="meta-key">Notes</div>
                        <div class="meta-value">{{ $invoice->notes }}</div>
                    </div>
                @endif
            </div>

            <div class="box">
                <p class="box-title">Summary</p>
                <table class="totals-table">
                    <tr>
                        <td>Subtotal</td>
                        <td>{{ number_format((float) $invoice->subtotal_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Total Discount</td>
                        <td>{{ number_format((float) $invoice->discount_amount, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Paid Amount</td>
                        <td>{{ number_format((float) $invoice->paid_amount, 2) }}</td>
                    </tr>
                    <tr class="grand">
                        <td>Balance Due</td>
                        <td>{{ number_format((float) $invoice->balance_due, 2) }}</td>
                    </tr>
                </table>
            </div>
        </section>

        <section class="footer">
            <div>Generated: {{ now()->format('Y-m-d H:i') }}</div>
            <div>For school finance reference</div>
        </section>
    </div>
</body>
</html>
