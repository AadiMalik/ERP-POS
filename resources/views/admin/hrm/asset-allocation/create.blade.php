@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_assets.issue_asset') }}</h4>

    <div class="card">
        <form action="{{ url('admin/asset-allocation') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('hrm_assets.singular') }} <span class="text-danger">*</span></label>
                        <select name="asset_id" class="form-select select2" required>
                            <option value="">{{ __('hrm_assets.select_available_asset') }}</option>
                            @foreach ($assets as $item)
                            <option value="{{ $item->asset_id }}" {{ old('asset_id') == $item->asset_id ? 'selected' : '' }}>
                                {{ $item->name }} @if($item->asset_tag) ({{ $item->asset_tag }}) @endif
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.employee') }} <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select select2" required>
                            <option value="">{{ __('common.select_employee') }}</option>
                            @foreach ($employees as $item)
                            <option value="{{ $item->employee_id }}" {{ old('employee_id') == $item->employee_id ? 'selected' : '' }}>
                                {{ $item->user->name ?? '-' }} ({{ $item->employee_code }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_assets.issue_date') }} <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="issue_date" value="{{ old('issue_date', date('Y-m-d')) }}" required>
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_assets.expected_return_date') }}</label>
                        <input type="date" class="form-control" name="expected_return_date" value="{{ old('expected_return_date') }}">
                    </div>
                    <div class="col-md-4">
                        <label class="fw-semibold">{{ __('hrm_assets.condition_on_issue') }}</label>
                        <select name="condition_on_issue" class="form-select">
                            @foreach ([
                                'new' => __('hrm_assets.condition_new'),
                                'good' => __('hrm_assets.condition_good'),
                                'fair' => __('hrm_assets.condition_fair'),
                                'damaged' => __('hrm_assets.condition_damaged'),
                            ] as $key => $label)
                            <option value="{{ $key }}" {{ old('condition_on_issue') == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">{{ __('common.remarks') }}</label>
                        <textarea class="form-control" name="remarks" rows="2">{{ old('remarks') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('hrm_assets.issue_asset') }}</button>
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
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endsection
