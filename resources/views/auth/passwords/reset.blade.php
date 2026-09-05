@extends('layouts.auth')

@section('title', 'Reset Password')

@section('content')
    <h4 class="mb-2">{{ __('Reset Password') }}</h4>
    <p class="mb-4">Enter the 6-digit code sent to <strong>{{ $email }}</strong>, then choose a new password.</p>
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif
    <form method="POST" class="mb-3" action="{{ route('password.otp.reset') }}">
        @csrf
        <div class="mb-3">
            <label for="code" class="form-label">Verification code</label>
            <input id="code" type="text" inputmode="numeric" pattern="[0-9]{6}" maxlength="6"
                class="form-control @error('code') is-invalid @enderror" name="code"
                value="{{ old('code') }}" required autocomplete="one-time-code" autofocus>
            @error('code')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
        </div>
        <div class="mb-3">
            <label class="form-label" for="password">New password</label>
            @include('partials.password-input', [
                'name' => 'password',
                'id' => 'password',
                'autocomplete' => 'new-password',
            ])
            <small class="form-text text-muted">Must be at least 8 characters.</small>
        </div>
        <div class="mb-3">
            <label class="form-label" for="password_confirmation">Confirm new password</label>
            @include('partials.password-input', [
                'name' => 'password_confirmation',
                'id' => 'password_confirmation',
                'autocomplete' => 'new-password',
                'required' => true,
            ])
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary d-grid w-100">
                Reset password
            </button>
        </div>
    </form>
    <form method="POST" action="{{ route('password.otp.resend') }}" class="text-center mb-2">
        @csrf
        <button type="submit" class="btn btn-link p-0">Resend code</button>
    </form>
    <div class="text-center">
        <a href="{{ route('password.request') }}">
            <small>Use a different email</small>
        </a>
        <span class="mx-1">·</span>
        <a href="{{ route('login') }}">
            <small>Back to login</small>
        </a>
    </div>
@endsection
