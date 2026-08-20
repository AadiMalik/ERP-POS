@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Customer</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($user) ? 'Update' : 'New' }} Customer</h5>
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
                                <h6 class="mb-0">Basic Information</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-4">
                                    @if (!empty($business))
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Business <span class="text-danger">*</span></label>
                                            <select class="form-select" name="business_id" id="business_id" required
                                                {{ isset($user) ? 'disabled' : '' }}>
                                                <option value="">-- Select Business --</option>
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
                                        <label class="fw-semibold">Customer Code <small>(if blank, will be auto generated)</small></label>
                                        <input type="text" class="form-control" name="code"
                                            value="{{ old('code', $customer_profile->code ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" name="name"
                                            value="{{ old('name', $user->name ?? '') }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Company Name</label>
                                        <input type="text" class="form-control" name="company_name"
                                            value="{{ old('company_name', $customer_profile->company_name ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Contact Person</label>
                                        <input type="text" class="form-control" name="contact_person"
                                            value="{{ old('contact_person', $customer_profile->contact_person ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Email <span class="text-danger">*</span></label>
                                        <input type="email" class="form-control" name="email"
                                            value="{{ old('email', $user->email ?? '') }}" required>
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Phone</label>
                                        <input type="text" class="form-control" name="phone"
                                            value="{{ old('phone', $user->phone ?? '') }}">
                                    </div>

                                    <div class="col-md-6">
                                        <label class="fw-semibold">Status</label>
                                        <select class="form-select" name="status">
                                            <option value="active" {{ old('status', $customer_profile->status ?? 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                            <option value="inactive" {{ old('status', $customer_profile->status ?? '') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Billing Address -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Billing Address</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="fw-semibold">Address</label>
                                        <textarea class="form-control" rows="2" name="address" id="address">{{ old('address', $customer_profile->address ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">City</label>
                                        <input type="text" class="form-control" name="city" id="city"
                                            value="{{ old('city', $customer_profile->city ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">State</label>
                                        <input type="text" class="form-control" name="state" id="state"
                                            value="{{ old('state', $customer_profile->state ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">Country</label>
                                        <input type="text" class="form-control" name="country" id="country"
                                            value="{{ old('country', $customer_profile->country ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Shipping Address -->
                        <div class="card mb-4">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <h6 class="mb-0">Shipping Address</h6>
                                <div class="form-check mb-0">
                                    <input class="form-check-input" type="checkbox" id="same_as_billing">
                                    <label class="form-check-label" for="same_as_billing">Same as billing</label>
                                </div>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-12">
                                        <label class="fw-semibold">Address</label>
                                        <textarea class="form-control" rows="2" name="shipping_address" id="shipping_address">{{ old('shipping_address', $customer_profile->shipping_address ?? '') }}</textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">City</label>
                                        <input type="text" class="form-control" name="shipping_city" id="shipping_city"
                                            value="{{ old('shipping_city', $customer_profile->shipping_city ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">State</label>
                                        <input type="text" class="form-control" name="shipping_state" id="shipping_state"
                                            value="{{ old('shipping_state', $customer_profile->shipping_state ?? '') }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="fw-semibold">Country</label>
                                        <input type="text" class="form-control" name="shipping_country" id="shipping_country"
                                            value="{{ old('shipping_country', $customer_profile->shipping_country ?? '') }}">
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Credit & Accounting -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Credit &amp; Accounting</h6>
                            </div>
                            <div class="card-body">
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Credit Limit</label>
                                        <input type="number" step="0.01" class="form-control" name="credit_limit"
                                            value="{{ old('credit_limit', $customer_profile->credit_limit ?? 0) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Credit Days</label>
                                        <input type="number" class="form-control" name="credit_days"
                                            value="{{ old('credit_days', $customer_profile->credit_days ?? 0) }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="fw-semibold">Payment Terms</label>
                                        <input type="text" class="form-control" name="payment_terms"
                                            placeholder="e.g. Net 30, Due on Receipt"
                                            value="{{ old('payment_terms', $customer_profile->payment_terms ?? '') }}">
                                    </div>

                                    @if (!isset($user))
                                        <div class="col-md-3">
                                            <label class="fw-semibold">Opening Balance</label>
                                            <input type="number" step="0.01" class="form-control" name="opening_balance"
                                                value="{{ old('opening_balance', 0) }}">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="fw-semibold">Balance Type</label>
                                            <select class="form-select" name="opening_balance_type">
                                                <option value="Dr">Debit (Customer Owes)</option>
                                                <option value="Cr">Credit (Customer Advance)</option>
                                            </select>
                                        </div>
                                    @else
                                        <div class="col-md-6">
                                            <label class="fw-semibold">Opening Balance</label>
                                            <input type="text" class="form-control" disabled
                                                value="{{ currency($customer_profile->opening_balance ?? 0) }} {{ $customer_profile->opening_balance_type ?? '' }}">
                                            <small class="text-muted">Opening balance can only be set at creation.</small>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Notes -->
                        <div class="card mb-4">
                            <div class="card-header bg-light">
                                <h6 class="mb-0">Notes</h6>
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
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save Customer</button>
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
