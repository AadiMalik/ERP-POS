@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_exit.new_heading') }}</h4>

    <div class="card">
        <form action="{{ url('admin/employee-exit') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.employee') }} <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select select2" required>
                            <option value="">{{ __('common.select_employee') }}</option>
                            @foreach ($employees as $item)
                            <option value="{{ $item->employee_id }}" {{ old('employee_id') == $item->employee_id ? 'selected' : '' }}>
                                {{ $item->user->name ?? '-' }} ({{ $item->employee_code }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.type') }} <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="resignation" {{ old('type') == 'resignation' ? 'selected' : '' }}>{{ __('hrm_exit.resignation') }}</option>
                            <option value="termination" {{ old('type') == 'termination' ? 'selected' : '' }}>{{ __('hrm_exit.termination') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('hrm_exit.notice_period_days') }}</label>
                        <input type="number" min="0" class="form-control" name="notice_period_days" value="{{ old('notice_period_days', 30) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('hrm_exit.last_working_date') }}</label>
                        <input type="date" class="form-control" name="last_working_date" value="{{ old('last_working_date') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">{{ __('common.reason') }}</label>
                        <textarea class="form-control" name="reason" rows="3">{{ old('reason') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('common.submit') }}</button>
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
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endsection
