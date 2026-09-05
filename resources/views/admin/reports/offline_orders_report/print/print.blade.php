@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.offline_orders_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.offline_orders_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_order_no') }}</th>
                <th>{{ __('reports.col_date') }}</th>
                <th>{{ __('reports.col_branch') }}</th>
                <th>{{ __('reports.col_device') }}</th>
                <th>{{ __('reports.col_customer') }}</th>
                <th>{{ __('reports.col_status') }}</th>
                <th class="text-right">{{ __('reports.col_total') }}</th>
                <th>{{ __('reports.col_offline_local_id') }}</th>
                <th>{{ __('reports.col_last_sync') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->daily_order_id }}</td>
                    <td>{{ optional($row->order_date)->format('d-m-Y H:i') }}</td>
                    <td>{{ $row->branch->name ?? '' }}</td>
                    <td>{{ $row->posDevice->name ?? '-' }}</td>
                    <td>{{ $row->user->name ?? 'Walk-in' }}</td>
                    <td>{{ ucfirst(str_replace('_', ' ', $row->status)) }}</td>
                    <td class="text-right">{{ currency($row->total) }}</td>
                    <td>{{ $row->offline_local_id ?? '-' }}</td>
                    <td>{{ optional(optional($row->posDevice)->last_sync_at)->format('d-m-Y H:i') ?? '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="text-center">No records found</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
