@php
$purposeText = [
    'onboarding' => 'complete your account setup',
    'login' => 'log in to your account',
    'password_reset' => 'reset your password',
][$purpose] ?? 'verify your identity';
@endphp

<p>Use the code below to {{ $purposeText }}:</p>

<h2 style="letter-spacing: 4px;">{{ $otp }}</h2>

<p>This code expires in 10 minutes. If you did not request this, you can safely ignore this email.</p>
