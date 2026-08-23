@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Designation</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($designation) ? 'Update' : 'New' }} Designation</h5>
        </div>

        <form action="{{ url('admin/designation') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="designation_id" value="{{ $designation->designation_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $designation->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Code</label>
                        <input type="text" class="form-control" name="code" value="{{ old('code', $designation->code ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex align-items-center justify-content-between">
                            <label class="fw-semibold mb-0">Department</label>
                            @include('admin.partials.quick-add-btn', ['permission' => 'department.create', 'modal' => 'quickAddDepartmentModal', 'label' => 'Department'])
                        </div>
                        <select name="department_id" id="department_id" class="form-select">
                            <option value="">-- Select Department --</option>
                            @foreach ($departments as $item)
                            <option value="{{ $item->department_id }}" {{ old('department_id', $designation->department_id ?? '') == $item->department_id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @if (isset($designation))
                    <div class="col-md-6">
                        <label class="fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ ($designation->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ ($designation->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    @endif
                    <div class="col-md-12">
                        <label class="fw-semibold">Description</label>
                        <textarea class="form-control" name="description" rows="2">{{ old('description', $designation->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save Designation</button>
                </div>
            </div>
        </form>
    </div>

    @include('admin.hrm.department.model.quick-create')
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
        $('#department_id').select2();
    });

    initQuickAdd({
        modalId: '#quickAddDepartmentModal',
        formId: '#quickAddDepartmentForm',
        url: url_local + '/admin/department',
        valueField: 'department_id',
        labelField: 'name',
        targetSelectIds: ['department_id'],
    });
</script>
@endsection
