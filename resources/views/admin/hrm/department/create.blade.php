@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_departments.singular') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($department) ? __('hrm_departments.update_heading') : __('hrm_departments.new_heading') }}</h5>
        </div>

        <form action="{{ url('admin/department') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="department_id" value="{{ $department->department_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $department->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.code') }}</label>
                        <input type="text" class="form-control" name="code" value="{{ old('code', $department->code ?? '') }}">
                    </div>
                    @if (isset($department))
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.status') }}</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ ($department->status ?? 'active') == 'active' ? 'selected' : '' }}>{{ __('common.active') }}</option>
                            <option value="inactive" {{ ($department->status ?? '') == 'inactive' ? 'selected' : '' }}>{{ __('common.inactive') }}</option>
                        </select>
                    </div>
                    @endif
                    <div class="col-md-12">
                        <label class="fw-semibold">{{ __('common.description') }}</label>
                        <textarea class="form-control" name="description" rows="2">{{ old('description', $department->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('hrm_departments.save_department') }}</button>
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
