@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_salary_structures.structure_for', ['name' => $employee->user->name ?? '-']) }}</h4>

    <div class="card mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ __('hrm_salary_structures.assign_new_structure') }}</h5>
        </div>
        <form action="{{ url('admin/salary-structure/' . $employee->employee_id) }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row g-4 mb-4">
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_salary_structures.effective_from') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="effective_from" value="{{ old('effective_from', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_salary_structures.basic_salary') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="basic_salary" value="{{ old('basic_salary', $current->basic_salary ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_salary_structures.overtime_rate_per_hour') }}</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="overtime_rate_per_hour" value="{{ old('overtime_rate_per_hour', $current->overtime_rate_per_hour ?? '') }}">
                    </div>
                </div>

                <h6 class="fw-bold">{{ __('hrm_salary_structures.earnings') }}</h6>
                <div class="row g-3 mb-4">
                    @forelse ($earning_components as $component)
                    @php
                        $existing = $current?->items->firstWhere('salary_component_id', $component->salary_component_id);
                    @endphp
                    <div class="col-md-4">
                        <label class="form-label">{{ $component->name }} @if($component->calculation_type == 'percentage_of_basic'){{ __('hrm_salary_structures.percent_of_basic') }}@endif</label>
                        <div class="input-group">
                            <input type="hidden" name="salary_component_id[]" value="{{ $component->salary_component_id }}">
                            <input type="number" step="0.01" min="0" class="form-control" name="amount_or_percentage[]" value="{{ $existing->amount_or_percentage ?? '' }}">
                        </div>
                    </div>
                    @empty
                    <div class="col-12 text-muted">{{ __('hrm_salary_structures.no_earning_components') }}</div>
                    @endforelse
                </div>

                <h6 class="fw-bold">{{ __('hrm_salary_structures.deductions') }}</h6>
                <div class="row g-3">
                    @forelse ($deduction_components as $component)
                    @php
                        $existing = $current?->items->firstWhere('salary_component_id', $component->salary_component_id);
                    @endphp
                    <div class="col-md-4">
                        <label class="form-label">{{ $component->name }} @if($component->calculation_type == 'percentage_of_basic'){{ __('hrm_salary_structures.percent_of_basic') }}@endif</label>
                        <input type="hidden" name="salary_component_id[]" value="{{ $component->salary_component_id }}">
                        <input type="number" step="0.01" min="0" class="form-control" name="amount_or_percentage[]" value="{{ $existing->amount_or_percentage ?? '' }}">
                    </div>
                    @empty
                    <div class="col-12 text-muted">{{ __('hrm_salary_structures.no_deduction_components') }}</div>
                    @endforelse
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('hrm_salary_structures.save_new_version') }}</button>
                </div>
            </div>
        </form>
    </div>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ __('hrm_salary_structures.history') }}</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>{{ __('hrm_salary_structures.effective_from') }}</th>
                            <th>{{ __('hrm_salary_structures.basic_salary') }}</th>
                            <th>{{ __('hrm_salary_structures.overtime_rate') }}</th>
                            <th>{{ __('common.status') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($history as $row)
                        <tr>
                            <td>{{ $row->effective_from }}</td>
                            <td>{{ number_format($row->basic_salary, 2) }}</td>
                            <td>{{ $row->overtime_rate_per_hour ? number_format($row->overtime_rate_per_hour, 2) : '-' }}</td>
                            <td>
                                @if ($row->status == 'active')
                                <span class="badge bg-label-success">{{ __('common.active') }}</span>
                                @else
                                <span class="badge bg-label-secondary">{{ __('hrm_salary_structures.superseded') }}</span>
                                @endif
                            </td>
                        </tr>
                        @empty
                        <tr><td colspan="4" class="text-center text-muted">{{ __('hrm_salary_structures.no_history') }}</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
@if ($errors->any())
<script>
    errorMessage("{{ $errors->first() }}");
</script>
@endif
@endsection
