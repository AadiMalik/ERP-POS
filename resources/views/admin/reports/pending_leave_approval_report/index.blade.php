@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Pending Leave Approval Report
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
                            <label class="form-label">Leave Type</label>
                            <select id="leave_type_id" class="form-select">
                                <option value="">--All Leave Types--</option>
                                @foreach ($leaveTypes as $item)
                                    <option value="{{ $item->leave_type_id }}">{{ $item->name }}</option>
                                @endforeach
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

                <div class="table-responsive p-4">
                    <table id="pending_leave_approval_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Employee Code</th>
                                <th>Name</th>
                                <th>Department</th>
                                <th>Leave Type</th>
                                <th>Start Date</th>
                                <th>End Date</th>
                                <th class="text-end">Days</th>
                                <th>Requested On</th>
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
                        {data:'leave_type',name:'leave_type',sortable:false},
                        {data:'start_date',name:'start_date',sortable:false},
                        {data:'end_date',name:'end_date',sortable:false},
                        {data:'days_count',name:'days_count',sortable:false,className:'text-end'},
                        {data:'requested_on',name:'requested_on',sortable:false}",
        'route' => 'pending-leave-approval-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'pending_leave_approval_report_table',
        'variable' => 'pending_leave_approval_report_table',
        'params' => "business_id:$('#business_id').val(),department_id:$('#department_id').val(),leave_type_id:$('#leave_type_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                department_id: $('#department_id').val() || '',
                leave_type_id: $('#leave_type_id').val() || '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #department_id, #leave_type_id').select2();
        });

        $('#search_btn').click(function() {
            initDataTablepending_leave_approval_report_table();
        });

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/pending-leave-approval-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/pending-leave-approval-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/pending-leave-approval-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/pending-leave-approval-report/export-csv');
        });
    </script>
@endsection
