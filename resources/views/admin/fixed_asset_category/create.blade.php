@php
    use App\Enums\RoleNames;
    use App\Enums\Status;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Fixed Asset Category</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($category) ? 'Update' : 'New' }} Fixed Asset Category</h5>
        </div>

        <form action="{{ url('admin/fixed-asset-category/store') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="fixed_asset_category_id"
                    value="{{ isset($category) ? $category->fixed_asset_category_id : '' }}">

                <div class="row g-4">
                    @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-6">
                        <label class="fw-semibold">
                            Business <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="business_id" id="business_id" required>
                            <option value="">-- Select Business --</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}"
                                {{ old('business_id', $category->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                {{ $item->code ?? '' }} {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-md-6">
                        <label class="fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                            value="{{ old('name', $category->name ?? '') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold">Code</label>
                        <input type="text" class="form-control" name="code"
                            value="{{ old('code', $category->code ?? '') }}">
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold">Useful Life (Years) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" name="default_useful_life_years" min="1" max="100"
                            value="{{ old('default_useful_life_years', $category->default_useful_life_years ?? 5) }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold">Residual %</label>
                        <input type="number" class="form-control" name="default_residual_percent" min="0" max="100" step="0.01"
                            value="{{ old('default_residual_percent', $category->default_residual_percent ?? 0) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold">Status</label>
                        <select class="form-select" name="status">
                            <option value="{{ Status::ACTIVE }}"
                                {{ old('status', $category->status ?? Status::ACTIVE) == Status::ACTIVE ? 'selected' : '' }}>
                                Active
                            </option>
                            <option value="{{ Status::INACTIVE }}"
                                {{ old('status', $category->status ?? '') == Status::INACTIVE ? 'selected' : '' }}>
                                Inactive
                            </option>
                        </select>
                    </div>

                    <div class="col-md-12">
                        <label class="fw-semibold">Description</label>
                        <textarea class="form-control" name="description" rows="3">{{ old('description', $category->description ?? '') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save Category</button>
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
@if(session('success'))
<script>
    successMessage("{{ session('success') }}");
</script>
@endif
<script>
    $(document).ready(function() {
        $('#business_id').select2();
    });
</script>
@endsection
