@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_deductions.singular') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($employee_deduction) ? __('hrm_deductions.update_heading') : __('hrm_deductions.new_heading') }}</h5>
        </div>

        <form action="{{ url('admin/employee-deduction') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="employee_deduction_id" value="{{ $employee_deduction->employee_deduction_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.employee') }} <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select select2" required {{ isset($employee_deduction) ? 'disabled' : '' }}>
                            <option value="">{{ __('common.select_employee') }}</option>
                            @foreach ($employees as $item)
                            <option value="{{ $item->employee_id }}" {{ old('employee_id', $employee_deduction->employee_id ?? '') == $item->employee_id ? 'selected' : '' }}>
                                {{ $item->user->name ?? '-' }} ({{ $item->employee_code }})
                            </option>
                            @endforeach
                        </select>
                        @if (isset($employee_deduction))
                        <input type="hidden" name="employee_id" value="{{ $employee_deduction->employee_id }}">
                        @endif
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.title') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="title" value="{{ old('title', $employee_deduction->title ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('common.amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="amount" value="{{ old('amount', $employee_deduction->amount ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_deductions.effective_from') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="effective_from" value="{{ old('effective_from', $employee_deduction->effective_from ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_deductions.effective_to') }}</label>
                        <input type="date" class="form-control" name="effective_to" value="{{ old('effective_to', $employee_deduction->effective_to ?? '') }}">
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_recurring" id="is_recurring" value="1" {{ old('is_recurring', $employee_deduction->is_recurring ?? false) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_recurring">{{ __('hrm_deductions.is_recurring') }}</label>
                        </div>
                    </div>
                    @if (isset($employee_deduction))
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.status') }}</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ ($employee_deduction->status ?? 'active') == 'active' ? 'selected' : '' }}>{{ __('common.active') }}</option>
                            <option value="inactive" {{ ($employee_deduction->status ?? '') == 'inactive' ? 'selected' : '' }}>{{ __('common.inactive') }}</option>
                        </select>
                    </div>
                    @endif
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
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endsection
