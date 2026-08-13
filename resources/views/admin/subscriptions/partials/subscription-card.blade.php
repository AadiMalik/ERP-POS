@php
    $statusColors = [
        'trial' => 'info',
        'active' => 'success',
        'expiring_soon' => 'warning',
        'payment_pending' => 'warning',
        'grace_period' => 'warning',
        'suspended' => 'secondary',
        'cancelled' => 'dark',
        'expired' => 'danger',
    ];
    $statusLabels = [
        'trial' => 'Trial',
        'active' => 'Active',
        'expiring_soon' => 'Expiring Soon',
        'payment_pending' => 'Payment Pending',
        'grace_period' => 'Grace Period',
        'suspended' => 'Suspended',
        'cancelled' => 'Cancelled',
        'expired' => 'Expired',
    ];
    $color = $statusColors[$display_status] ?? 'secondary';
    $label = $statusLabels[$display_status] ?? ucfirst($display_status ?? 'Unknown');
@endphp

<div class="card mb-4 border-{{ $color }}">
    <div class="card-body">
        @if (!$subscription)
            <div class="text-center text-muted py-3">
                <i class="fa fa-exclamation-triangle fa-2x mb-2 d-block"></i>
                No active subscription found. Please contact support.
            </div>
        @else
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    <span class="badge bg-label-{{ $color }} mb-2">{{ $label }}</span>
                    <h5 class="mb-1">{{ $subscription->package->name ?? '-' }}</h5>
                    <p class="text-muted mb-0">
                        {{ ucfirst($subscription->billing_cycle ?? '') }} billing &middot;
                        {{ currency($subscription->total) }}
                    </p>
                </div>
                <div class="text-md-end">
                    <p class="mb-1"><strong>Expiry:</strong> {{ localDate($subscription->end_at) }}</p>
                    <p class="mb-1">
                        @php $remaining = $subscription->remaining_days; @endphp
                        @if ($remaining !== null && $remaining >= 0)
                            <strong>{{ $remaining }}</strong> day{{ $remaining == 1 ? '' : 's' }} remaining
                        @else
                            <span class="text-danger fw-semibold">Expired</span>
                        @endif
                    </p>
                </div>
            </div>

            @if (in_array($display_status, ['expiring_soon', 'payment_pending', 'grace_period']))
                <div class="alert alert-warning mt-3 mb-0">
                    <i class="fa fa-clock"></i>
                    @if ($display_status === 'grace_period')
                        Your subscription has expired and you're in a grace period. Renew now to avoid interruption.
                    @elseif ($display_status === 'payment_pending')
                        Your last payment is still pending confirmation.
                    @else
                        Your subscription is expiring soon. Renew to keep uninterrupted access.
                    @endif
                </div>
            @elseif ($display_status === 'expired')
                <div class="alert alert-danger mt-3 mb-0">
                    <i class="fa fa-exclamation-circle"></i>
                    Your subscription has expired. Your data is safe, but some features may be restricted until you renew.
                </div>
            @endif

            <div class="mt-3">
                <a href="{{ route('my-subscription.index') }}" class="btn btn-primary">
                    <i class="fa fa-refresh"></i> Renew Subscription
                </a>
            </div>
        @endif
    </div>
</div>
