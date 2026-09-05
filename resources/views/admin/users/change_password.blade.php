@php

    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('Admin User') }}</h4>

        <div class="card">
            <div class="card-header bg-white border-bottom">
                <h5 class="mb-0">Change Password <small>({{$user->email??''}})</small></h5>
            </div>

            <form action="{{ url('admin/users/change-password') }}" method="POST" enctype="multipart/form-data">
                @csrf
                <div class="card-body">

                    <input type="hidden" name="id" value="{{ isset($user) ? $user->id : '' }}">

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="fw-semibold">Password<span class="text-danger">*</span></label>
                            @include('partials.password-input', [
                                'name' => 'password',
                                'id' => 'user_change_password',
                                'autocomplete' => 'new-password',
                            ])
                        </div>
                        <div class="col-md-6">
                            <label class="fw-semibold">Confirm Password<span class="text-danger">*</span></label>
                            @include('partials.password-input', [
                                'name' => 'password_confirmation',
                                'id' => 'user_change_password_confirmation',
                                'autocomplete' => 'new-password',
                            ])
                        </div>
                    </div>
                </div>
                <div class="card-footer border-top">
                    <div class="d-flex justify-content-end gap-2">
                        <button type="button" class="btn btn-outline-secondary"
                            onclick="window.history.back()">{{ __('common.cancel') }}</button>
                        <button class="btn btn-primary px-4">Change</button>
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
@endsection

@section('css')
@endsection
