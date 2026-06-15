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
                                <label class="fw-semibold">Password<span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password" required>
                            </div>
                            <div class="col-md-6">
                                <label class="fw-semibold">Confirm Password<span class="text-danger">*</span></label>
                                <input type="password" class="form-control" name="password_confirmation" required>
                            </div>
                        @endif
                        <div class="col-md-6">
                            <label class="fw-semibold">Role<span class="text-danger">*</span></label>
                            <select name="role_id" id="role_id" class="form-control" required style="height: 40px;">
                                <option value="">--Select Role--</option>
                                @foreach ($roles as $item)
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
                                    <option value="{{ $item->business_id }}"
                                        {{ old('branch_id', $user->branch_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
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

            $('#role_id').on('change', function() {

                let role = $(this).find(':selected').data('role');

                $('#business_div').hide();
                $('#branch_div').hide();

                $('#business_id').prop('required', false);
                $('#branch_id').prop('required', false);

                // Super Admin creating Business Admin
                if (role === 'Business Admin' && role === 'Business Admin') {

                    $('#business_div').show();
                    $('#business_id').prop('required', true);
                }

                // Branch level roles
                if (branchRoles.includes(role)) {

                    $('#branch_div').show();
                    $('#branch_id').prop('required', true);
                }
            });

            $('#role_id').trigger('change');
        });
    </script>
@endsection

@section('css')
@endsection
