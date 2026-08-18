@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Attendance</h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    Filters
                </button>
            </div>
            <div class="d-flex gap-2">
                @can('attendance.report.view')
                <a href="{{ url('admin/attendance/report') }}" class="btn btn-outline-primary rounded-pill">
                    <i class="fa fa-chart-bar"></i>
                    Report
                </a>
                @endcan
                @can('attendance.create')
                <a href="{{ url('admin/attendance/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    Add New
                </a>
                @endcan
            </div>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Employee</label>
                        <select id="employee_id" class="form-select">
                            <option value="">--All Employees--</option>
                            @foreach ($employees as $item)
                            <option value="{{ $item->employee_id }}">{{ $item->user->name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Status</label>
                        <select id="status" class="form-select">
                            <option value="">--All Status--</option>
                            <option value="present">Present</option>
                            <option value="late">Late</option>
                            <option value="half_day">Half Day</option>
                            <option value="absent">Absent</option>
                            <option value="on_leave">On Leave</option>
                            <option value="holiday">Holiday</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Date</label>
                        @include('admin.partials.date_filter')
                    </div>
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                        <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                    </div>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="attendance_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Employee</th>
                            <th>Date</th>
                            <th>Check In</th>
                            <th>Check Out</th>
                            <th>Working Hours</th>
                            <th>Status</th>
                            <th>Action</th>
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
