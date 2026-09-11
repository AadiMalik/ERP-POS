@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.cost_price_adjustment'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.cost_price_adjustment'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead>
            <tr>
                <th>{{ __('reports.col_reference_no') }}</th>
                <th>{{ __('reports.col_date') }}</th>
                <th>{{ __('common.product') }}</th>
                <th>{{ __('reports.col_variation') }}</th>
                <th>{{ __('reports.col_warehouse') }}</th>
                <th>{{ __('reports.col_previous_cost') }}</th>
                <th>{{ __('reports.col_new_cost') }}</th>
                <th>{{ __('reports.col_qty') }}</th>
                <th>{{ __('reports.col_total_adjustment') }}</th>
                <th>{{ __('reports.col_status') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ is_object($row) ? ($row->reference_no ?? '-') : '-' }}</td>
                    <td>{{ is_object($row) && $row->adjustment_date ? businessDate($row->adjustment_date) : '-' }}</td>
                    <td>{{ is_object($row) ? ($row->product_name ?? '-') : '-' }}</td>
                    <td>{{ is_object($row) ? ($row->variation_name ?? '-') : '-' }}</td>
                    <td>{{ is_object($row) ? ($row->warehouse_name ?? '-') : '-' }}</td>
                    <td>{{ is_object($row) ? currency($row->previous_cost_price ?? 0) : '-' }}</td>
                    <td>{{ is_object($row) ? currency($row->new_cost_price ?? 0) : '-' }}</td>
                    <td>{{ is_object($row) ? decimal($row->quantity_on_hand ?? 0) : '-' }}</td>
                    <td>{{ is_object($row) ? currency($row->total_adjustment_amount ?? 0) : '-' }}</td>
                    <td>{{ is_object($row) ? ucfirst($row->status ?? '-') : '-' }}</td>
                </tr>
            @empty
                <tr><td colspan="10">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
