@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('css')
@endsection
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('warehouses.singular') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($warehouse) ? __('warehouses.update_heading') : __('warehouses.new_heading') }}</h5>
        </div>

        <form action="{{ url('admin/warehouse') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <input type="hidden" name="warehouse_id" value="{{ isset($warehouse) ? $warehouse->warehouse_id : '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.name') }} <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                            value="{{ $warehouse->name ?? '' }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.code') }}</label>
                        <input type="text" class="form-control" name="code"
                            value="{{ $warehouse->code ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.phone') }}</label>
                        <input type="text" class="form-control" name="phone"
                            value="{{ $warehouse->phone ?? '' }}">
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
                                {{ old('business_id', $warehouse->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                {{ $item->code }} {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.branch') }}</label>
                        <select name="branch_id" id="branch_id" class="form-control">
                            <option value="">{{ __('warehouses.select_branch') }}</option>
                            @foreach ($branches as $item)
                            <option value="{{ $item->business_id }}"
                                {{ old('branch_id', $user->branch_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">{{ __('common.address') }}</label>
                        <textarea class="form-control" name="address" rows="2">{{ $warehouse->address ?? '' }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('warehouses.save_warehouse') }}</button>
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
    });
</script>
@endsection
