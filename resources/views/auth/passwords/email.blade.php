@extends('layouts.auth')

@section('title', 'Forgot Password')

@section('content')
    <h4 class="mb-2">Forgot Password? 🔒</h4>
    <p class="mb-4">Enter your email and we'll send a 6-digit verification code to reset your password.</p>
    @if (session('status'))
        <div class="alert alert-success" role="alert">
            {{ session('status') }}
        </div>
    @endif
    <form method="POST" class="mb-3" action="{{ route('password.email') }}">
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
            <button type="submit" class="btn btn-primary d-grid w-100">
                Send verification code
            </button>
        </div>
        <div class="text-center">
            <a href="{{ route('login') }}">
                <small>Back to login</small>
            </a>
        </div>
    </form>
@endsection
