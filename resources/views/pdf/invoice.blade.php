<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ $invoice->number }}</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }

        body {
            font-family: 'DejaVu Sans', Arial, sans-serif;
            font-size: 11px;
            color: #1e293b;
            background: #fff;
            padding: 40px;
        }

        /* ── Header ── */
        .header {
            display: table;
            width: 100%;
            margin-bottom: 32px;
        }
        .header-left, .header-right {
            display: table-cell;
            vertical-align: top;
        }
        .header-right {
            text-align: right;
        }
        .business-name {
            font-size: 20px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 4px;
        }
        .business-detail {
            color: #64748b;
            font-size: 10px;
            line-height: 1.6;
        }
        .doc-type {
            font-size: 28px;
            font-weight: bold;
            color: #2563eb;
            text-transform: uppercase;
            letter-spacing: 2px;
        }
        .doc-number {
            font-size: 13px;
            color: #475569;
            margin-top: 4px;
        }

        /* ── Status Badge ── */
        .status-badge {
            display: inline-block;
            padding: 2px 10px;
            border-radius: 20px;
            font-size: 9px;
            font-weight: bold;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 6px;
        }
        .status-draft    { background: #f1f5f9; color: #64748b; }
        .status-sent     { background: #dbeafe; color: #1d4ed8; }
        .status-approved { background: #dbeafe; color: #1d4ed8; }
        .status-paid     { background: #dcfce7; color: #15803d; }
        .status-partially_paid { background: #fef9c3; color: #92400e; }
        .status-overdue  { background: #fee2e2; color: #dc2626; }
        .status-void     { background: #f1f5f9; color: #94a3b8; }

        /* ── Divider ── */
        hr {
            border: none;
            border-top: 1px solid #e2e8f0;
            margin: 20px 0;
        }

        /* ── Billing Info ── */
        .billing {
            display: table;
            width: 100%;
            margin-bottom: 24px;
        }
        .billing-col {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .billing-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            margin-bottom: 6px;
            font-weight: bold;
        }
        .billing-name {
            font-size: 13px;
            font-weight: bold;
            color: #1e293b;
            margin-bottom: 2px;
        }
        .billing-detail {
            font-size: 10px;
            color: #64748b;
            line-height: 1.6;
        }

        /* ── Meta Row ── */
        .meta-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 24px;
            background: #f8fafc;
            border-radius: 4px;
        }
        .meta-table td {
            padding: 8px 12px;
            font-size: 10px;
            border-right: 1px solid #e2e8f0;
        }
        .meta-table td:last-child { border-right: none; }
        .meta-label {
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-size: 8px;
            display: block;
            margin-bottom: 2px;
        }
        .meta-value {
            font-weight: bold;
            color: #1e293b;
        }

        /* ── Line Items Table ── */
        .lines-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .lines-table th {
            background: #1e293b;
            color: #fff;
            padding: 8px 10px;
            text-align: left;
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .lines-table th.right { text-align: right; }
        .lines-table td {
            padding: 9px 10px;
            border-bottom: 1px solid #f1f5f9;
            font-size: 10px;
            vertical-align: top;
        }
        .lines-table tr:last-child td { border-bottom: none; }
        .lines-table tr:nth-child(even) td { background: #f8fafc; }
        .line-desc { font-weight: 600; color: #1e293b; }
        .line-account { font-size: 9px; color: #94a3b8; margin-top: 1px; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }

        /* ── Totals ── */
        .totals-wrap {
            display: table;
            width: 100%;
            margin-top: 12px;
        }
        .totals-spacer { display: table-cell; width: 55%; }
        .totals-box {
            display: table-cell;
            width: 45%;
            vertical-align: top;
        }
        .totals-row {
            display: table;
            width: 100%;
            padding: 5px 0;
            border-bottom: 1px solid #f1f5f9;
        }
        .totals-row:last-child { border-bottom: none; }
        .totals-label, .totals-amount {
            display: table-cell;
            font-size: 11px;
        }
        .totals-amount { text-align: right; font-weight: 600; }
        .totals-label.muted { color: #64748b; font-weight: normal; }
        .totals-grand {
            background: #1e293b;
            color: #fff;
            padding: 8px 10px;
            margin-top: 4px;
            border-radius: 4px;
        }
        .totals-grand .totals-label, .totals-grand .totals-amount {
            font-size: 13px;
            font-weight: bold;
            color: #fff;
        }
        .totals-balance {
            background: #fef3c7;
            padding: 6px 10px;
            margin-top: 4px;
            border-radius: 4px;
        }
        .totals-balance .totals-label, .totals-balance .totals-amount {
            color: #92400e;
            font-weight: bold;
        }

        /* ── Notes / Terms ── */
        .notes-section {
            margin-top: 28px;
            display: table;
            width: 100%;
        }
        .notes-col {
            display: table-cell;
            width: 48%;
            vertical-align: top;
            padding-right: 16px;
        }
        .notes-col:last-child { padding-right: 0; }
        .notes-label {
            font-size: 9px;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: #94a3b8;
            font-weight: bold;
            margin-bottom: 6px;
        }
        .notes-text {
            font-size: 10px;
            color: #475569;
            line-height: 1.6;
        }

        /* ── Footer ── */
        .footer {
            margin-top: 40px;
            padding-top: 12px;
            border-top: 1px solid #e2e8f0;
            text-align: center;
            font-size: 9px;
            color: #94a3b8;
        }
    </style>
</head>
<body>

{{-- ── HEADER ── --}}
<div class="header">
    <div class="header-left">
        <div class="business-name">{{ $business->name }}</div>
        <div class="business-detail">
            @if($business->legal_name){{ $business->legal_name }}<br>@endif
            @if($business->address_line_1){{ $business->address_line_1 }}<br>@endif
            @if($business->city || $business->state || $business->postal_code)
                {{ implode(', ', array_filter([$business->city, $business->state, $business->postal_code])) }}<br>
            @endif
            @if($business->email){{ $business->email }}@endif
            @if($business->email && $business->phone) &nbsp;·&nbsp; @endif
            @if($business->phone){{ $business->phone }}@endif
            @if($business->tax_number)<br>Tax #: {{ $business->tax_number }}@endif
        </div>
    </div>
    <div class="header-right">
        <div class="doc-type">
            @switch($invoice->type)
                @case('quote') Quote @break
                @case('credit_note') Credit Note @break
                @case('purchase_invoice') Purchase Invoice @break
                @default Invoice
            @endswitch
        </div>
        <div class="doc-number">{{ $invoice->number }}</div>
        <div>
            <span class="status-badge status-{{ str_replace('_', '_', $invoice->status) }}">
                {{ str_replace('_', ' ', $invoice->status) }}
            </span>
        </div>
    </div>
</div>

<hr>

{{-- ── BILLING INFO ── --}}
<div class="billing">
    <div class="billing-col">
        <div class="billing-label">
            @if($invoice->type === 'purchase_invoice') From @else Bill To @endif
        </div>
        @if($invoice->contact)
            <div class="billing-name">{{ $invoice->contact->name }}</div>
            <div class="billing-detail">
                @if($invoice->contact->email){{ $invoice->contact->email }}<br>@endif
                @if($invoice->contact->phone){{ $invoice->contact->phone }}<br>@endif
                @if($invoice->contact->address_line_1){{ $invoice->contact->address_line_1 }}<br>@endif
                @if($invoice->contact->city || $invoice->contact->state)
                    {{ implode(', ', array_filter([$invoice->contact->city, $invoice->contact->state, $invoice->contact->postal_code])) }}<br>
                @endif
                @if($invoice->contact->tax_number)Tax #: {{ $invoice->contact->tax_number }}@endif
            </div>
        @else
            <div class="billing-detail" style="color:#94a3b8">No contact specified</div>
        @endif
    </div>
    <div class="billing-col">&nbsp;</div>
</div>

{{-- ── META ROW ── --}}
<table class="meta-table">
    <tr>
        <td>
            <span class="meta-label">
                @if($invoice->type === 'quote') Quote Date
                @elseif($invoice->type === 'credit_note') Issue Date
                @else Invoice Date
                @endif
            </span>
            <span class="meta-value">{{ \Carbon\Carbon::parse($invoice->date)->format('d M Y') }}</span>
        </td>
        @if($invoice->due_date)
        <td>
            <span class="meta-label">Due Date</span>
            <span class="meta-value">{{ \Carbon\Carbon::parse($invoice->due_date)->format('d M Y') }}</span>
        </td>
        @endif
        @if($invoice->reference)
        <td>
            <span class="meta-label">Reference</span>
            <span class="meta-value">{{ $invoice->reference }}</span>
        </td>
        @endif
        <td>
            <span class="meta-label">Currency</span>
            <span class="meta-value">{{ $invoice->currency_code }}</span>
        </td>
    </tr>
</table>

{{-- ── LINE ITEMS ── --}}
<table class="lines-table">
    <thead>
        <tr>
            <th style="width:42%">Description</th>
            <th class="right" style="width:8%">Qty</th>
            <th class="right" style="width:14%">Unit Price</th>
            <th class="right" style="width:8%">Disc %</th>
            <th style="width:14%">Tax</th>
            <th class="right" style="width:14%">Total</th>
        </tr>
    </thead>
    <tbody>
        @foreach($invoice->lines as $line)
        <tr>
            <td>
                <div class="line-desc">{{ $line->description }}</div>
                @if($line->account)
                    <div class="line-account">{{ $line->account->code }} · {{ $line->account->name }}</div>
                @endif
            </td>
            <td class="text-right">{{ $line->quantity }}</td>
            <td class="text-right">{{ number_format($line->unit_price, 2) }}</td>
            <td class="text-right">{{ $line->discount_percent > 0 ? $line->discount_percent.'%' : '—' }}</td>
            <td>{{ $line->taxCode?->name ?? '—' }}</td>
            <td class="text-right">{{ number_format($line->line_total, 2) }}</td>
        </tr>
        @endforeach
    </tbody>
</table>

{{-- ── TOTALS ── --}}
<div class="totals-wrap">
    <div class="totals-spacer"></div>
    <div class="totals-box">
        <div class="totals-row">
            <span class="totals-label muted">Subtotal</span>
            <span class="totals-amount">{{ $invoice->currency_code }} {{ number_format($invoice->subtotal, 2) }}</span>
        </div>
        @if($invoice->tax_amount > 0)
        <div class="totals-row">
            <span class="totals-label muted">Tax</span>
            <span class="totals-amount">{{ $invoice->currency_code }} {{ number_format($invoice->tax_amount, 2) }}</span>
        </div>
        @endif
        <div class="totals-grand">
            <div class="totals-row" style="border-bottom:none;padding:0">
                <span class="totals-label">Total</span>
                <span class="totals-amount">{{ $invoice->currency_code }} {{ number_format($invoice->total, 2) }}</span>
            </div>
        </div>
        @if($invoice->amount_paid > 0)
        <div class="totals-row" style="padding-top:8px">
            <span class="totals-label muted">Paid</span>
            <span class="totals-amount" style="color:#15803d">− {{ $invoice->currency_code }} {{ number_format($invoice->amount_paid, 2) }}</span>
        </div>
        <div class="totals-balance">
            <div class="totals-row" style="border-bottom:none;padding:0">
                <span class="totals-label">Balance Due</span>
                <span class="totals-amount">{{ $invoice->currency_code }} {{ number_format($invoice->balance_due, 2) }}</span>
            </div>
        </div>
        @endif
    </div>
</div>

{{-- ── NOTES / TERMS ── --}}
@if($invoice->notes || $invoice->terms)
<div class="notes-section">
    @if($invoice->notes)
    <div class="notes-col">
        <div class="notes-label">Notes</div>
        <div class="notes-text">{{ $invoice->notes }}</div>
    </div>
    @endif
    @if($invoice->terms)
    <div class="notes-col">
        <div class="notes-label">Terms & Conditions</div>
        <div class="notes-text">{{ $invoice->terms }}</div>
    </div>
    @endif
</div>
@endif

{{-- ── FOOTER ── --}}
<div class="footer">
    {{ $business->name }}
    @if($business->email) · {{ $business->email }}@endif
    @if($business->phone) · {{ $business->phone }}@endif
</div>

</body>
</html>
