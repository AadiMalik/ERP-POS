@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.shift_wise_attendance_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.shift-wise-attendance-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.shift-wise-attendance-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.shift-wise-attendance-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.shift-wise-attendance-report.export-csv')
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
                            <label class="form-label">{{ __('reports.shift') }}</label>
                            <select id="shift_id" class="form-select">
                                <option value="">--All Shifts--</option>
                                @foreach ($shifts as $item)
                                    <option value="{{ $item->shift_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
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
                    <table id="shift_wise_attendance_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_shift') }}</th>
                                <th>{{ __('reports.col_timing') }}</th>
                                <th class="text-end">Employees</th>
                                <th class="text-end">{{ __('reports.col_present') }}</th>
                                <th class="text-end">{{ __('reports.col_absent') }}</th>
                                <th class="text-end">{{ __('reports.col_late') }}</th>
                                <th class="text-end">Leave</th>
                                <th class="text-end">Working Hours</th>
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
                        {data:'shift_name',name:'shift_name',sortable:false},
                        {data:'timing',name:'timing',sortable:false},
                        {data:'employee_count',name:'employee_count',sortable:false,className:'text-end'},
                        {data:'present_count',name:'present_count',sortable:false,className:'text-end'},
                        {data:'absent_count',name:'absent_count',sortable:false,className:'text-end'},
                        {data:'late_count',name:'late_count',sortable:false,className:'text-end'},
                        {data:'leave_count',name:'leave_count',sortable:false,className:'text-end'},
                        {data:'total_working_hours',name:'total_working_hours',sortable:false,className:'text-end'}",
        'route' => 'shift-wise-attendance-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'shift_wise_attendance_report_table',
        'variable' => 'shift_wise_attendance_report_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),shift_id:$('#shift_id').val(),department_id:$('#department_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                shift_id: $('#shift_id').val() || '',
                department_id: $('#department_id').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #shift_id, #department_id').select2();
        });

        $('#search_btn').click(function() {
            initDataTableshift_wise_attendance_report_table();
        });

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/shift-wise-attendance-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/shift-wise-attendance-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/shift-wise-attendance-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/shift-wise-attendance-report/export-csv');
        });
    </script>
@endsection
