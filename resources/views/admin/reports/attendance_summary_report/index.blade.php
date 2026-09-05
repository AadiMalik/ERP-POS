@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.attendance_summary_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.attendance-summary-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.attendance-summary-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.attendance-summary-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.attendance-summary-report.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> {{ __('common.csv') }}
                    </a>
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
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.department') }}</label>
                            <select id="department_id" class="form-select">
                                <option value="">{{ __('common.all_departments') }}</option>
                                @foreach ($departments as $item)
                                    <option value="{{ $item->department_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.employee') }}</label>
                            <select id="employee_id" class="form-select">
                                <option value="">{{ __('common.all_employees') }}</option>
                                @foreach ($employees as $item)
                                    <option value="{{ $item->employee_id }}">{{ $item->user?->name }} ({{ $item->employee_code }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.date_range') }}</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2 mt-3">
                            <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="attendance_summary_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_employee_code') }}</th>
                                <th>{{ __('common.name') }}</th>
                                <th>{{ __('reports.col_department') }}</th>
                                <th>{{ __('reports.col_designation') }}</th>
                                <th class="text-end">{{ __('reports.col_present') }}</th>
                                <th class="text-end">{{ __('reports.col_absent') }}</th>
                                <th class="text-end">{{ __('reports.col_late') }}</th>
                                <th class="text-end">Half Day</th>
                                <th class="text-end">Leave</th>
                                <th class="text-end">Holiday</th>
                                <th class="text-end">Early Checkout</th>
                                <th class="text-end">Working Hours</th>
                                <th class="text-end">Scheduled Days</th>
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
                        {data:'present_count',name:'present_count',sortable:false,className:'text-end'},
                        {data:'absent_count',name:'absent_count',sortable:false,className:'text-end'},
                        {data:'late_count',name:'late_count',sortable:false,className:'text-end'},
                        {data:'half_day_count',name:'half_day_count',sortable:false,className:'text-end'},
                        {data:'leave_count',name:'leave_count',sortable:false,className:'text-end'},
                        {data:'holiday_count',name:'holiday_count',sortable:false,className:'text-end'},
                        {data:'early_checkout_count',name:'early_checkout_count',sortable:false,className:'text-end'},
                        {data:'total_working_hours',name:'total_working_hours',sortable:false,className:'text-end'},
                        {data:'scheduled_working_days',name:'scheduled_working_days',sortable:false,className:'text-end'}",
        'route' => 'attendance-summary-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'attendance_summary_report_table',
        'variable' => 'attendance_summary_report_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),department_id:$('#department_id').val(),employee_id:$('#employee_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                department_id: $('#department_id').val() || '',
                employee_id: $('#employee_id').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #department_id, #employee_id').select2();
        });

        $('#search_btn').click(function() {
            initDataTableattendance_summary_report_table();
        });

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/attendance-summary-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/attendance-summary-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/attendance-summary-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/attendance-summary-report/export-csv');
        });
    </script>
@endsection
