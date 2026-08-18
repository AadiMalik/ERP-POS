@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Department</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($department) ? 'Update' : 'New' }} Department</h5>
        </div>

        <form action="{{ url('admin/department') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="department_id" value="{{ $department->department_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $department->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Code</label>
                        <input type="text" class="form-control" name="code" value="{{ old('code', $department->code ?? '') }}">
                    </div>
                    @if (isset($department))
                    <div class="col-md-6">
                        <label class="fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ ($department->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ ($department->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    @endif
                    <div class="col-md-12">
                        <label class="fw-semibold">Description</label>
                        <textarea class="form-control" name="description" rows="2">{{ old('description', $department->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save Department</button>
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
