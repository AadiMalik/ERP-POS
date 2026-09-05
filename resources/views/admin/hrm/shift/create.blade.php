@php
    $days = [
        'mon' => __('hrm_shifts.day_mon'),
        'tue' => __('hrm_shifts.day_tue'),
        'wed' => __('hrm_shifts.day_wed'),
        'thu' => __('hrm_shifts.day_thu'),
        'fri' => __('hrm_shifts.day_fri'),
        'sat' => __('hrm_shifts.day_sat'),
        'sun' => __('hrm_shifts.day_sun'),
    ];
    $selected_days = old('working_days', $shift->working_days ?? []);
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_shifts.singular') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($shift) ? __('hrm_shifts.update_heading') : __('hrm_shifts.new_heading') }}</h5>
        </div>

        <form action="{{ url('admin/shift') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="shift_id" value="{{ $shift->shift_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $shift->name ?? '') }}" required>
                    </div>
                    @if (isset($shift))
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.status') }}</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ ($shift->status ?? 'active') == 'active' ? 'selected' : '' }}>{{ __('common.active') }}</option>
                            <option value="inactive" {{ ($shift->status ?? '') == 'inactive' ? 'selected' : '' }}>{{ __('common.inactive') }}</option>
                        </select>
                    </div>
                    @endif
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_shifts.start_time') }} <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" name="start_time" value="{{ old('start_time', $shift->start_time ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_shifts.end_time') }} <span class="text-danger">*</span></label>
                        <input type="time" class="form-control" name="end_time" value="{{ old('end_time', $shift->end_time ?? '') }}" required>
                    </div>
                    <div class="col-md-4"></div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_shifts.break_duration_minutes') }}</label>
                        <input type="number" min="0" class="form-control" name="break_duration_minutes" value="{{ old('break_duration_minutes', $shift->break_duration_minutes ?? 0) }}">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_shifts.grace_period_minutes') }}</label>
                        <input type="number" min="0" class="form-control" name="grace_period_minutes" value="{{ old('grace_period_minutes', $shift->grace_period_minutes ?? 0) }}">
                        <small class="form-text text-muted">{{ __('hrm_shifts.grace_period_hint') }}</small>
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold d-block">{{ __('hrm_shifts.working_days') }} <span class="text-danger">*</span></label>
                        @foreach ($days as $key => $label)
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="working_days[]" value="{{ $key }}" id="day_{{ $key }}"
                                {{ in_array($key, $selected_days) ? 'checked' : '' }}>
                            <label class="form-check-label" for="day_{{ $key }}">{{ $label }}</label>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('hrm_shifts.save_shift') }}</button>
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
@endsection
