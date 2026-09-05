@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('customers.singular') }}</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($user) ? __('customers.update_heading') : __('customers.new_heading') }}</h5>
        </div>

        <form action="{{ url('admin/customer') }}" method="POST">
            @csrf

            <input type="hidden" name="id" value="{{ isset($user) ? $user->id : '' }}">

            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-12">

                        <!-- Basic Information -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('customers.basic_information') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    @if (!empty($business))
                                        <div class="col-md-6">
                                            <label class="fw-semibold">{{ __('common.business') }} <span class="text-danger">*</span></label>
                                            <select class="form-select" name="business_id" id="business_id" required
                                                {{ isset($user) ? 'disabled' : '' }}>
                                                <option value="">{{ __('common.select_business') }}</option>
                                                @foreach ($business as $item)
                                                    <option value="{{ $item->business_id }}"
                                                        {{ old('business_id', $customer_profile->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                                        {{ $item->code }} - {{ $item->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                            @if (isset($user))
                                                <input type="hidden" name="business_id" value="{{ $customer_profile->business_id ?? '' }}">
                                            @endif
                                        </div>
                                    @endif

                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('customers.customer_code') }} <small>{{ __('customers.code_auto_hint') }}</small></label>
                                        <input type="text" class="form-control" name="code"
                                            value="{{ old('code', $customer_profile->code ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('common.name') }} <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                            value="{{ old('name', $user->name ?? '') }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('customers.company_name') }}</label>
                                        <input type="text" class="form-control" name="company_name"
                                            value="{{ old('company_name', $customer_profile->company_name ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('customers.contact_person') }}</label>
                                        <input type="text" class="form-control" name="contact_person"
                                            value="{{ old('contact_person', $customer_profile->contact_person ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('common.email') }} <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="email"
                                            value="{{ old('email', $user->email ?? '') }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('common.phone') }}</label>
                                        <input type="text" class="form-control" name="phone"
                                            value="{{ old('phone', $user->phone ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('common.status') }}</label>
                                        <select class="form-select" name="status">
                                            <option value="active" {{ old('status', $customer_profile->status ?? 'active') == 'active' ? 'selected' : '' }}>{{ __('common.active') }}</option>
                                            <option value="inactive" {{ old('status', $customer_profile->status ?? '') == 'inactive' ? 'selected' : '' }}>{{ __('common.inactive') }}</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Billing Address -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('customers.billing_address') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="fw-semibold">{{ __('common.address') }}</label>
                                        <textarea class="form-control" rows="2" name="address" id="address">{{ old('address', $customer_profile->address ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('common.city') }}</label>
                                        <input type="text" class="form-control" name="city" id="city"
                                            value="{{ old('city', $customer_profile->city ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('customers.state') }}</label>
                                        <input type="text" class="form-control" name="state" id="state"
                                            value="{{ old('state', $customer_profile->state ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('common.country') }}</label>
                                        <input type="text" class="form-control" name="country" id="country"
                                            value="{{ old('country', $customer_profile->country ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="card mb-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">{{ __('customers.shipping_address') }}</h6>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="same_as_billing">
                                    <label class="form-check-label" for="same_as_billing">{{ __('customers.same_as_billing') }}</label>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="fw-semibold">{{ __('common.address') }}</label>
                                        <textarea class="form-control" rows="2" name="shipping_address" id="shipping_address">{{ old('shipping_address', $customer_profile->shipping_address ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('common.city') }}</label>
                                        <input type="text" class="form-control" name="shipping_city" id="shipping_city"
                                            value="{{ old('shipping_city', $customer_profile->shipping_city ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('customers.state') }}</label>
                                        <input type="text" class="form-control" name="shipping_state" id="shipping_state"
                                            value="{{ old('shipping_state', $customer_profile->shipping_state ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">{{ __('common.country') }}</label>
                                        <input type="text" class="form-control" name="shipping_country" id="shipping_country"
                                            value="{{ old('shipping_country', $customer_profile->shipping_country ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Credit & Accounting -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('customers.credit_accounting') }}</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('customers.credit_limit') }}</label>
                                        <input type="number" step="0.01" class="form-control" name="credit_limit"
                                            value="{{ old('credit_limit', $customer_profile->credit_limit ?? 0) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('customers.credit_days') }}</label>
                                        <input type="number" class="form-control" name="credit_days"
                                            value="{{ old('credit_days', $customer_profile->credit_days ?? 0) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">{{ __('customers.payment_terms') }}</label>
                                        <input type="text" class="form-control" name="payment_terms"
                                            placeholder="{{ __('customers.payment_terms_placeholder') }}"
                                            value="{{ old('payment_terms', $customer_profile->payment_terms ?? '') }}">
                                    </div>

                                    @if (!isset($user))
                                        <div class="col-md-3">
                                            <label class="fw-semibold">{{ __('customers.opening_balance') }}</label>
                                            <input type="number" step="0.01" class="form-control" name="opening_balance"
                                                value="{{ old('opening_balance', 0) }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="fw-semibold">{{ __('customers.balance_type') }}</label>
                                            <select class="form-select" name="opening_balance_type">
                                                <option value="Dr">{{ __('customers.debit_customer_owes') }}</option>
                                                <option value="Cr">{{ __('customers.credit_customer_advance') }}</option>
                                            </select>
                                        </div>
                                    @else
                                        <div class="col-md-6">
                                            <label class="fw-semibold">{{ __('customers.opening_balance') }}</label>
                                            <input type="text" class="form-control" disabled
                                                value="{{ currency($customer_profile->opening_balance ?? 0) }} {{ $customer_profile->opening_balance_type ?? '' }}">
                                            <small class="text-muted">{{ __('customers.opening_balance_locked') }}</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">{{ __('common.notes') }}</h6>
                            </div>
                            <div class="card-body">
                                <textarea class="form-control" rows="3" name="notes">{{ old('notes', $customer_profile->notes ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('customers.save_customer') }}</button>
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
<script>
    $(document).ready(function() {
        $('#business_id').select2();
    });

    $('#same_as_billing').on('change', function() {
        if (this.checked) {
            $('#shipping_address').val($('#address').val());
            $('#shipping_city').val($('#city').val());
            $('#shipping_state').val($('#state').val());
            $('#shipping_country').val($('#country').val());
        }
    });
</script>
@endsection
