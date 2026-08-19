@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Employee Status Report
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
                            <label class="form-label">Department</label>
                            <select id="department_id" class="form-select">
                                <option value="">--All Departments--</option>
                                @foreach ($departments as $item)
                                    <option value="{{ $item->department_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="">--All Statuses--</option>
                                <option value="active">Active</option>
                                <option value="on_leave">On Leave</option>
                                <option value="suspended">Suspended</option>
                                <option value="resigned">Resigned</option>
                                <option value="terminated">Terminated</option>
                            </select>
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
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
                    <div class="col-md-2">
                        <div class="alert alert-success mb-0"><strong>Active:</strong> <span id="total_active_display">-</span></div>
                    </div>
                    <div class="col-md-2">
                        <div class="alert alert-info mb-0"><strong>On Leave:</strong> <span id="total_on_leave_display">-</span></div>
                    </div>
                    <div class="col-md-2">
                        <div class="alert alert-warning mb-0"><strong>Suspended:</strong> <span id="total_suspended_display">-</span></div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-secondary mb-0"><strong>Resigned:</strong> <span id="total_resigned_display">-</span></div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-danger mb-0"><strong>Terminated:</strong> <span id="total_terminated_display">-</span></div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="employee_status_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Employee Code</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Designation</th>
                                <th>Branch</th>
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
                        {data:'employee_code',name:'employee_code',sortable:false},
                        {data:'name',name:'name',sortable:false},
                        {data:'department',name:'department',sortable:false},
                        {data:'designation',name:'designation',sortable:false},
                        {data:'branch',name:'branch',sortable:false},
                        {data:'status',name:'status',sortable:false}",
        'route' => 'employee-status-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'employee_status_report_table',
        'variable' => 'employee_status_report_table',
        'params' => "business_id:$('#business_id').val(),department_id:$('#department_id').val(),status:$('#status').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                department_id: $('#department_id').val() || '',
                status: $('#status').val() || '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #department_id, #status').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTableemployee_status_report_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/employee-status-report/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#total_active_display').text(response.total_active ?? '-');
                    $('#total_on_leave_display').text(response.total_on_leave ?? '-');
                    $('#total_suspended_display').text(response.total_suspended ?? '-');
                    $('#total_resigned_display').text(response.total_resigned ?? '-');
                    $('#total_terminated_display').text(response.total_terminated ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/employee-status-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/employee-status-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/employee-status-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/employee-status-report/export-csv');
        });
    </script>
@endsection
