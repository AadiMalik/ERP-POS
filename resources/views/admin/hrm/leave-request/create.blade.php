@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_leaves.new_heading') }}</h4>

    <div class="card">
        <form action="{{ url('admin/leave-request') }}" method="POST" enctype="multipart/form-data">
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
                        <label class="fw-semibold">{{ __('hrm_leaves.leave_type') }} <span class="text-danger">*</span></label>
                        <select name="leave_type_id" class="form-select select2" required>
                            <option value="">{{ __('hrm_leaves.select_leave_type') }}</option>
                            @foreach ($leave_types as $item)
                            <option value="{{ $item->leave_type_id }}" {{ old('leave_type_id') == $item->leave_type_id ? 'selected' : '' }}>{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.start_date') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="start_date" value="{{ old('start_date') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.end_date') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="end_date" value="{{ old('end_date') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.attachment') }}</label>
                        <input type="file" class="form-control" name="attachment">
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">{{ __('common.reason') }}</label>
                        <textarea class="form-control" name="reason" rows="2">{{ old('reason') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('hrm_leaves.submit_request') }}</button>
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
