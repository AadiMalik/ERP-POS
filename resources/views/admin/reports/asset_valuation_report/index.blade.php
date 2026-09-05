@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.asset_valuation_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i> {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.asset-valuation-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary"><i class="fa fa-print"></i> {{ __('common.print') }}</a>
                    @endcanAccess
                    @canAccess('reports.asset-valuation-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger"><i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}</a>
                    @endcanAccess
                    @canAccess('reports.asset-valuation-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success"><i class="fa fa-file-excel"></i> {{ __('common.excel') }}</a>
                    @endcanAccess
                    @canAccess('reports.asset-valuation-report.export-csv')
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
                            <label class="form-label">{{ __('common.category') }}</label>
                            <select id="fixed_asset_category_id" class="form-select">
                                <option value="">{{ __('common.all_categories') }}</option>
                                @foreach ($categories as $item)
                                <option value="{{ $item->fixed_asset_category_id }}">{{ $item->name }}</option>
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
                        <div class="alert alert-info mb-0"><strong>Total Cost:</strong> <span id="total_purchase_cost_display">-</span></div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-secondary mb-0"><strong>Total Accum. Dep.:</strong> <span id="total_accumulated_depreciation_display">-</span></div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success mb-0"><strong>Total Book Value:</strong> <span id="total_current_book_value_display">-</span></div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="asset_valuation_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_code') }}</th>
                                <th>{{ __('common.name') }}</th>
                                <th>{{ __('reports.col_category') }}</th>
                                <th>{{ __('common.branch') }}</th>
                                <th class="text-end">Purchase Cost</th>
                                <th class="text-end">Accum. Dep.</th>
                                <th class="text-end">Current Value</th>
                                <th class="text-end">Previous Value</th>
                                <th class="text-end">Residual</th>
                                <th>{{ __('common.status') }}</th>
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
            {data:'purchase_cost',name:'purchase_cost',sortable:false,className:'text-end'},
            {data:'accumulated_depreciation',name:'accumulated_depreciation',sortable:false,className:'text-end'},
            {data:'current_book_value',name:'current_book_value',sortable:false,className:'text-end'},
            {data:'previous_book_value',name:'previous_book_value',sortable:false,className:'text-end'},
            {data:'residual_value',name:'residual_value',sortable:false,className:'text-end'},
            {data:'depreciation_status',name:'depreciation_status',sortable:false}",
        'route' => 'asset-valuation-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'asset_valuation_report_table',
        'variable' => 'asset_valuation_report_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),fixed_asset_category_id:$('#fixed_asset_category_id').val()",
    ])
    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                fixed_asset_category_id: $('#fixed_asset_category_id').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }
        function buildReportUrl(path) {
            return url_local + path + '?' + $.param(currentReportParams());
        }
        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/asset-valuation-report/data',
                type: 'POST',
                data: $.extend({ _token: $('meta[name="csrf-token"]').attr('content'), draw: 1, start: 0, length: 1 }, currentReportParams()),
                success: function(response) {
                    $('#total_purchase_cost_display').text(response.total_purchase_cost ?? '-');
                    $('#total_accumulated_depreciation_display').text(response.total_accumulated_depreciation ?? '-');
                    $('#total_current_book_value_display').text(response.total_current_book_value ?? '-');
                }
            });
        }
        $(document).ready(function() {
            $('#business_id, #branch_id, #fixed_asset_category_id').select2();
            refreshTotals();
        });
        $('#search_btn').click(function() { initDataTableasset_valuation_report_table(); refreshTotals(); });
        $('#btn_print').click(function() { window.open(buildReportUrl('/admin/reports/asset-valuation-report/print'), '_blank'); });
        $('#btn_pdf').click(function() { window.open(buildReportUrl('/admin/reports/asset-valuation-report/pdf'), '_blank'); });
        $('#btn_excel').click(function() { window.location.href = buildReportUrl('/admin/reports/asset-valuation-report/export'); });
        $('#btn_csv').click(function() { window.location.href = buildReportUrl('/admin/reports/asset-valuation-report/export-csv'); });
    </script>
@endsection
