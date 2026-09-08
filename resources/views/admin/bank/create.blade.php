@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('banks.singular') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($bank) ? __('banks.update_heading') : __('banks.new_heading') }}</h5>
        </div>

        <form action="{{ url('admin/bank') }}" method="POST">
            @csrf
            <div class="card-body">
                <input type="hidden" name="bank_id" value="{{ isset($bank) ? $bank->bank_id : '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                            value="{{ old('name', $bank->name ?? '') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.code') }}</label>
                        <input type="text" class="form-control" name="code"
                            value="{{ old('code', $bank->code ?? '') }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.account_number') }}</label>
                        <input type="text" class="form-control" name="account_number"
                            value="{{ old('account_number', $bank->account_number ?? '') }}">
                    </div>
                    @if (!empty($business) && RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-6">
                        <label class="fw-semibold">
                            {{ __('common.business') }} <span class="text-danger">*</span>
                        </label>
                        <select class="form-select select2" name="business_id" id="business_id" required>
                            <option value="">{{ __('common.select_business') }}</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}"
                                {{ old('business_id', $bank->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                {{ $item->code }} {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.branch') }}</label>
                        <select name="branch_id" id="branch_id" class="form-select select2">
                            <option value="">{{ __('banks.select_branch') }}</option>
                            @foreach ($branches as $item)
                            <option value="{{ $item->branch_id }}"
                                {{ old('branch_id', $bank->branch_id ?? '') == $item->branch_id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                        <small class="text-muted">{{ __('banks.select_branch') }}</small>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.account') }} <span class="text-danger">*</span></label>
                        <select name="account_id" id="account_id" class="form-select select2" required>
                            <option value="">{{ __('banks.select_account') }}</option>
                            @foreach ($accounts as $item)
                            <option value="{{ $item->account_id }}"
                                {{ old('account_id', $bank->account_id ?? '') == $item->account_id ? 'selected' : '' }}>
                                {{ $item->code }} - {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('banks.save_bank') }}</button>
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
@if (session('error'))
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
