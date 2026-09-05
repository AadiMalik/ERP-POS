@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_attendance.report') }}</h4>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" action="{{ url('admin/attendance/report') }}" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label">{{ __('common.employee') }}</label>
                    <select name="employee_id" class="form-select select2" required>
                        <option value="">{{ __('common.select_employee') }}</option>
                        @foreach ($employees as $item)
                        <option value="{{ $item->employee_id }}" {{ request('employee_id') == $item->employee_id ? 'selected' : '' }}>
                            {{ $item->user->name ?? '-' }} ({{ $item->employee_code }})
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('common.month') }}</label>
                    <select name="month" class="form-select">
                        @foreach (range(1, 12) as $m)
                        <option value="{{ $m }}" {{ $month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('common.year') }}</label>
                    <select name="year" class="form-select">
                        @foreach (range(now()->year, now()->year - 5) as $y)
                        <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button class="btn btn-primary w-100">{{ __('common.view') }}</button>
                </div>
            </form>
        </div>
    </div>

    @if (!empty($summary))
    <div class="row g-3">
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('hrm_attendance.present_days') }}</div><h4>{{ $summary['present_days'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('hrm_attendance.absent_days') }}</div><h4>{{ $summary['absent_days'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('hrm_attendance.leave_days') }}</div><h4>{{ $summary['leave_days'] }}</h4></div></div></div>
        <div class="col-md-3"><div class="card"><div class="card-body"><div class="text-muted">{{ __('hrm_attendance.late_days') }}</div><h4>{{ $summary['late_days'] }}</h4></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">{{ __('hrm_attendance.total_working_hours') }}</div><h4>{{ $summary['total_working_hours'] }}</h4></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">{{ __('hrm_attendance.overtime_hours') }}</div><h4>{{ $summary['overtime_hours'] }}</h4></div></div></div>
        <div class="col-md-4"><div class="card"><div class="card-body"><div class="text-muted">{{ __('hrm_attendance.standard_daily_hours') }}</div><h4>{{ $summary['standard_daily_hours'] }}</h4></div></div></div>
    </div>
    @endif
</div>
@endsection

@section('js')
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endsection
