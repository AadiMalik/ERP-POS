@php
    $labels = [
        'trial' => ['Trial', 'info'],
        'active' => ['Active', 'success'],
        'expiring_soon' => ['Expiring Soon', 'warning'],
        'payment_pending' => ['Payment Pending', 'warning'],
        'grace_period' => ['Grace Period', 'warning'],
        'suspended' => ['Suspended', 'secondary'],
        'cancelled' => ['Cancelled', 'dark'],
        'expired' => ['Expired', 'danger'],
    ];
    [$label, $color] = $labels[$status] ?? [ucfirst($status ?? '-'), 'secondary'];
@endphp
<span class="badge bg-label-{{ $color }}">{{ $label }}</span>
