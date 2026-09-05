@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_assets.singular') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($asset) ? __('hrm_assets.update_heading') : __('hrm_assets.new_heading') }}</h5>
        </div>

        <form action="{{ url('admin/asset') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="asset_id" value="{{ $asset->asset_id ?? '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" value="{{ old('name', $asset->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('hrm_assets.asset_tag') }}</label>
                        <input type="text" class="form-control" name="asset_tag" value="{{ old('asset_tag', $asset->asset_tag ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.category') }}</label>
                        <input type="text" class="form-control" name="category" value="{{ old('category', $asset->category ?? '') }}" placeholder="{{ __('hrm_assets.category_placeholder') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('hrm_assets.condition') }}</label>
                        <select name="condition" class="form-select">
                            @foreach ([
                                'new' => __('hrm_assets.condition_new'),
                                'good' => __('hrm_assets.condition_good'),
                                'fair' => __('hrm_assets.condition_fair'),
                                'damaged' => __('hrm_assets.condition_damaged'),
                            ] as $key => $label)
                            <option value="{{ $key }}" {{ old('condition', $asset->condition ?? 'new') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.purchase_date') }}</label>
                        <input type="date" class="form-control" name="purchase_date" value="{{ old('purchase_date', $asset->purchase_date ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('hrm_assets.purchase_value') }}</label>
                        <input type="number" step="0.01" min="0" class="form-control" name="purchase_value" value="{{ old('purchase_value', $asset->purchase_value ?? '') }}">
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('hrm_assets.save_asset') }}</button>
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
