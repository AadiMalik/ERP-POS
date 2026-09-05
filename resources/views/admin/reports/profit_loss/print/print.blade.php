@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.profit_loss'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.profit_loss'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [
            'Period' => localDate($result['start_date']) . ' to ' . localDate($result['end_date']),
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <tbody>
            <tr><th colspan="2">Revenue / Income</th></tr>
            @foreach ($result['revenue'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Revenue</td><td class="text-right">{{ currency($result['total_revenue']) }}</td></tr>

            <tr><th colspan="2">Cost of Revenue</th></tr>
            @foreach ($result['cost_of_revenue'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Cost of Revenue</td><td class="text-right">{{ currency($result['total_cost_of_revenue']) }}</td></tr>
            <tr class="grand-total"><td>Gross Profit</td><td class="text-right">{{ currency($result['gross_profit']) }}</td></tr>

            <tr><th colspan="2">Direct Expenses</th></tr>
            @foreach ($result['direct_expense'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Direct Expenses</td><td class="text-right">{{ currency($result['total_direct_expense']) }}</td></tr>

            <tr><th colspan="2">Operating Expenses</th></tr>
            @foreach ($result['operating_expense'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Operating Expenses</td><td class="text-right">{{ currency($result['total_operating_expense']) }}</td></tr>
            <tr class="grand-total"><td>Operating Profit</td><td class="text-right">{{ currency($result['operating_profit']) }}</td></tr>

            <tr><th colspan="2">Other Income</th></tr>
            @foreach ($result['other_income'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Other Income</td><td class="text-right">{{ currency($result['total_other_income']) }}</td></tr>

            <tr><th colspan="2">Other Expenses</th></tr>
            @foreach ($result['other_expense'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Other Expenses</td><td class="text-right">{{ currency($result['total_other_expense']) }}</td></tr>

            <tr class="grand-total"><td><strong>Net Profit / (Loss)</strong></td><td class="text-right"><strong>{{ currency($result['net_profit']) }}</strong></td></tr>
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
