@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('css')
@endsection
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('delivery-zones.singular') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($delivery_zone) ? __('delivery-zones.update_heading') : __('delivery-zones.new_heading') }}</h5>
        </div>

        <form action="{{ url('admin/delivery-zone') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="delivery_zone_id" value="{{ isset($delivery_zone) ? $delivery_zone->delivery_zone_id : '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.name') }}</label>
                        <input type="text" class="form-control" name="name"
                            value="{{ old('name', $delivery_zone->name ?? '') }}">
                    </div>
                    @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-6">
                        <label class="fw-semibold">
                            {{ __('common.business') }} <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="business_id" id="business_id" required>
                            <option value="">{{ __('common.select_business') }}</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}"
                                {{ old('business_id', $delivery_zone->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                {{ $item->code }} {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.branch') }} <span class="text-danger">*</span></label>
                        <select name="branch_id" id="branch_id" class="form-select" required>
                            <option value="">{{ __('delivery-zones.select_branch') }}</option>
                            @foreach ($branches as $item)
                            <option value="{{ $item->branch_id }}"
                                {{ old('branch_id', $delivery_zone->branch_id ?? '') == $item->branch_id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('delivery-zones.min_km') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.001" min="0" class="form-control" name="min_km"
                            value="{{ old('min_km', $delivery_zone->min_km ?? 0) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('delivery-zones.max_km') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.001" min="0" class="form-control" name="max_km"
                            value="{{ old('max_km', $delivery_zone->max_km ?? '') }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('delivery-zones.fee') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" name="fee"
                            value="{{ old('fee', $delivery_zone->fee ?? 0) }}" required>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('delivery-zones.save_button') }}</button>
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
    errorMessage(
        "{{ session('error') }}"
    );
</script>
@endif
<script>
    $(document).ready(function() {
        $('#business_id').select2();
        $('#branch_id').select2();
    });
</script>
@endsection
