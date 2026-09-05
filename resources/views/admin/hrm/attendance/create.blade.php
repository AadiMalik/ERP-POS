@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_attendance.title') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($attendance) ? __('hrm_attendance.update_record_heading') : __('hrm_attendance.new_record_heading') }}</h5>
        </div>

        <form action="{{ url('admin/attendance') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="attendance_id" value="{{ $attendance->attendance_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('common.employee') }} <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select select2" required {{ isset($attendance) ? 'disabled' : '' }}>
                            <option value="">{{ __('common.select_employee') }}</option>
                            @foreach ($employees as $item)
                            <option value="{{ $item->employee_id }}" {{ old('employee_id', $attendance->employee_id ?? '') == $item->employee_id ? 'selected' : '' }}>
                                {{ $item->user->name ?? '-' }} ({{ $item->employee_code }})
                            </option>
                            @endforeach
                        </select>
                        @if (isset($attendance))
                        <input type="hidden" name="employee_id" value="{{ $attendance->employee_id }}">
                        @endif
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('common.date') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="date" value="{{ old('date', $attendance->date ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('common.status') }} <span class="text-danger">*</span></label>
                        <select name="status" class="form-select" required>
                            @foreach ([
                                'present' => __('hrm_attendance.present'),
                                'late' => __('hrm_attendance.late'),
                                'half_day' => __('hrm_attendance.half_day'),
                                'absent' => __('hrm_attendance.absent'),
                                'on_leave' => __('hrm_attendance.on_leave'),
                                'holiday' => __('hrm_attendance.holiday'),
                            ] as $key => $label)
                            <option value="{{ $key }}" {{ old('status', $attendance->status ?? '') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_attendance.check_in_time') }}</label>
                        <input type="time" class="form-control" name="check_in_time" value="{{ old('check_in_time', $attendance->check_in_time ?? '') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_attendance.check_out_time') }}</label>
                        <input type="time" class="form-control" name="check_out_time" value="{{ old('check_out_time', $attendance->check_out_time ?? '') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">{{ __('common.notes') }}</label>
                        <textarea class="form-control" name="notes" rows="2">{{ old('notes', $attendance->notes ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('common.save') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
@if ($errors->any())
<script>
    errorMessage("{{ $errors->first() }}");
</script>
@endif
@if(session('error'))
<script>
    errorMessage("{{ session('error') }}");
</script>
@endif
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endsection
