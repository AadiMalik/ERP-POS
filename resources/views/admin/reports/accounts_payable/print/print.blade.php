@php
    use App\Services\Concrete\Admin\Reports\AccountsPayableReportService;
    $business = Auth::user()->business;
@endphp
@extends('layouts.print')

@section('title', 'Accounts Payable Report')

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Accounts Payable Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [
            'Supplier' => optional($invoices->firstWhere('supplier_id', request('supplier_id')))->supplier_name ?? 'All Suppliers',
        ],
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Supplier</th>
                <th>Purchase No.</th>
                <th>Invoice No.</th>
                <th>Invoice Date</th>
                <th>Due Date</th>
                <th class="text-right">Invoiced</th>
                <th class="text-right">Paid</th>
                <th class="text-right">Outstanding</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($invoices as $row)
                <tr>
                    <td>{{ $row->supplier_name }}</td>
                    <td>{{ $row->purchase_no }}</td>
                    <td>{{ $row->invoice_number }}</td>
                    <td>{{ localDate($row->invoice_date) }}</td>
                    <td>{{ localDate($row->due_date) }}</td>
                    <td class="text-right">{{ currency($row->invoiced_amount) }}</td>
                    <td class="text-right">{{ currency($row->paid_amount) }}</td>
                    <td class="text-right">{{ currency($row->outstanding_amount) }}</td>
                    <td>{{ AccountsPayableReportService::STATUS_LABELS[$row->status] ?? ucfirst($row->status) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <table class="print-totals">
        <tr>
            <td>Total Invoiced</td>
            <td class="text-right">{{ currency($invoices->sum('invoiced_amount')) }}</td>
        </tr>
        <tr>
            <td>Total Paid</td>
            <td class="text-right">{{ currency($invoices->sum('paid_amount')) }}</td>
        </tr>
        <tr class="grand-total">
            <td>Total Outstanding</td>
            <td class="text-right">{{ currency($invoices->sum('outstanding_amount')) }}</td>
        </tr>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
    ])
@endsection
