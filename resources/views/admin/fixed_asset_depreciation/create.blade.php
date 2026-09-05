@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Post Depreciation</h4>
    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ __('fixed_asset_depreciations.create_heading') }} Entry</h5>
        </div>
        <form action="{{ url('admin/fixed-asset-depreciation/store') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row g-4">
                    @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-4">
                        <label class="fw-semibold">Business <span class="text-danger">*</span></label>
                        <select class="form-select" name="business_id" id="business_id" required>
                            <option value="">-- Select Business --</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}" {{ old('business_id') == $item->business_id ? 'selected' : '' }}>
                                {{ $item->code ?? '' }} {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <label class="fw-semibold">Fixed Asset <span class="text-danger">*</span></label>
                        <select class="form-select select2" name="fixed_asset_id" required>
                            <option value="">-- Select Asset --</option>
                            @foreach ($assets as $item)
                            <option value="{{ $item->fixed_asset_id }}" {{ old('fixed_asset_id') == $item->fixed_asset_id ? 'selected' : '' }}>
                                {{ $item->asset_code ? $item->asset_code . ' - ' : '' }}{{ $item->name }}
                                (BV: {{ currency($item->current_book_value) }}, Next: {{ $item->next_depreciation_date ? localDate($item->next_depreciation_date) : 'N/A' }})
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">Only active assets for your business are listed.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">Depreciation Date</label>
                        <input type="date" class="form-control" name="depreciation_date" value="{{ old('depreciation_date') }}">
                        <small class="text-muted">Leave blank to use the asset's next depreciation date.</small>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <a href="{{ url('admin/fixed-asset-depreciation') }}" class="btn btn-outline-secondary">Cancel</a>
                    <button class="btn btn-primary px-4">Post Depreciation</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection
@section('js')
@if ($errors->any())
<script>errorMessage("{{ $errors->first() }}");</script>
@endif
@if(session('error'))
<script>errorMessage("{{ session('error') }}");</script>
@endif
<script>
$(document).ready(function() {
    $('.select2').select2();
});
</script>
@endsection
