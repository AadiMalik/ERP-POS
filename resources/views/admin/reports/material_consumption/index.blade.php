@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('reports.material_consumption') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <button type="button" id="toggleFilter" class="btn btn-outline-primary"><i class="fa fa-filter"></i> {{ __('common.filters') }}</button>
            <div class="d-flex gap-2">
                @can('reports.material-consumption-report.print')
                <button type="button" id="print_btn" class="btn btn-outline-secondary"><i class="fa fa-print"></i> {{ __('common.print') }}</button>
                @endcan
                @can('reports.material-consumption-report.pdf')
                <button type="button" id="pdf_btn" class="btn btn-outline-danger"><i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}</button>
                @endcan
                @can('reports.material-consumption-report.export')
                <button type="button" id="export_btn" class="btn btn-outline-success"><i class="fa fa-file-excel"></i> {{ __('common.excel') }}</button>
                @endcan
                @can('reports.material-consumption-report.export-csv')
                <button type="button" id="export_csv_btn" class="btn btn-outline-info"><i class="fa fa-file-csv"></i> {{ __('common.csv') }}</button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.business') }}</label>
                        <select id="business_id" class="form-select">
                            <option value="">{{ __('common.all_businesses') }}</option>
                            @foreach ($business as $item)<option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>@endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.branch') }}</label>
                        <select id="branch_id" class="form-select">
                            <option value="">{{ __('common.all_branches') }}</option>
                            @foreach ($branches as $item)<option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('reports.raw_material_product') }}</label>
                        <select id="product_id" class="form-select">
                            <option value="">{{ __('common.all_products') }}</option>
                            @foreach ($products as $item)<option value="{{ $item->product_id }}">{{ $item->name ?? '' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.warehouse') }}</label>
                        <select id="warehouse_id" class="form-select">
                            <option value="">{{ __('common.all_warehouses') }}</option>
                            @foreach ($warehouses as $item)<option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('reports.report_mode') }}</label>
                        <select id="report_mode" class="form-select">
                            <option value="detail">Detail / Cost Analysis</option>
                            <option value="variance">Expected vs Actual / Variance</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('reports.group_by') }}</label>
                        <select id="group_by" class="form-select">
                            <option value="detail">No Grouping (Detail)</option>
                            <option value="material">Material-wise</option>
                            <option value="product">Product-wise (Finished)</option>
                            <option value="category">Category-wise</option>
                            <option value="warehouse">Warehouse-wise</option>
                            <option value="production">Production-wise</option>
                            <option value="recipe">Recipe-wise</option>
                            <option value="plan">Plan / Order-wise</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.date_range') }}</label>
                        @include('admin.partials.date_filter')
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="material_consumption_report_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.date') }}</th><th>{{ __('reports.col_group') }}</th><th>Raw Material</th><th>Finished</th><th>{{ __('reports.col_batch') }}</th>
                            <th>Actual Qty</th><th>Expected</th><th>Variance</th><th>Var %</th><th>Efficiency</th>
                            <th>{{ __('reports.col_unit_cost') }}</th><th>Total Cost</th><th>{{ __('reports.col_warehouse') }}</th><th>{{ __('reports.col_production_hash') }}</th><th>{{ __('reports.col_plan_hash') }}</th>
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
{data:'date',name:'date_created'},
{data:'group_label',name:'group_label',sortable:false},
{data:'raw_material',name:'raw_material',sortable:false},
{data:'finished_product',name:'finished_product',sortable:false},
{data:'batch_consumed',name:'batch_consumed',sortable:false},
{data:'quantity',name:'quantity'},
{data:'expected_qty',name:'expected_qty',sortable:false},
{data:'variance_qty',name:'variance_qty',sortable:false},
{data:'variance_pct',name:'variance_pct',sortable:false},
{data:'efficiency_pct',name:'efficiency_pct',sortable:false},
{data:'unit_cost',name:'unit_cost'},
{data:'total_cost',name:'total_cost'},
{data:'warehouse',name:'warehouse',sortable:false},
{data:'production_no',name:'production_no',sortable:false},
{data:'plan_no',name:'plan_no',sortable:false}",
'route' => 'material-consumption/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'material_consumption_report_table',
'variable' => 'material_consumption_report_table',
'datefilter' => true,
'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),product_id:$('#product_id').val(),warehouse_id:$('#warehouse_id').val(),report_mode:$('#report_mode').val(),group_by:$('#group_by').val(),production_id:(new URLSearchParams(window.location.search)).get('production_id')",
])
<script>
    function currentReportParams() {
        return {
            business_id: $('#business_id').val(), branch_id: $('#branch_id').val(),
            product_id: $('#product_id').val(), warehouse_id: $('#warehouse_id').val(),
            report_mode: $('#report_mode').val(), group_by: $('#group_by').val(),
            production_id: (new URLSearchParams(window.location.search)).get('production_id') || '',
            start_date: typeof filterStartDate !== 'undefined' ? filterStartDate : '',
            end_date: typeof filterEndDate !== 'undefined' ? filterEndDate : '',
        };
    }
    function buildReportUrl(action) {
        return url_local + '/admin/reports/material-consumption/' + action + '?' + $.param(currentReportParams());
    }
    $(document).ready(function() {
        $('#business_id, #branch_id, #product_id, #warehouse_id, #report_mode, #group_by').select2();
        let q = new URLSearchParams(window.location.search);
        q.forEach(function(v, k) { if ($('#' + k).length) $('#' + k).val(v).trigger('change'); });
    });
    $('#search_btn').click(function() { initDataTablematerial_consumption_report_table(); });
    $('#reset_filter').click(function() {
        $('#business_id, #branch_id, #product_id, #warehouse_id, #report_mode, #group_by').val('').trigger('change');
        $('#report_mode').val('detail').trigger('change');
        $('#group_by').val('detail').trigger('change');
        initDataTablematerial_consumption_report_table();
    });
    $('#print_btn').click(function() { window.open(buildReportUrl('print'), '_blank'); });
    $('#pdf_btn').click(function() { window.open(buildReportUrl('pdf'), '_blank'); });
    $('#export_btn').click(function() { window.location.href = buildReportUrl('export'); });
    $('#export_csv_btn').click(function() { window.location.href = buildReportUrl('export-csv'); });
</script>
@endsection
