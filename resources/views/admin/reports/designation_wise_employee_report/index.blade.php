@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Designation-wise Employee Report
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
                    @canAccess('reports.designation-wise-employee-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.designation-wise-employee-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.designation-wise-employee-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.designation-wise-employee-report.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> CSV
                    </a>
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
                            <label class="form-label">Designation</label>
                            <select id="designation_id" class="form-select">
                                <option value="">--All Designations--</option>
                                @foreach ($designations as $item)
                                    <option value="{{ $item->designation_id }}">{{ $item->name }}</option>
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
                    <table id="designation_wise_employee_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Designation</th>
                                <th>Department</th>
                                <th class="text-end">Total Employees</th>
                                <th class="text-end">Active</th>
                                <th class="text-end">On Leave</th>
                                <th class="text-end">Resigned</th>
                                <th class="text-end">Terminated</th>
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
                        {data:'designation',name:'designation',sortable:false},
                        {data:'department',name:'department',sortable:false},
                        {data:'total_employees',name:'total_employees',sortable:false,className:'text-end'},
                        {data:'active_employees',name:'active_employees',sortable:false,className:'text-end'},
                        {data:'on_leave_employees',name:'on_leave_employees',sortable:false,className:'text-end'},
                        {data:'resigned_employees',name:'resigned_employees',sortable:false,className:'text-end'},
                        {data:'terminated_employees',name:'terminated_employees',sortable:false,className:'text-end'}",
        'route' => 'designation-wise-employee-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'designation_wise_employee_report_table',
        'variable' => 'designation_wise_employee_report_table',
        'params' => "business_id:$('#business_id').val(),department_id:$('#department_id').val(),designation_id:$('#designation_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                department_id: $('#department_id').val() || '',
                designation_id: $('#designation_id').val() || '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #department_id, #designation_id').select2();
        });

        $('#search_btn').click(function() {
            initDataTabledesignation_wise_employee_report_table();
        });

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/designation-wise-employee-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/designation-wise-employee-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/designation-wise-employee-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/designation-wise-employee-report/export-csv');
        });
    </script>
@endsection
