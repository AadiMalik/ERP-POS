@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_leaves.leave_type') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($leave_type) ? __('hrm_leaves.update_leave_type_heading') : __('hrm_leaves.new_leave_type_heading') }}</h5>
        </div>

        <form action="{{ url('admin/leave-type') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="leave_type_id" value="{{ $leave_type->leave_type_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $leave_type->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.code') }}</label>
                        <input type="text" class="form-control" name="code" value="{{ old('code', $leave_type->code ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('hrm_leaves.max_days_per_year') }} <span class="text-danger">*</span></label>
                        <input type="number" min="0" class="form-control" name="max_days_per_year" value="{{ old('max_days_per_year', $leave_type->max_days_per_year ?? 0) }}" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_paid" id="is_paid" value="1" {{ old('is_paid', $leave_type->is_paid ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_paid">{{ __('hrm_leaves.paid_leave') }}</label>
                        </div>
                    </div>
                    @if (isset($leave_type))
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.status') }}</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ ($leave_type->status ?? 'active') == 'active' ? 'selected' : '' }}>{{ __('common.active') }}</option>
                            <option value="inactive" {{ ($leave_type->status ?? '') == 'inactive' ? 'selected' : '' }}>{{ __('common.inactive') }}</option>
                        </select>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('hrm_leaves.save_leave_type') }}</button>
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
