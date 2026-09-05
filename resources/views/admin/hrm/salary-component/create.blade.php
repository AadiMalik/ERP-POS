@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_salary_components.singular') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($salary_component) ? __('hrm_salary_components.update_heading') : __('hrm_salary_components.new_heading') }}</h5>
        </div>

        <form action="{{ url('admin/salary-component') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="salary_component_id" value="{{ $salary_component->salary_component_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $salary_component->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.code') }}</label>
                        <input type="text" class="form-control" name="code" value="{{ old('code', $salary_component->code ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.type') }} <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="earning" {{ old('type', $salary_component->type ?? '') == 'earning' ? 'selected' : '' }}>{{ __('hrm_salary_components.earning') }}</option>
                            <option value="deduction" {{ old('type', $salary_component->type ?? '') == 'deduction' ? 'selected' : '' }}>{{ __('hrm_salary_components.deduction') }}</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('hrm_salary_components.calculation') }} <span class="text-danger">*</span></label>
                        <select name="calculation_type" class="form-select" required>
                            <option value="fixed" {{ old('calculation_type', $salary_component->calculation_type ?? '') == 'fixed' ? 'selected' : '' }}>{{ __('hrm_salary_components.fixed_amount') }}</option>
                            <option value="percentage_of_basic" {{ old('calculation_type', $salary_component->calculation_type ?? '') == 'percentage_of_basic' ? 'selected' : '' }}>{{ __('hrm_salary_components.percentage_of_basic') }}</option>
                        </select>
                    </div>
                    @if (isset($salary_component))
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.status') }}</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ ($salary_component->status ?? 'active') == 'active' ? 'selected' : '' }}>{{ __('common.active') }}</option>
                            <option value="inactive" {{ ($salary_component->status ?? '') == 'inactive' ? 'selected' : '' }}>{{ __('common.inactive') }}</option>
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
@if(session('error'))
<script>
    errorMessage("{{ session('error') }}");
</script>
@endif
@endsection
