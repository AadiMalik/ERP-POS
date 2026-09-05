@extends('layouts.auth')

@section('title', 'Login')

@section('content')
    <h4 class="mb-2">{{ __('Welcome to') }} @include('partials.brand-wordmark')! 👋</h4>
    <p class="mb-4">{{ __('Please sign-in to your account and start the adventure') }}</p>
    @if (session('success'))
        <div class="alert alert-success" role="alert">
            {{ session('success') }}
        </div>
    @endif
    <form method="POST" class="mb-3" action="{{ route('login') }}">
        @csrf
        <div class="mb-3">
            <label for="email" class="form-label">{{ __('Email Address') }}</label>
            <input id="email" type="email"
                class="form-control @error('email') is-invalid @enderror" name="email"
                value="{{ old('email') }}" required autocomplete="email" autofocus>
            @error('email')
            <span class="invalid-feedback" role="alert">
                <strong>{{ $message }}</strong>
            </span>
            @enderror
        </div>
        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <label class="form-label" for="password">{{ __('Password') }}</label>
                <a href="{{ route('password.request') }}">
                    <small>{{ __('Forgot Password?') }}</small>
                </a>
            </div>
            @include('partials.password-input', [
                'name' => 'password',
                'id' => 'password',
                'autocomplete' => 'current-password',
                'placeholder' => '············',
            ])
        </div>
        <div class="mb-3">
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="remember"
                    id="remember" {{ old('remember') ? 'checked' : '' }}>
                <label class="form-check-label" for="remember">
                    {{ __('Remember Me') }}
                </label>
            </div>
        </div>
        <div class="mb-3">
            <button type="submit" class="btn btn-primary d-grid w-100">
                {{ __('Login') }}
            </button>
        </div>
    </form>
@endsection
