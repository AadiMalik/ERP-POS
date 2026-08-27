@extends('layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Change Your Password</h4>

        <div class="alert alert-warning">
            For security, you must set a new password before you can continue using Smart ERP.
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
                            @include('partials.password-input', [
                                'name' => 'current_password',
                                'id' => 'force_current_password',
                                'autocomplete' => 'current-password',
                            ])
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">New Password<span class="text-danger">*</span></label>
                            @include('partials.password-input', [
                                'name' => 'password',
                                'id' => 'force_password',
                                'autocomplete' => 'new-password',
                            ])
                        </div>
                        <div class="col-md-4">
                            <label class="fw-semibold">Confirm New Password<span class="text-danger">*</span></label>
                            @include('partials.password-input', [
                                'name' => 'password_confirmation',
                                'id' => 'force_password_confirmation',
                                'autocomplete' => 'new-password',
                            ])
                        </div>
                    </div>
                    <small class="form-text text-muted d-block mt-2">
                        Password must be at least 8 characters and different from your current password. You will be
                        asked to sign in again after changing it.
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
@endsection

@section('css')
@endsection
