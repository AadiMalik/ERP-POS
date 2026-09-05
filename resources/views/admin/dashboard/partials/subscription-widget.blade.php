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

    $remaining = $subscription->remaining_days ?? null;
    $totalDays = $subscription
        ? \Carbon\Carbon::parse($subscription->start_at)->diffInDays(\Carbon\Carbon::parse($subscription->end_at))
        : 0;
    $remainingPct = ($totalDays > 0 && $remaining !== null && $remaining >= 0)
        ? min(100, round(($remaining / $totalDays) * 100))
        : 0;
@endphp
<div class="col-sm-6 col-lg-3 mb-4">
    <div class="card h-100 border-{{ $color }}">
        <div class="card-header d-flex justify-content-between align-items-center">
            <h5 class="mb-0">{{ __('Subscription') }}</h5>
            <span class="badge bg-label-{{ $color }}">{{ $label }}</span>
        </div>
        <div class="card-body">
            @if (!$subscription)
                <p class="text-muted mb-0">No active subscription found.</p>
            @else
                <div id="subscriptionGauge"></div>
                <p class="mb-1 fw-semibold">{{ $subscription->package->name ?? '-' }}</p>
                <p class="text-muted mb-2">
                    @if ($remaining !== null && $remaining >= 0)
                        Expires {{ localDate($subscription->end_at) }} ({{ $remaining }} day{{ $remaining == 1 ? '' : 's' }} left)
                    @else
                        <span class="text-danger fw-semibold">Expired</span>
                    @endif
                </p>
                <a href="{{ route('my-subscription.index') }}" class="btn btn-sm btn-outline-primary w-100">
                    <i class="fa fa-refresh"></i> View Details
                </a>
            @endif
        </div>
    </div>
</div>

@if ($subscription)
@push('js')
<script>
    (function () {
        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts library not loaded — skipping Subscription gauge.');
            return;
        }

        try {
            new ApexCharts(document.querySelector('#subscriptionGauge'), {
                series: [{{ $remainingPct }}],
                chart: { type: 'radialBar', height: 150 },
                colors: [{{ $remainingPct > 20 ? 'config.colors.success' : 'config.colors.danger' }}],
                plotOptions: { radialBar: { hollow: { size: '55%' }, dataLabels: { value: { formatter: function (val) { return val + '%'; } } } } },
                labels: ['Remaining'],
            }).render();
        } catch (e) {
            console.error('Subscription gauge failed to render:', e);
        }
    })();
</script>
@endpush
@endif
