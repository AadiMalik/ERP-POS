@extends('layouts.print')

@section('title', 'Supplier Payment - ' . ($payment->payment_no ?? ''))

@section('content')
    @php
        $posted = $payment->status === \App\Enums\Status::POSTED;
    @endphp

    @include('admin.partials.print.status_badge', ['status' => $payment->status, 'posted' => $posted])

    @include('admin.partials.print.header', [
        'business' => $payment->business,
        'branch' => $payment->branch,
        'title' => 'Payment Voucher',
        'doc_no' => $payment->payment_no,
        'doc_date' => localDate($payment->payment_date),
        'reference' => [
            'Supplier' => $payment->supplier->name ?? 'N/A',
            'Linked Purchase No.' => $payment->purchase->purchase_no ?? 'N/A',
            'Payment Method' => ucwords(str_replace('_', ' ', $payment->payment_method ?? '')),
        ],
    ])

    <table class="print-reference">
        <tr>
            <td class="label">Supplier Account</td>
            <td>{{ $payment->supplier->account->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Payment Account</td>
            <td>{{ $payment->paymentAccount->name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Reference No.</td>
            <td>{{ $payment->reference_no ?? 'N/A' }}</td>
        </tr>
        @if (!empty($payment->cheque_date))
            <tr>
                <td class="label">Cheque Date</td>
                <td>{{ localDate($payment->cheque_date) }}</td>
            </tr>
        @endif
    </table>

    <table class="print-totals">
        <tr>
            <td>Amount</td>
            <td class="text-right">{{ currency($payment->amount) }}</td>
        </tr>
        <tr>
            <td>Tax Amount</td>
            <td class="text-right">{{ currency($payment->tax_amount) }}</td>
        </tr>
        <tr>
            <td>Discount Amount</td>
            <td class="text-right">{{ currency($payment->discount_amount) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Net Amount</td>
            <td class="text-right">{{ currency($payment->net_amount) }}</td>
        </tr>
    </table>

    @if (!empty($payment->remarks))
        <div class="print-remarks">
            <strong>Remarks:</strong> {{ $payment->remarks }}
        </div>
    @endif

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Posted By'],
    ])
@endsection
