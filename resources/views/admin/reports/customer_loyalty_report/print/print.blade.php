@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', 'Customer Loyalty History Report')

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => 'Customer Loyalty History Report',
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>Customer</th>
                <th>Reference</th>
                <th>Transaction Type</th>
                <th class="text-right">Points</th>
                <th class="text-right">Monetary Value</th>
                <th>Date</th>
                <th class="text-right">Balance After</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->customer_name ?? 'N/A' }}</td>
                    <td>
                        @if ('order' === $row->reference_type && $row->reference_order_no)
                            {{ $row->reference_order_no }}
                        @elseif ($row->reference_type)
                            {{ ucfirst(str_replace('_', ' ', $row->reference_type)) }}{{ $row->reference_id ? ' #' . $row->reference_id : '' }}
                        @else
                            N/A
                        @endif
                    </td>
                    <td>{{ ucfirst($row->transaction_type) }}</td>
                    <td class="text-right">{{ decimal($row->points) }}</td>
                    <td class="text-right">{{ $row->monetary_value !== null ? currency($row->monetary_value) : '-' }}</td>
                    <td>{{ optional($row->date_created)->format('d-m-Y H:i') }}</td>
                    <td class="text-right">{{ decimal($row->available_balance_after) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
