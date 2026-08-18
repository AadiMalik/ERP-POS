@php
    $hasTrend = ($scope['can_use_date_filter'] ?? true) && !empty($sales['daily_trend']);
    $topProductsLabels = $sales['top_products']->map(function ($row) {
        $name = $row->product->name ?? 'Unknown Product';
        return $row->productVariation->name ? $name . ' - ' . $row->productVariation->name : $name;
    });
@endphp
<div class="row">
    @if ($hasTrend)
        <div class="col-lg-8 mb-4">
            <div class="card h-100">
                <div class="card-header">
                    <h5 class="mb-0">Sales Trend</h5>
                </div>
                <div class="card-body">
                    <div id="salesTrendChart"></div>
                </div>
            </div>
        </div>
    @endif
    <div class="col-lg-{{ $hasTrend ? 4 : 12 }} mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Top Selling Products</h5>
                <a href="{{ route('order.history') }}" class="btn btn-sm btn-outline-primary">View Sales</a>
            </div>
            <div class="card-body" style="max-height: 320px; overflow-y: auto;">
                @forelse ($sales['top_products'] as $row)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h6 class="mb-0">{{ $row->product->name ?? 'Unknown Product' }}</h6>
                            <small class="text-muted">{{ $row->productVariation->name ?? '' }}</small>
                        </div>
                        <div class="text-end">
                            <div class="fw-semibold">{{ currency($row->total_revenue) }}</div>
                            <small class="text-muted">{{ number_format($row->total_quantity, 0) }} sold</small>
                        </div>
                    </div>
                @empty
                    <p class="text-muted mb-0">No sales in the selected period.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

@if ($sales['top_products']->isNotEmpty())
<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">Top Selling Products (Revenue)</h5></div>
            <div class="card-body">
                <div id="topProductsChart"></div>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Sales by Order Type</h5></div>
            <div class="card-body">
                @if (($sales['by_order_type'] ?? collect())->isNotEmpty())
                    <div id="orderTypeChart"></div>
                @else
                    <p class="text-muted mb-0">No data for the selected period.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Sales by Payment Method</h5></div>
            <div class="card-body">
                @if (($sales['by_payment_method'] ?? collect())->isNotEmpty())
                    <div id="paymentMethodChart"></div>
                @else
                    <p class="text-muted mb-0">No data for the selected period.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Sales by Order Source</h5></div>
            <div class="card-body">
                @if (($sales['by_order_source'] ?? collect())->isNotEmpty())
                    <div id="orderSourceChart"></div>
                @else
                    <p class="text-muted mb-0">No data for the selected period.</p>
                @endif
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    (function () {
        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts library not loaded — skipping Sales charts.');
            return;
        }

        var chartColors = [config.colors.primary, config.colors.success, config.colors.info, config.colors.warning, config.colors.danger, config.colors.secondary];

        @if ($hasTrend)
            try {
                var trendData = @json($sales['daily_trend']);
                new ApexCharts(document.querySelector('#salesTrendChart'), {
                    series: [{ name: 'Sales', data: Object.values(trendData) }],
                    chart: { type: 'area', height: 300, toolbar: { show: false } },
                    colors: [config.colors.primary],
                    dataLabels: { enabled: false },
                    stroke: { curve: 'smooth', width: 2 },
                    fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
                    xaxis: { categories: Object.keys(trendData), labels: { style: { colors: config.colors.axisColor } } },
                    yaxis: { labels: { style: { colors: config.colors.axisColor } } },
                    grid: { borderColor: config.colors.borderColor },
                    tooltip: { y: { formatter: function (val) { return currency_symbol + ' ' + val.toFixed(2); } } }
                }).render();
            } catch (e) {
                console.error('Sales Trend chart failed to render:', e);
            }
        @endif

        function donutChart(elementId, data) {
            var el = document.querySelector(elementId);
            if (!el) { return; }
            try {
                new ApexCharts(el, {
                    series: Object.values(data),
                    labels: Object.keys(data),
                    chart: { type: 'donut', height: 260 },
                    colors: chartColors,
                    legend: { position: 'bottom', labels: { colors: config.colors.headingColor } },
                    dataLabels: { enabled: true, formatter: function (val) { return val.toFixed(1) + '%'; } },
                    tooltip: { y: { formatter: function (val) { return currency_symbol + ' ' + val.toFixed(2); } } }
                }).render();
            } catch (e) {
                console.error('Chart ' + elementId + ' failed to render:', e);
            }
        }

        donutChart('#orderTypeChart', @json($sales['by_order_type'] ?? []));
        donutChart('#paymentMethodChart', @json($sales['by_payment_method'] ?? []));
        donutChart('#orderSourceChart', @json($sales['by_order_source'] ?? []));

        @if ($sales['top_products']->isNotEmpty())
            try {
                new ApexCharts(document.querySelector('#topProductsChart'), {
                    series: [{ name: 'Revenue', data: @json($sales['top_products']->pluck('total_revenue')) }],
                    chart: { type: 'bar', height: 280, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, borderRadius: 4 } },
                    colors: [config.colors.primary],
                    dataLabels: { enabled: false },
                    xaxis: { categories: @json($topProductsLabels), labels: { style: { colors: config.colors.axisColor } } },
                    grid: { borderColor: config.colors.borderColor },
                    tooltip: { y: { formatter: function (val) { return currency_symbol + ' ' + val.toFixed(2); } } }
                }).render();
            } catch (e) {
                console.error('Top Products chart failed to render:', e);
            }
        @endif
    })();
</script>
@endpush
