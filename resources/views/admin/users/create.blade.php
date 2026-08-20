@php

    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Admin User</h4>

        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">{{ isset($user) ? 'Update' : 'New' }} Admin User</h5>
            </div>

            <form action="{{ url('admin/users') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">

                    <input type="hidden" name="id" value="{{ isset($user) ? $user->id : '' }}">

                    <div class="row g-3">

                        <div class="col-md-6">
                            <label class="fw-semibold">Full Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name"
                                value="{{ old('name', $user->name ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Email<span class="text-danger">**</span></label>
                            <input type="email" class="form-control" name="email"
                                value="{{ old('email', $user->email ?? '') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Phone</label>
                            <input type="text" class="form-control" name="phone"
                                value="{{ old('phone', $user->phone ?? '') }}">
                        </div>
                        @if (!isset($user))
                            <div class="col-md-6">
                                <label class="fw-semibold" id="password_label">Password<span
                                        class="text-danger" id="password_required_mark">*</span></label>
                                <input type="password" class="form-control" id="password" name="password">
                                <small class="form-text text-muted d-none" id="password_hint">
                                    Optional for customers - they set their own password via the OTP
                                    onboarding flow.
                                </small>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold" id="password_confirmation_label">Confirm
                                    Password<span class="text-danger" id="password_confirmation_required_mark">*</span></label>
                                <input type="password" class="form-control" id="password_confirmation"
                                    name="password_confirmation">
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="fw-semibold">Role<span class="text-danger">*</span></label>
                            <select name="role_id" id="role_id" class="form-control" required style="height: 40px;">
                                <option value="">--Select Role--</option>
                                @foreach ($roles as $item)
                                    @continue($item->name === RoleNames::USER)
                                    <option value="{{ $item->id }}" data-role="{{ $item->name }}"
                                        {{ old('role_id', isset($user)?$user->roles->first()->id :'') == $item->id ? 'selected' : '' }}>
                                        {{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6" id="business_div" style="display:none;">
                            <label class="fw-semibold">Business <span class="text-danger">*</span></label>
                            <select name="business_id" id="business_id" class="form-control">
                                <option value="">--Select Business--</option>
                                @foreach ($business as $item)
                                    <option value="{{ $item->business_id }}"
                                        {{ old('business_id', $user->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                        {{ isset($item->code) ? $item->code : '' }} {{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6" id="branch_div" style="display:none;">
                            <label class="fw-semibold">
                                Branch <span class="text-danger">*</span>
                            </label>
                            <select name="branch_id" id="branch_id" class="form-control">
                                <option value="">--Select Branch--</option>

                                @foreach ($branches as $item)
                                    <option value="{{ $item->branch_id }}"
                                        {{ old('branch_id', $user->branch_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>

                <div class="card-body border-top" id="customer_profile_div" style="display:none;">
                    <h6 class="mb-3">Customer Profile</h6>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="fw-semibold">
                                Code <small>(if blank, will be auto generated)</small>
                            </label>
                            <input type="text" class="form-control" name="code"
                                value="{{ old('code', $customer_profile->code ?? '') }}"
                                {{ isset($customer_profile) ? 'readonly' : '' }}>
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
                            <label class="fw-semibold">Credit Limit</label>
                            <input type="number" step="0.01" class="form-control" name="credit_limit"
                                value="{{ old('credit_limit', $customer_profile->credit_limit ?? 0) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Credit Days</label>
                            <input type="number" class="form-control" name="credit_days"
                                value="{{ old('credit_days', $customer_profile->credit_days ?? 0) }}">
                        </div>
                        <div class="col-md-12">
                            <label class="fw-semibold">Address</label>
                            <textarea class="form-control" rows="2" name="address">{{ old('address', $customer_profile->address ?? '') }}</textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">City</label>
                            <input type="text" class="form-control" name="city"
                                value="{{ old('city', $customer_profile->city ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">State</label>
                            <input type="text" class="form-control" name="state"
                                value="{{ old('state', $customer_profile->state ?? '') }}">
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">Country</label>
                            <input type="text" class="form-control" name="country"
                                value="{{ old('country', $customer_profile->country ?? '') }}">
                        </div>
                    </div>
                </div>
                <div class="card-footer border-top">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="window.history.back()">Cancel</button>
                        <button class="btn btn-primary px-4">Save</button>
                    </div>
                </div>
                <!-- Form Actions -->

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
            errorMessage(
                "{{ session('error') }}"
            );
        </script>
    @endif
    <script>
        $(document).ready(function() {
            $('#role_id').select2();
            $('#business_id').select2();
            $('#branch_id').select2();

            const currentUserRole = "{{ getRoleName() }}";
            const businessRoles = [
                'General Manager',
                'Operation Manager',
                'Inventory Manager',
                'Finance Manager',
                'Sale Manager',
                'Purchase Manager',
                'Marketing Manager',
                'Accountant',
                'HR Manager',
                'Reporting Analyst'
            ];

            const branchRoles = [
                'Branch Admin',
                'Staff',
                'POS Manager',
                'Order Taker'
            ];

            const customerRole = 'User';

            $('#role_id').on('change', function() {

                let role = $(this).find(':selected').data('role');
                let isCustomer = role === customerRole;

                $('#business_div').hide();
                $('#branch_div').hide();
                $('#customer_profile_div').toggle(isCustomer);

                $('#business_id').prop('required', false);
                $('#branch_id').prop('required', false);

                // Super Admin creating Business Admin
                if (role === 'Business Admin') {

                    $('#business_div').show();
                    $('#business_id').prop('required', true);
                }

                // Customers are always tied to a business too - their
                // CustomerProfile (credit terms, address, ...) is saved
                // against it.
                if (isCustomer) {

                    $('#business_div').show();
                    $('#business_id').prop('required', true);
                }

                // Branch level roles
                if (branchRoles.includes(role)) {

                    $('#branch_div').show();
                    $('#branch_id').prop('required', true);
                }

                // Customers set their own password later via OTP onboarding -
                // staff roles still require one up front.
                if ($('#password').length) {
                    $('#password, #password_confirmation').prop('required', !isCustomer);
                    $('#password_required_mark, #password_confirmation_required_mark').toggleClass('d-none',
                        isCustomer);
                    $('#password_hint').toggleClass('d-none', !isCustomer);
                }
            });

            $('#role_id').trigger('change');
        });
    </script>
@endsection

@section('css')
@endsection
