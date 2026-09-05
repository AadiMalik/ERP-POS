<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Invoice {{ $invoice->invoice_no }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #333; }
        .header { display: flex; justify-content: space-between; margin-bottom: 20px; }
        h2 { margin: 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { padding: 6px 8px; border: 1px solid #ddd; text-align: left; }
        th { background: #f5f5f5; }
        .text-right { text-align: right; }
        .totals td { border: none; }
        .totals { width: 40%; margin-left: auto; }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h2>Invoice {{ $invoice->invoice_no }}</h2>
            <p>Date: {{ $invoice->date_created ? date('d-m-Y', strtotime($invoice->date_created)) : '' }}</p>
            @if ($invoice->due_date)
                <p>Due: {{ date('d-m-Y', strtotime($invoice->due_date)) }}</p>
            @endif
        </div>
        <div>
            <strong>{{ $invoice->business->name ?? '' }}</strong><br>
            {{ $invoice->business->owner_email ?? '' }}<br>
            {{ $invoice->business->phone ?? '' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Description</th>
                <th>Billing Cycle</th>
                <th>Period</th>
                <th class="text-right">Amount</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>{{ $invoice->package->name ?? 'Subscription' }}</td>
                <td>{{ ucfirst($invoice->billing_cycle) }}</td>
                <td>
                    {{ $invoice->period_start ? date('d-m-Y', strtotime($invoice->period_start)) : '' }}
                    -
                    {{ $invoice->period_end ? date('d-m-Y', strtotime($invoice->period_end)) : '' }}
                </td>
                <td class="text-right">{{ number_format($invoice->subtotal, 2) }}</td>
            </tr>
        </tbody>
    </table>

    <table class="totals">
        <tr><td>Subtotal</td><td class="text-right">{{ number_format($invoice->subtotal, 2) }}</td></tr>
        <tr><td>Discount</td><td class="text-right">{{ number_format($invoice->discount_amount, 2) }}</td></tr>
        <tr><td>Tax</td><td class="text-right">{{ number_format($invoice->tax_amount, 2) }}</td></tr>
        <tr><th>Total</th><th class="text-right">{{ number_format($invoice->total, 2) }}</th></tr>
    </table>

    <h4>{{ __('Payments') }}</h4>
    <table>
        <thead>
            <tr><th>Date</th><th>Method</th><th>Reference</th><th>Status</th><th class="text-right">Amount</th></tr>
        </thead>
        <tbody>
            @forelse ($invoice->payments as $payment)
                <tr>
                    <td>{{ $payment->paid_at ? date('d-m-Y', strtotime($payment->paid_at)) : '' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                    <td>{{ $payment->payment_reference ?? '-' }}</td>
                    <td>{{ ucfirst($payment->status) }}</td>
                    <td class="text-right">{{ number_format($payment->amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="5">No payments recorded yet.</td></tr>
            @endforelse
        </tbody>
    </table>

    <p style="margin-top: 20px; color: #777;">Status: {{ ucwords(str_replace('_', ' ', $invoice->status)) }}</p>
</body>
</html>
