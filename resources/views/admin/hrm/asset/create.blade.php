@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Asset</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($asset) ? 'Update' : 'New' }} Asset</h5>
        </div>

        <form action="{{ url('admin/asset') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="asset_id" value="{{ $asset->asset_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $asset->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Asset Tag</label>
                        <input type="text" class="form-control" name="asset_tag" value="{{ old('asset_tag', $asset->asset_tag ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Category</label>
                        <input type="text" class="form-control" name="category" value="{{ old('category', $asset->category ?? '') }}" placeholder="e.g. Laptop, Vehicle, Furniture">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Condition</label>
                        <select name="condition" class="form-select">
                            @foreach (['new' => 'New', 'good' => 'Good', 'fair' => 'Fair', 'damaged' => 'Damaged'] as $key => $label)
                            <option value="{{ $key }}" {{ old('condition', $asset->condition ?? 'new') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Purchase Date</label>
                        <input type="date" class="form-control" name="purchase_date" value="{{ old('purchase_date', $asset->purchase_date ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Purchase Value</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="purchase_value" value="{{ old('purchase_value', $asset->purchase_value ?? '') }}">
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save Asset</button>
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
@endsection
