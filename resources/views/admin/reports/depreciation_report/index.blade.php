@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Depreciation Report</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i> Filters
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.depreciation-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary"><i class="fa fa-print"></i> Print</a>
                    @endcanAccess
                    @canAccess('reports.depreciation-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger"><i class="fa fa-file-pdf"></i> PDF</a>
                    @endcanAccess
                    @canAccess('reports.depreciation-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success"><i class="fa fa-file-excel"></i> Excel</a>
                    @endcanAccess
                    @canAccess('reports.depreciation-report.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success"><i class="fa fa-file-text"></i> CSV</a>
                    @endcanAccess
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-3">
                            <label class="form-label">Business</label>
                            <select id="business_id" class="form-select">
                                <option value="">--All Businesses--</option>
                                @foreach ($business as $item)
                                <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select id="branch_id" class="form-select">
                                <option value="">--All Branches--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                @foreach ($branches as $item)
                                <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Period</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                        </div>
                    </div>
                </div>
                <div class="row g-3 p-4 pb-0">
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>Total Depreciation:</strong> <span id="total_depreciation_amount_display">-</span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="depreciation_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Period</th>
                                <th>Asset Code</th>
                                <th>Asset Name</th>
                                <th>Branch</th>
                                <th class="text-end">Previous</th>
                                <th class="text-end">Amount</th>
                                <th class="text-end">New Value</th>
                                <th class="text-end">Accumulated</th>
                                <th>JV</th>
                                <th>Status</th>
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
            {data:'depreciation_date',name:'depreciation_date',sortable:false},
            {data:'period_key',name:'period_key',sortable:false},
            {data:'asset_code',name:'asset_code',sortable:false},
            {data:'asset_name',name:'asset_name',sortable:false},
            {data:'branch',name:'branch',sortable:false},
            {data:'previous_value',name:'previous_value',sortable:false,className:'text-end'},
            {data:'depreciation_amount',name:'depreciation_amount',sortable:false,className:'text-end'},
            {data:'new_value',name:'new_value',sortable:false,className:'text-end'},
            {data:'accumulated_depreciation',name:'accumulated_depreciation',sortable:false,className:'text-end'},
            {data:'journal_entry',name:'journal_entry',sortable:false},
            {data:'status',name:'status',sortable:false}",
        'route' => 'depreciation-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'depreciation_report_table',
        'variable' => 'depreciation_report_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val()",
    ])
    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }
        function buildReportUrl(path) {
            return url_local + path + '?' + $.param(currentReportParams());
        }
        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/depreciation-report/data',
                type: 'POST',
                data: $.extend({ _token: $('meta[name="csrf-token"]').attr('content'), draw: 1, start: 0, length: 1 }, currentReportParams()),
                success: function(response) {
                    $('#total_depreciation_amount_display').text(response.total_depreciation_amount ?? '-');
                }
            });
        }
        $(document).ready(function() {
            $('#business_id, #branch_id').select2();
            refreshTotals();
        });
        $('#search_btn').click(function() { initDataTabledepreciation_report_table(); refreshTotals(); });
        $('#btn_print').click(function() { window.open(buildReportUrl('/admin/reports/depreciation-report/print'), '_blank'); });
        $('#btn_pdf').click(function() { window.open(buildReportUrl('/admin/reports/depreciation-report/pdf'), '_blank'); });
        $('#btn_excel').click(function() { window.location.href = buildReportUrl('/admin/reports/depreciation-report/export'); });
        $('#btn_csv').click(function() { window.location.href = buildReportUrl('/admin/reports/depreciation-report/export-csv'); });
    </script>
@endsection
