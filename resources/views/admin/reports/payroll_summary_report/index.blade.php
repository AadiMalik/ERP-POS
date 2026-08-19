@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Payroll Summary Report
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>
                </div>
                <div class="d-flex gap-2">
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> CSV
                    </a>
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
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select id="branch_id" class="form-select">
                                <option value="">--All Branches--</option>
                                @foreach ($branches as $item)
                                    <option value="{{ $item->branch_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Month</label>
                            <select id="month" class="form-select">
                                <option value="">--All--</option>
                                @foreach (range(1, 12) as $m)
                                    <option value="{{ $m }}">{{ \Carbon\Carbon::create()->month($m)->format('F') }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Year</label>
                            <select id="year" class="form-select">
                                <option value="">--All--</option>
                                @foreach (range(now()->year, now()->year - 5) as $y)
                                    <option value="{{ $y }}">{{ $y }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="">--All--</option>
                                <option value="draft">Draft</option>
                                <option value="finalized">Finalized</option>
                                <option value="paid">Paid</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2 mt-3">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                Search
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 p-4 pb-0">
                    <div class="col-md-4">
                        <div class="alert alert-success mb-0"><strong>Total Net Salary:</strong> <span id="total_net_salary_display">-</span></div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="payroll_summary_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Period</th>
                                <th class="text-end">Employees</th>
                                <th class="text-end">Gross Salary</th>
                                <th class="text-end">Deductions</th>
                                <th class="text-end">Advance Deduction</th>
                                <th class="text-end">Overtime</th>
                                <th class="text-end">Net Salary</th>
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
                        {data:'period',name:'period',sortable:false},
                        {data:'employee_count',name:'employee_count',sortable:false,className:'text-end'},
                        {data:'total_gross',name:'total_gross',sortable:false,className:'text-end'},
                        {data:'total_deductions',name:'total_deductions',sortable:false,className:'text-end'},
                        {data:'total_advance_deduction',name:'total_advance_deduction',sortable:false,className:'text-end'},
                        {data:'total_overtime',name:'total_overtime',sortable:false,className:'text-end'},
                        {data:'total_net_salary',name:'total_net_salary',sortable:false,className:'text-end'},
                        {data:'status',name:'status',sortable:false}",
        'route' => 'payroll-summary-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'payroll_summary_report_table',
        'variable' => 'payroll_summary_report_table',
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),month:$('#month').val(),year:$('#year').val(),status:$('#status').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                month: $('#month').val() || '',
                year: $('#year').val() || '',
                status: $('#status').val() || '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #branch_id, #month, #year, #status').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTablepayroll_summary_report_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/payroll-summary-report/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#total_net_salary_display').text(response.total_net_salary ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/payroll-summary-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/payroll-summary-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/payroll-summary-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/payroll-summary-report/export-csv');
        });
    </script>
@endsection
