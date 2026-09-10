@php
    $business = Auth::user()->business;
    $print_config = app(\App\Services\Concrete\Admin\PrintSettingResolverService::class)
        ->resolve(Auth::user()->business_id);
@endphp
@extends('layouts.print')

@section('title', __('reports.waste_damage_expiry_report'))

@section('css')
    @include('admin.partials.print.page_css', ['print_config' => $print_config])
@endsection

@section('content')
    @include('admin.partials.print.header', [
        'business' => $business,
        'branch' => null,
        'title' => __('reports.waste_damage_expiry_report'),
        'doc_no' => '',
        'doc_date' => localDate(now()),
        'reference' => [],
        'print_config' => $print_config,
    ])

    <table class="print-table">
        <thead><tr><th>{{ __('reports.col_reference_no_alt') }}</th><th>{{ __('reports.col_date') }}</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_product') }}</th><th>{{ __('reports.col_variation') }}</th><th>{{ __('reports.col_batch') }}</th><th>{{ __('reports.col_expiry') }}</th><th>{{ __('reports.col_qty') }}</th><th>{{ __('reports.col_unit') }}</th><th>{{ __('reports.col_unit_cost') }}</th><th>{{ __('reports.col_value') }}</th><th>{{ __('reports.col_loss_type') }}</th><th>{{ __('reports.col_reason') }}</th><th>{{ __('reports.col_status') }}</th></tr></thead>
        <tbody>
            @forelse ($rows as $row)
                <tr>
                    <td>{{ $row->reference_no ?? '-' }}</td>
                    <td>{{ $row->transaction_date ? businessDate($row->transaction_date) : '-' }}</td>
                    <td>{{ $row->warehouse_name ?? '-' }}</td>
                    <td>{{ $row->product_name ?? '-' }}</td>
                    <td>{{ $row->variation_name ?? '-' }}</td>
                    <td>{{ $row->batch_no ?? '-' }}</td>
                    <td>{{ $row->expiry_date ? businessDate($row->expiry_date) : '-' }}</td>
                    <td>{{ $row->quantity ?? '-' }}</td>
                    <td>{{ $row->unit_name ?? '-' }}</td>
                    <td>{{ $row->unit_cost ?? '-' }}</td>
                    <td>{{ $row->value ?? '-' }}</td>
                    <td>{{ $row->loss_type_label ?? '-' }}</td>
                    <td>{{ $row->loss_reason ?? '-' }}</td>
                    <td>{{ ucfirst($row->status ?? '-') }}</td>
                </tr>
            @empty
                <tr><td colspan="14">{{ __('common.no_records_found') }}</td></tr>
            @endforelse
        </tbody>
    </table>
    @include('admin.partials.print.footer', [
        'signatories' => ['Prepared By', 'Verified By'],
        'print_config' => $print_config,
    ])
@endsection
