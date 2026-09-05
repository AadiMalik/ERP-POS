@php
    $maxRevenue = $sales['top_products']->max('total_revenue') ?: 1;
    $barColors = ['primary', 'success', 'warning', 'info', 'danger', 'secondary'];
@endphp
<div class="card h-100">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Top Selling Products') }}</h5>
        <a href="{{ route('order.history') }}" class="btn btn-sm btn-outline-primary">View Sales</a>
    </div>
    <div class="card-body">
        @forelse ($sales['top_products'] as $index => $row)
            <div class="erp-bar-row" style="--erp-bar-color: var(--bs-{{ $barColors[$index % count($barColors)] }});">
                <div class="erp-bar-row-label">
                    <span>{{ $row->product->name ?? 'Unknown Product' }}{{ $row->productVariation->name ? ' - ' . $row->productVariation->name : '' }}</span>
                    <span class="fw-semibold">{{ currency($row->total_revenue) }}</span>
                </div>
                <div class="erp-bar-track">
                    <div class="erp-bar-fill" style="width: {{ round(($row->total_revenue / $maxRevenue) * 100, 1) }}%;"></div>
                </div>
            </div>
        @empty
            <p class="text-muted mb-0">No sales in the selected period.</p>
        @endforelse
    </div>
</div>
