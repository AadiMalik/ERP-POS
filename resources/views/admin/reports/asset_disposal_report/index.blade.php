@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.asset_disposal_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i> {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.asset-disposal-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary"><i class="fa fa-print"></i> {{ __('common.print') }}</a>
                    @endcanAccess
                    @canAccess('reports.asset-disposal-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger"><i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}</a>
                    @endcanAccess
                    @canAccess('reports.asset-disposal-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success"><i class="fa fa-file-excel"></i> {{ __('common.excel') }}</a>
                    @endcanAccess
                    @canAccess('reports.asset-disposal-report.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success"><i class="fa fa-file-text"></i> {{ __('common.csv') }}</a>
                    @endcanAccess
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.business') }}</label>
                            <select id="business_id" class="form-select">
                                <option value="">{{ __('common.all_businesses') }}</option>
                                @foreach ($business as $item)
                                <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.branch') }}</label>
                            <select id="branch_id" class="form-select">
                                <option value="">{{ __('common.all_branches') }}</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                @foreach ($branches as $item)
                                <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Disposal Type</label>
                            <select id="disposal_type" class="form-select">
                                <option value="">{{ __('common.all_types') }}</option>
                                @foreach ($disposal_types as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.date') }}</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                        </div>
                    </div>
                </div>
                <div class="row g-3 p-4 pb-0">
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0"><strong>Total Sale Price:</strong> <span id="total_sale_price_display">-</span></div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-secondary mb-0"><strong>Total Book Value:</strong> <span id="total_book_value_display">-</span></div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="asset_disposal_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_code') }}</th>
                                <th>{{ __('common.name') }}</th>
                                <th>{{ __('reports.col_category') }}</th>
                                <th>{{ __('common.branch') }}</th>
                                <th>{{ __('reports.col_disposal_date') }}</th>
                                <th>{{ __('reports.col_type') }}</th>
                                <th class="text-end">Sale Price</th>
                                <th class="text-end">Book Value</th>
                                <th class="text-end">Purchase Cost</th>
                                <th>{{ __('reports.col_reason') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>JV</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
            {data:'asset_code',name:'asset_code',sortable:false},
            {data:'name',name:'name',sortable:false},
            {data:'category',name:'category',sortable:false},
            {data:'branch',name:'branch',sortable:false},
            {data:'disposal_date',name:'disposal_date',sortable:false},
            {data:'disposal_type',name:'disposal_type',sortable:false},
            {data:'sale_price',name:'sale_price',sortable:false,className:'text-end'},
            {data:'current_book_value',name:'current_book_value',sortable:false,className:'text-end'},
            {data:'purchase_cost',name:'purchase_cost',sortable:false,className:'text-end'},
            {data:'disposal_reason',name:'disposal_reason',sortable:false},
            {data:'depreciation_status',name:'depreciation_status',sortable:false},
            {data:'journal_entry',name:'journal_entry',sortable:false}",
        'route' => 'asset-disposal-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'asset_disposal_report_table',
        'variable' => 'asset_disposal_report_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),disposal_type:$('#disposal_type').val()",
    ])
    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                disposal_type: $('#disposal_type').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }
        function buildReportUrl(path) {
            return url_local + path + '?' + $.param(currentReportParams());
        }
        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/asset-disposal-report/data',
                type: 'POST',
                data: $.extend({ _token: $('meta[name="csrf-token"]').attr('content'), draw: 1, start: 0, length: 1 }, currentReportParams()),
                success: function(response) {
                    $('#total_sale_price_display').text(response.total_sale_price ?? '-');
                    $('#total_book_value_display').text(response.total_book_value ?? '-');
                }
            });
        }
        $(document).ready(function() {
            $('#business_id, #branch_id, #disposal_type').select2();
            refreshTotals();
        });
        $('#search_btn').click(function() { initDataTableasset_disposal_report_table(); refreshTotals(); });
        $('#btn_print').click(function() { window.open(buildReportUrl('/admin/reports/asset-disposal-report/print'), '_blank'); });
        $('#btn_pdf').click(function() { window.open(buildReportUrl('/admin/reports/asset-disposal-report/pdf'), '_blank'); });
        $('#btn_excel').click(function() { window.location.href = buildReportUrl('/admin/reports/asset-disposal-report/export'); });
        $('#btn_csv').click(function() { window.location.href = buildReportUrl('/admin/reports/asset-disposal-report/export-csv'); });
    </script>
@endsection
