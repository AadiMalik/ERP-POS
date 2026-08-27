@php
    $name = $name ?? 'password';
    $id = $id ?? $name;
    $class = $class ?? 'form-control';
    $required = $required ?? true;
    $autocomplete = $autocomplete ?? 'current-password';
    $value = $value ?? null;
    $placeholder = $placeholder ?? '';
    $errorKey = $errorKey ?? $name;
    $hasError = $errors->has($errorKey);
@endphp
<div class="input-group input-group-merge">
    <input type="password"
        id="{{ $id }}"
        name="{{ $name }}"
        class="{{ $class }}{{ $hasError ? ' is-invalid' : '' }}"
        @if ($required) required @endif
        autocomplete="{{ $autocomplete }}"
        @if ($placeholder !== '') placeholder="{{ $placeholder }}" @endif
        @if ($value !== null && $value !== '') value="{{ $value }}" @endif>
    <span class="input-group-text js-password-toggle" role="button" tabindex="0" aria-label="Show password">
        <svg class="js-password-icon-hide" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94"></path>
            <path d="M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19"></path>
            <line x1="1" y1="1" x2="23" y2="23"></line>
        </svg>
        <svg class="js-password-icon-show d-none" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"></path>
            <circle cx="12" cy="12" r="3"></circle>
        </svg>
    </span>
</div>
@error($errorKey)
    <span class="invalid-feedback d-block" role="alert">
        <strong>{{ $message }}</strong>
    </span>
@enderror
