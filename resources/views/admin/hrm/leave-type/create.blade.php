@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Leave Type</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($leave_type) ? 'Update' : 'New' }} Leave Type</h5>
        </div>

        <form action="{{ url('admin/leave-type') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="leave_type_id" value="{{ $leave_type->leave_type_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $leave_type->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Code</label>
                        <input type="text" class="form-control" name="code" value="{{ old('code', $leave_type->code ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Max Days Per Year <span class="text-danger">*</span></label>
                        <input type="number" min="0" class="form-control" name="max_days_per_year" value="{{ old('max_days_per_year', $leave_type->max_days_per_year ?? 0) }}" required>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="is_paid" id="is_paid" value="1" {{ old('is_paid', $leave_type->is_paid ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label fw-semibold" for="is_paid">Paid Leave</label>
                        </div>
                    </div>
                    @if (isset($leave_type))
                    <div class="col-md-6">
                        <label class="fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ ($leave_type->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ ($leave_type->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save Leave Type</button>
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
