@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Salary Component</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($salary_component) ? 'Update' : 'New' }} Salary Component</h5>
        </div>

        <form action="{{ url('admin/salary-component') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="salary_component_id" value="{{ $salary_component->salary_component_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $salary_component->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Code</label>
                        <input type="text" class="form-control" name="code" value="{{ old('code', $salary_component->code ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Type <span class="text-danger">*</span></label>
                        <select name="type" class="form-select" required>
                            <option value="earning" {{ old('type', $salary_component->type ?? '') == 'earning' ? 'selected' : '' }}>Earning</option>
                            <option value="deduction" {{ old('type', $salary_component->type ?? '') == 'deduction' ? 'selected' : '' }}>Deduction</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Calculation <span class="text-danger">*</span></label>
                        <select name="calculation_type" class="form-select" required>
                            <option value="fixed" {{ old('calculation_type', $salary_component->calculation_type ?? '') == 'fixed' ? 'selected' : '' }}>Fixed Amount</option>
                            <option value="percentage_of_basic" {{ old('calculation_type', $salary_component->calculation_type ?? '') == 'percentage_of_basic' ? 'selected' : '' }}>Percentage of Basic</option>
                        </select>
                    </div>
                    @if (isset($salary_component))
                    <div class="col-md-6">
                        <label class="fw-semibold">Status</label>
                        <select name="status" class="form-select">
                            <option value="active" {{ ($salary_component->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                            <option value="inactive" {{ ($salary_component->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    @endif
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save</button>
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
