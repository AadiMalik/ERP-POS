@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Manufacturing Plan Report</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <button type="button" id="toggleFilter" class="btn btn-outline-primary"><i class="fa fa-filter"></i> Filters</button>
            <div class="d-flex gap-2">
                @can('reports.manufacturing-plan-report.print')
                <button type="button" id="print_btn" class="btn btn-outline-secondary"><i class="fa fa-print"></i> Print</button>
                @endcan
                @can('reports.manufacturing-plan-report.pdf')
                <button type="button" id="pdf_btn" class="btn btn-outline-danger"><i class="fa fa-file-pdf"></i> PDF</button>
                @endcan
                @can('reports.manufacturing-plan-report.export')
                <button type="button" id="export_btn" class="btn btn-outline-success"><i class="fa fa-file-excel"></i> Excel</button>
                @endcan
                @can('reports.manufacturing-plan-report.export-csv')
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
                        <label class="form-label">Status</label>
                        <select id="status" class="form-select">
                            <option value="">--All Statuses--</option>
                            @foreach ($statuses as $value => $label)<option value="{{ $value }}">{{ $label }}</option>@endforeach
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
                <table id="mfg_plan_report_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Business</th><th>Branch</th><th>Product</th><th>Plan Date</th>
                            <th>Planned Qty</th><th>Produced Qty</th><th>Remaining Qty</th><th>Progress</th><th>Status</th>
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
{data:'business',name:'business',sortable:false},
{data:'branch',name:'branch',sortable:false},
{data:'product',name:'product',sortable:false},
{data:'plan_date',name:'plan_date'},
{data:'planned_quantity',name:'planned_quantity'},
{data:'produced_quantity',name:'produced_quantity'},
{data:'remaining_quantity',name:'remaining_quantity',sortable:false},
{data:'progress',name:'progress',sortable:false},
{data:'status',name:'status',sortable:false}",
'route' => 'manufacturing-plan-report/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'mfg_plan_report_table',
'variable' => 'mfg_plan_report_table',
'datefilter' => true,
'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),status:$('#status').val()",
])
<script>
    function currentReportParams() {
        return {
            business_id: $('#business_id').val(),
            branch_id: $('#branch_id').val(),
            status: $('#status').val(),
            start_date: typeof filterStartDate !== 'undefined' ? filterStartDate : '',
            end_date: typeof filterEndDate !== 'undefined' ? filterEndDate : '',
        };
    }
    function buildReportUrl(action) {
        return url_local + '/admin/reports/manufacturing-plan/' + action + '?' + $.param(currentReportParams());
    }
    $(document).ready(function() {
        $('#business_id, #branch_id, #status').select2();
    });
    $('#search_btn').click(function() { initDataTablemfg_plan_report_table(); });
    $('#reset_filter').click(function() {
        $('#business_id, #branch_id, #status').val('').trigger('change');
        initDataTablemfg_plan_report_table();
    });
    $('#print_btn').click(function() { window.open(buildReportUrl('print'), '_blank'); });
    $('#pdf_btn').click(function() { window.open(buildReportUrl('pdf'), '_blank'); });
    $('#export_btn').click(function() { window.location.href = buildReportUrl('export'); });
    $('#export_csv_btn').click(function() { window.location.href = buildReportUrl('export-csv'); });
</script>
@endsection
