@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_attendance.title') }}</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    {{ __('common.filters') }}
                </button>
            </div>
            <div class="d-flex gap-2">
                @include('admin.partials.import-export-buttons', [
                    'importExportModule' => 'attendance',
                    'importExportLabel' => __('hrm_attendance.title'),
                    'importExportRefreshFn' => 'initDataTableattendance_table',
                ])
                @can('attendance.report.view')
                <a href="{{ url('admin/attendance/report') }}" class="btn btn-outline-primary rounded-pill">
                    <i class="fa fa-chart-bar"></i>
                    {{ __('hrm_attendance.report') }}
                </a>
                @endcan
                @can('attendance.create')
                <a href="{{ url('admin/attendance/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    {{ __('common.add_new') }}
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.employee') }}</label>
                        <select id="employee_id" class="form-select">
                            <option value="">{{ __('common.all_employees') }}</option>
                            @foreach ($employees as $item)
                            <option value="{{ $item->employee_id }}">{{ $item->user->name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.status') }}</label>
                        <select id="status" class="form-select">
                            <option value="">{{ __('common.all_status') }}</option>
                            <option value="present">{{ __('hrm_attendance.present') }}</option>
                            <option value="late">{{ __('hrm_attendance.late') }}</option>
                            <option value="half_day">{{ __('hrm_attendance.half_day') }}</option>
                            <option value="absent">{{ __('hrm_attendance.absent') }}</option>
                            <option value="on_leave">{{ __('hrm_attendance.on_leave') }}</option>
                            <option value="holiday">{{ __('hrm_attendance.holiday') }}</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.date') }}</label>
                        @include('admin.partials.date_filter')
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="attendance_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>{{ __('common.employee') }}</th>
                            <th>{{ __('common.date') }}</th>
                            <th>{{ __('hrm_attendance.check_in') }}</th>
                            <th>{{ __('hrm_attendance.check_out') }}</th>
                            <th>{{ __('hrm_attendance.working_hours') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
    @include('admin.partials.import-export-modal')
</div>
@endsection
@section('js')
@include('admin.partials.datatable', [
'columns' => "
{data:'employee',name:'employee',sortable:false},
{data:'date',name:'date'},
{data:'check_in_time',name:'check_in_time'},
{data:'check_out_time',name:'check_out_time'},
{data:'working_hours',name:'working_hours'},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'attendance/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'attendance_table',
'variable' => 'attendance_table',
'datefilter' => true,
'params' => "employee_id:$('#employee_id').val(),status:$('#status').val()",
])
<script>
    $(document).ready(function() {
        $('#employee_id').select2();
        $('#status').select2();
    });
    $('#search_btn').click(function() {
        initDataTableattendance_table();
    });
    deleteRecord({
        buttonClass: "#deleteAttendance",
        url: url_local + "/admin/attendance",
        tableCallback: function() {
            initDataTableattendance_table();
        }
    });
</script>
@endsection
