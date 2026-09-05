@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.balance_sheet'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.balance_sheet'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [
            'As Of' => localDate($result['as_of_date']),
        ],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <tbody>
            <tr><th colspan="2">Assets</th></tr>
            <tr><th colspan="2">Current Assets</th></tr>
            @foreach ($result['current_assets'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Current Assets</td><td class="text-right">{{ currency($result['total_current_assets']) }}</td></tr>

            <tr><th colspan="2">Fixed Assets</th></tr>
            @foreach ($result['fixed_assets'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Fixed Assets</td><td class="text-right">{{ currency($result['total_fixed_assets']) }}</td></tr>

            @if ($result['other_assets']->isNotEmpty())
                <tr><th colspan="2">Other Assets</th></tr>
                @foreach ($result['other_assets'] as $row)
                    <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
                @endforeach
                <tr class="grand-total"><td>Total Other Assets</td><td class="text-right">{{ currency($result['total_other_assets']) }}</td></tr>
            @endif

            <tr class="grand-total"><td><strong>Total Assets</strong></td><td class="text-right"><strong>{{ currency($result['total_assets']) }}</strong></td></tr>
        </tbody>
    </table>

    <table class="print-table" style="margin-top: 15px;">
        <tbody>
            <tr><th colspan="2">Liabilities</th></tr>
            <tr><th colspan="2">Current Liabilities</th></tr>
            @foreach ($result['current_liabilities'] as $row)
                <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Current Liabilities</td><td class="text-right">{{ currency($result['total_current_liabilities']) }}</td></tr>

            @if ($result['long_term_liabilities']->isNotEmpty())
                <tr><th colspan="2">Long-term Liabilities</th></tr>
                @foreach ($result['long_term_liabilities'] as $row)
                    <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
                @endforeach
                <tr class="grand-total"><td>Total Long-term Liabilities</td><td class="text-right">{{ currency($result['total_long_term_liabilities']) }}</td></tr>
            @endif

            @if ($result['other_liabilities']->isNotEmpty())
                <tr><th colspan="2">Other Liabilities</th></tr>
                @foreach ($result['other_liabilities'] as $row)
                    <tr><td>{{ $row->account_code }} {{ $row->account_name }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
                @endforeach
                <tr class="grand-total"><td>Total Other Liabilities</td><td class="text-right">{{ currency($result['total_other_liabilities']) }}</td></tr>
            @endif

            <tr class="grand-total"><td>Total Liabilities</td><td class="text-right">{{ currency($result['total_liabilities']) }}</td></tr>

            <tr><th colspan="2">Equity</th></tr>
            @foreach ($result['equity'] as $row)
                <tr><td>{{ trim($row->account_code . ' ' . $row->account_name) }}</td><td class="text-right">{{ currency($row->amount) }}</td></tr>
            @endforeach
            <tr class="grand-total"><td>Total Equity</td><td class="text-right">{{ currency($result['total_equity']) }}</td></tr>

            <tr class="grand-total"><td><strong>Total Liabilities &amp; Equity</strong></td><td class="text-right"><strong>{{ currency($result['total_liabilities_and_equity']) }}</strong></td></tr>
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
