@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Employee Ledger Report
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
                            <label class="form-label">Employee</label>
                            <select id="employee_id" class="form-select">
                                <option value="">--All Employees--</option>
                                @foreach ($employees as $item)
                                    <option value="{{ $item->employee_id }}">{{ $item->user?->name }} ({{ $item->employee_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Type</label>
                            <select id="type" class="form-select">
                                <option value="">--All Types--</option>
                                <option value="advance">Advance</option>
                                <option value="deduction">Deduction</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date Range</label>
                            @include('admin.partials.date_filter')
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
                        <div class="alert alert-info mb-0"><strong>Total Debit:</strong> <span id="total_debit_display">-</span></div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-secondary mb-0"><strong>Total Credit:</strong> <span id="total_credit_display">-</span></div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="employee_ledger_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Employee Code</th>
                                <th>Name</th>
                                <th>Entry Date</th>
                                <th>Type</th>
                                <th>Description</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Balance After</th>
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
                        {data:'employee_code',name:'employee_code',sortable:false},
                        {data:'name',name:'name',sortable:false},
                        {data:'entry_date',name:'entry_date',sortable:false},
                        {data:'type',name:'type',sortable:false},
                        {data:'description',name:'description',sortable:false},
                        {data:'debit',name:'debit',sortable:false,className:'text-end'},
                        {data:'credit',name:'credit',sortable:false,className:'text-end'},
                        {data:'balance_after',name:'balance_after',sortable:false,className:'text-end'}",
        'route' => 'employee-ledger-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'employee_ledger_report_table',
        'variable' => 'employee_ledger_report_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),employee_id:$('#employee_id').val(),type:$('#type').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                employee_id: $('#employee_id').val() || '',
                type: $('#type').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #employee_id, #type').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTableemployee_ledger_report_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/employee-ledger-report/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#total_debit_display').text(response.total_debit ?? '-');
                    $('#total_credit_display').text(response.total_credit ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/employee-ledger-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/employee-ledger-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/employee-ledger-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/employee-ledger-report/export-csv');
        });
    </script>
@endsection
