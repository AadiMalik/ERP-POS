@extends('layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">My Profile</h4>

        <div class="card mb-4">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Profile Information</h5>
            </div>

            <form action="{{ url('admin/profile') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="fw-semibold">Full Name<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="name"
                                value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Email</label>
                            <input type="email" class="form-control" value="{{ $user->email }}" disabled readonly>
                            <small class="form-text text-muted">Email cannot be changed as it is used for login.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Phone</label>
                            <input type="text" class="form-control" name="phone"
                                value="{{ old('phone', $user->phone) }}">
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Role</label>
                            <input type="text" class="form-control" value="{{ getRoleName() }}" disabled readonly>
                        </div>
                        @if ($user->business)
                            <div class="col-md-6">
                                <label class="fw-semibold">Business</label>
                                <input type="text" class="form-control" value="{{ $user->business->name }}" disabled readonly>
                            </div>
                        @endif
                        @if ($user->branch)
                            <div class="col-md-6">
                                <label class="fw-semibold">Branch</label>
                                <input type="text" class="form-control" value="{{ $user->branch->name }}" disabled readonly>
                            </div>
                        @endif
                    </div>
                </div>
                <div class="card-footer border-top">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-primary px-4">Save Changes</button>
                    </div>
                </div>
            </form>
        </div>

        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Change Password</h5>
            </div>

            <form action="{{ url('admin/profile/password') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="fw-semibold">Current Password<span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="current_password" required>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">New Password<span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password" required>
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">Confirm New Password<span class="text-danger">*</span></label>
                            <input type="password" class="form-control" name="password_confirmation" required>
                        </div>
                    </div>
                    <small class="form-text text-muted d-block mt-2">
                        Password must be at least 8 characters and different from your current password. You will be
                        logged out and asked to sign in again after changing it.
                    </small>
                </div>
                <div class="card-footer border-top">
                    <div class="d-flex justify-content-end gap-2">
                        <button class="btn btn-primary px-4">Change Password</button>
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
    @if (session('success'))
        <script>
            successMessage("{{ session('success') }}");
        </script>
    @endif
@endsection

@section('css')
@endsection
