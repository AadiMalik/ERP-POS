@php
    $appName = $app_name ?? 'our store';
    $firstName = $first_name ?? null;
    $userName = $user_name ?? $firstName;
    $greeting = $firstName ? 'Hi ' . $firstName : 'Hi';
    $expiry = (int) ($expiry_minutes ?? 10);
    $emailAddress = is_string($account_email ?? null) ? $account_email : '';
    $purpose = $purpose ?? 'password_reset';

    $intro = [
        'password_reset' => 'We received a request to reset the password for your ' . $appName . ' account.',
        'login' => 'Use this code to sign in to your ' . $appName . ' account.',
        'onboarding' => 'Welcome to ' . $appName . '. Use this code to finish setting up your account.',
    ][$purpose] ?? 'Use the code below to continue.';
@endphp
{{ $greeting }},

{{ $intro }}
@if ($emailAddress)

Account: {{ $userName ? $userName . ' · ' : '' }}{{ $emailAddress }}
@endif

Your verification code: {{ $otp }}

This code expires in {{ $expiry }} minutes and can be used only once. Never share it with anyone.

@if ($purpose === 'password_reset')
Enter this code on the store website to choose a new password.

If you did not ask to reset your password, you can ignore this email. Your password will stay the same.
@else
If you did not request this, you can ignore this email.
@endif

— {{ $appName }}
Powered by Dukanaz
