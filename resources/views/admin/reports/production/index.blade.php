@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Production Report</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <button type="button" id="toggleFilter" class="btn btn-outline-primary"><i class="fa fa-filter"></i> Filters</button>
            <div class="d-flex gap-2">
                @can('reports.production-report.print')
                <button type="button" id="print_btn" class="btn btn-outline-secondary"><i class="fa fa-print"></i> Print</button>
                @endcan
                @can('reports.production-report.pdf')
                <button type="button" id="pdf_btn" class="btn btn-outline-danger"><i class="fa fa-file-pdf"></i> PDF</button>
                @endcan
                @can('reports.production-report.export')
                <button type="button" id="export_btn" class="btn btn-outline-success"><i class="fa fa-file-excel"></i> Excel</button>
                @endcan
                @can('reports.production-report.export-csv')
                <button type="button" id="export_csv_btn" class="btn btn-outline-info"><i class="fa fa-file-csv"></i> CSV</button>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-3">
                        <label class="form-label">Business</label>
                        <select id="business_id" class="form-select">
                            <option value="">--All Businesses--</option>
                            @foreach ($business as $item)<option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>@endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select id="branch_id" class="form-select">
                            <option value="">--All Branches--</option>
                            @foreach ($branches as $item)<option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Warehouse</label>
                        <select id="warehouse_id" class="form-select">
                            <option value="">--All Warehouses--</option>
                            @foreach ($warehouses as $item)<option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="status" class="form-select">
                            <option value="">--All Statuses--</option>
                            @foreach ($statuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Batch No.</label>
                        <input type="text" id="batch_no" class="form-control">
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Report View</label>
                        <select id="report_mode" class="form-select">
                            <option value="summary">Summary &amp; Detail</option>
                            <option value="performance">Performance &amp; Yield</option>
                            <option value="costing">Production Costing</option>
                            <option value="variance">Production Variance</option>
                            <option value="wastage_scrap">Wastage &amp; Scrap (Proxy)</option>
                            <option value="traceability">Traceability</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date Range</label>
                        @include('admin.partials.date_filter')
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="production_report_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Production #</th><th>Plan #</th><th>Business</th><th>Branch</th><th>Product</th><th>Warehouse</th>
                            <th>Batch</th><th>Qty</th><th>Planned</th><th>Yield %</th><th>Variance</th>
                            <th>Material</th><th>Labor</th><th>Overhead</th><th>Other</th><th>Total</th><th>Unit</th>
                            <th>Expected Mat.</th><th>Actual Mat.</th><th>Wastage</th><th>Consumptions</th><th>Hours</th><th>Status</th>
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
{data:'production_no',name:'production_no',sortable:false},
{data:'plan_no',name:'plan_no',sortable:false},
{data:'business',name:'business',sortable:false},
{data:'branch',name:'branch',sortable:false},
{data:'product',name:'product',sortable:false},
{data:'warehouse',name:'warehouse',sortable:false},
{data:'batch_no',name:'batch_no',sortable:false},
{data:'quantity',name:'quantity'},
{data:'planned_quantity',name:'planned_quantity',sortable:false},
{data:'yield_pct',name:'yield_pct',sortable:false},
{data:'qty_variance',name:'qty_variance',sortable:false},
{data:'material_cost',name:'material_cost'},
{data:'labor_cost',name:'labor_cost',sortable:false},
{data:'overhead_cost',name:'overhead_cost',sortable:false},
{data:'other_cost',name:'other_cost',sortable:false},
{data:'total_cost',name:'total_cost'},
{data:'unit_cost',name:'unit_cost'},
{data:'expected_material_qty',name:'expected_material_qty',sortable:false},
{data:'actual_material_qty',name:'actual_material_qty',sortable:false},
{data:'wastage_qty',name:'wastage_qty',sortable:false},
{data:'consumption_count',name:'consumption_count',sortable:false},
{data:'duration_hours',name:'duration_hours',sortable:false},
{data:'status',name:'status',sortable:false}",
'route' => 'production/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'production_report_table',
'variable' => 'production_report_table',
'datefilter' => true,
'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),warehouse_id:$('#warehouse_id').val(),status:$('#status').val(),batch_no:$('#batch_no').val(),report_mode:$('#report_mode').val()",
])
<script>
    function currentReportParams() {
        return {
            business_id: $('#business_id').val(), branch_id: $('#branch_id').val(),
            warehouse_id: $('#warehouse_id').val(), status: $('#status').val(),
            batch_no: $('#batch_no').val(), report_mode: $('#report_mode').val(),
            start_date: typeof filterStartDate !== 'undefined' ? filterStartDate : '',
            end_date: typeof filterEndDate !== 'undefined' ? filterEndDate : '',
        };
    }
    function buildReportUrl(action) {
        return url_local + '/admin/reports/production/' + action + '?' + $.param(currentReportParams());
    }
    $(document).ready(function() { $('#business_id, #branch_id, #warehouse_id, #status, #report_mode').select2(); });
    $('#search_btn').click(function() { initDataTableproduction_report_table(); });
    $('#reset_filter').click(function() {
        $('#business_id, #branch_id, #warehouse_id, #status, #report_mode').val('').trigger('change');
        $('#report_mode').val('summary').trigger('change');
        $('#batch_no').val('');
        initDataTableproduction_report_table();
    });
    $('#print_btn').click(function() { window.open(buildReportUrl('print'), '_blank'); });
    $('#pdf_btn').click(function() { window.open(buildReportUrl('pdf'), '_blank'); });
    $('#export_btn').click(function() { window.location.href = buildReportUrl('export'); });
    $('#export_csv_btn').click(function() { window.location.href = buildReportUrl('export-csv'); });
</script>
@endsection
