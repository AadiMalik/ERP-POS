@php
    $appName = $app_name ?? 'our store';
    $firstName = $first_name ?? null;
    $userName = $user_name ?? $firstName;
    $greeting = $firstName ? 'Hi ' . $firstName : 'Hi';
    $expiry = (int) ($expiry_minutes ?? 10);
    $year = $year ?? date('Y');
    $emailAddress = is_string($account_email ?? null) ? $account_email : '';
    $logoPath = $logo_path ?? null;
    $logoSrc = $logo_url ?? null;
    if (isset($message) && is_string($logoPath) && is_file($logoPath) && method_exists($message, 'embed')) {
        $logoSrc = $message->embed($logoPath);
    }
    $purpose = $purpose ?? 'password_reset';
    $primary = $brand_primary ?? '#0B1B32';
    $accent = $brand_accent ?? '#2DD4BF';

    $copy = [
        'password_reset' => [
            'preheader' => 'Your ' . $appName . ' password reset code expires in ' . $expiry . ' minutes.',
            'eyebrow' => 'Password reset',
            'headline' => 'Reset your password',
            'intro' => $userName
                ? 'We received a request to reset the password for your ' . $appName . ' account.'
                : 'We received a request to reset a ' . $appName . ' account password.',
            'code_label' => 'Your verification code',
            'ignore' => 'If you did not ask to reset your password, you can ignore this email. Your password will stay the same and your account remains secure.',
        ],
        'login' => [
            'preheader' => 'Your ' . $appName . ' sign-in code expires in ' . $expiry . ' minutes.',
            'eyebrow' => 'Sign in',
            'headline' => 'Sign in to ' . $appName,
            'intro' => $userName
                ? 'Use this code to sign in to your ' . $appName . ' account.'
                : 'Use this code to sign in to ' . $appName . '.',
            'code_label' => 'Your sign-in code',
            'ignore' => 'If you did not try to sign in, you can ignore this email. Do not share this code with anyone.',
        ],
        'onboarding' => [
            'preheader' => 'Verify your ' . $appName . ' account. This code expires in ' . $expiry . ' minutes.',
            'eyebrow' => 'Account verification',
            'headline' => 'Verify your email',
            'intro' => 'Welcome to ' . $appName . '. Use this code to finish setting up your account.',
            'code_label' => 'Your verification code',
            'ignore' => 'If you did not create a ' . $appName . ' account, you can ignore this email.',
        ],
    ][$purpose] ?? [
        'preheader' => 'Your ' . $appName . ' verification code expires in ' . $expiry . ' minutes.',
        'eyebrow' => 'Verification',
        'headline' => 'Verify your identity',
        'intro' => 'Use the code below to continue.',
        'code_label' => 'Your verification code',
        'ignore' => 'If you did not request this, you can ignore this email.',
    ];
@endphp
<!DOCTYPE html>
<html lang="en" xmlns="http://www.w3.org/1999/xhtml" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <title>{{ $copy['headline'] }}</title>
    <!--[if mso]>
    <noscript>
        <xml>
            <o:OfficeDocumentSettings>
                <o:PixelsPerInch>96</o:PixelsPerInch>
            </o:OfficeDocumentSettings>
        </xml>
    </noscript>
    <![endif]-->
</head>
<body style="margin:0;padding:0;background-color:#EEF1F6;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;-webkit-text-size-adjust:100%;-ms-text-size-adjust:100%;">
    <div style="display:none;max-height:0;overflow:hidden;mso-hide:all;font-size:1px;line-height:1px;color:#EEF1F6;">
        {{ $copy['preheader'] }}
    </div>
    <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:#EEF1F6;margin:0;padding:0;">
        <tr>
            <td align="center" style="padding:32px 16px;">
                <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="600" style="width:100%;max-width:600px;">
                    <tr>
                        <td align="center" style="padding:0 0 24px 0;">
                            @if ($logoSrc)
                                <img src="{{ $logoSrc }}" alt="{{ $appName }}" width="180" style="display:block;border:0;outline:none;text-decoration:none;height:auto;max-width:180px;max-height:72px;">
                            @else
                                <span style="font-size:26px;font-weight:700;letter-spacing:-0.4px;color:{{ $primary }};">{{ $appName }}</span>
                            @endif
                        </td>
                    </tr>
                    <tr>
                        <td style="background-color:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 8px 30px rgba(11,27,50,0.08);">
                            <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%">
                                <tr>
                                    <td style="height:6px;background-color:{{ $accent }};font-size:0;line-height:0;">&nbsp;</td>
                                </tr>
                                <tr>
                                    <td style="padding:36px 40px 16px 40px;">
                                        <p style="margin:0 0 8px 0;font-size:12px;font-weight:600;letter-spacing:1.6px;text-transform:uppercase;color:{{ $accent }};">
                                            {{ $copy['eyebrow'] }}
                                        </p>
                                        <h1 style="margin:0 0 16px 0;font-size:26px;line-height:1.25;font-weight:700;color:{{ $primary }};">
                                            {{ $copy['headline'] }}
                                        </h1>
                                        <p style="margin:0 0 8px 0;font-size:16px;line-height:1.6;color:{{ $primary }};">
                                            {{ $greeting }},
                                        </p>
                                        <p style="margin:0;font-size:16px;line-height:1.6;color:#4B5A6F;">
                                            {{ $copy['intro'] }}
                                        </p>
                                        @if ($emailAddress)
                                            <p style="margin:16px 0 0 0;font-size:14px;line-height:1.5;color:#4B5A6F;">
                                                Account:
                                                <strong style="color:{{ $primary }};">{{ $userName ?: $emailAddress }}</strong>
                                                @if ($userName)
                                                    <span style="color:#7A8799;"> · {{ $emailAddress }}</span>
                                                @endif
                                            </p>
                                        @endif
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:8px 40px 8px 40px;">
                                        <table role="presentation" cellpadding="0" cellspacing="0" border="0" width="100%" style="background-color:{{ $primary }};border-radius:12px;">
                                            <tr>
                                                <td align="center" style="padding:28px 24px;">
                                                    <p style="margin:0 0 10px 0;font-size:12px;font-weight:600;letter-spacing:1.4px;text-transform:uppercase;color:#ffffff;">
                                                        {{ $copy['code_label'] }}
                                                    </p>
                                                    <p style="margin:0;font-size:36px;line-height:1.2;font-weight:700;letter-spacing:10px;color:#ffffff;font-family:'Segoe UI',Roboto,Helvetica,Arial,sans-serif;">
                                                        {{ $otp }}
                                                    </p>
                                                    <p style="margin:12px 0 0 0;font-size:13px;color:#ffffff;opacity:0.8;">
                                                        Expires in {{ $expiry }} minutes · one-time use
                                                    </p>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:20px 40px 12px 40px;">
                                        <p style="margin:0;font-size:14px;line-height:1.6;color:#4B5A6F;">
                                            @if ($purpose === 'password_reset')
                                                Enter this code on the store website to choose a new password.
                                                Never share it with anyone — {{ $appName }} staff will never ask for it.
                                            @elseif ($purpose === 'login')
                                                Enter this code to finish signing in. Never share it with anyone — {{ $appName }} staff will never ask for it.
                                            @else
                                                Enter this code to continue. Never share it with anyone — {{ $appName }} staff will never ask for it.
                                            @endif
                                        </p>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="padding:16px 40px 36px 40px;">
                                        <p style="margin:0;font-size:13px;line-height:1.6;color:#7A8799;">
                                            {{ $copy['ignore'] }}
                                        </p>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    <tr>
                        <td align="center" style="padding:24px 16px 8px 16px;">
                            <p style="margin:0 0 12px 0;font-size:12px;line-height:1.5;color:#9AA6B5;">
                                © {{ $year }} {{ $appName }}. This is an automated security email.
                            </p>
                            <p style="margin:0;font-size:12px;line-height:1.5;color:#7A8799;">
                                Powered by
                                <span style="font-weight:700;color:#0B1B32;">Dukan<span style="color:#2DD4BF;">az</span></span>
                            </p>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
