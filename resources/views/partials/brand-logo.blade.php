@php
    $variant = $variant ?? 'sidebar';
    $brandBase = asset('public/assets/img/brand');
@endphp

@if ($variant === 'sidebar')
    <a href="{{ url('/home') }}" class="app-brand-link dukanaz-brand-link">
        <span class="app-brand-logo demo dukanaz-brand-logo">
            <img src="{{ $brandBase }}/horizontal-lockup.png" alt="Dukanaz" class="dukanaz-brand-img dukanaz-brand-img--sidebar">
        </span>
    </a>
@elseif ($variant === 'login')
    <a href="{{ route('login') }}" class="app-brand-link dukanaz-brand-link justify-content-center">
        <span class="app-brand-logo demo dukanaz-brand-logo dukanaz-brand-logo--login">
            <img src="{{ asset('public/assets/img/favicon/favicon-192.png') }}" alt="Dukanaz" class="dukanaz-brand-img dukanaz-brand-img--login">
        </span>
    </a>
@elseif ($variant === 'footer')
    <span class="dukanaz-footer-brand">
        <img src="{{ $brandBase }}/icon-only.png" alt="" class="dukanaz-footer-icon" width="18" height="18" aria-hidden="true">
        <span>Powered by <strong>Dukan<span class="dukanaz-accent">az</span></strong></span>
    </span>
@elseif ($variant === 'icon')
    <img src="{{ $brandBase }}/icon-only.png" alt="Dukanaz" class="dukanaz-brand-icon" width="{{ $size ?? 32 }}" height="{{ $size ?? 32 }}">
@endif
