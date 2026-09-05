@php
    $hasTrend = ($scope['can_use_date_filter'] ?? true) && !empty($sales['daily_trend']);
    $topProductsLabels = $sales['top_products']->map(function ($row) {
        $name = $row->product->name ?? 'Unknown Product';
        return $row->productVariation->name ? $name . ' - ' . $row->productVariation->name : $name;
    });

    $gauges = [];
    if (isset($finance)) {
        $netMarginPct = ($sales['total_sales'] ?? 0) > 0 ? round((($finance['net_profit'] ?? 0) / $sales['total_sales']) * 100, 1) : 0;
        $grossMarginPct = ($sales['total_sales'] ?? 0) > 0 ? round((($finance['gross_profit'] ?? 0) / $sales['total_sales']) * 100, 1) : 0;
        $gauges[] = ['id' => 'netMarginGauge', 'label' => 'Net Margin', 'value' => $netMarginPct, 'color' => $netMarginPct >= 0 ? 'success' : 'danger'];
        $gauges[] = ['id' => 'grossMarginGauge', 'label' => 'Gross Margin', 'value' => $grossMarginPct, 'color' => $grossMarginPct >= 0 ? 'info' : 'danger'];
    }
    if (isset($inventory)) {
        $stockPct = ($inventory['total_products'] ?? 0) > 0 ? round((($inventory['in_stock_count'] ?? 0) / $inventory['total_products']) * 100, 1) : 0;
        $gauges[] = ['id' => 'stockHealthGauge', 'label' => 'Stock Health', 'value' => $stockPct, 'color' => $stockPct >= 50 ? 'success' : 'warning'];
    }
@endphp
<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('Sales Trend') }}</h5></div>
            <div class="card-body">
                @if ($hasTrend)
                    <div id="salesTrendChart"></div>
                @else
                    <p class="text-muted mb-0">No trend data for the selected period.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-3 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Top Selling Products</h5></div>
            <div class="card-body">
                @if ($sales['top_products']->isNotEmpty())
                    <div id="topProductsChart"></div>
                @else
                    <p class="text-muted mb-0">No sales in the selected period.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-3 mb-4">
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
    <div class="col-lg-2 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">Business Health</h5></div>
            <div class="card-body">
                @forelse ($gauges as $gauge)
                    <div class="erp-gauge-row mb-2">
                        <div id="{{ $gauge['id'] }}" class="erp-gauge-chart"></div>
                        <span class="erp-gauge-label">{{ $gauge['label'] }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No data available.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-md-6 mb-4">
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
    <div class="col-md-6 mb-4">
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

        function donutChart(elementId, data, height) {
            var el = document.querySelector(elementId);
            if (!el) { return; }
            try {
                new ApexCharts(el, {
                    series: Object.values(data),
                    labels: Object.keys(data),
                    chart: { type: 'donut', height: height || 260 },
                    colors: chartColors,
                    legend: { position: 'bottom', labels: { colors: config.colors.headingColor } },
                    dataLabels: { enabled: true, formatter: function (val) { return val.toFixed(1) + '%'; } },
                    tooltip: { y: { formatter: function (val) { return currency_symbol + ' ' + val.toFixed(2); } } }
                }).render();
            } catch (e) {
                console.error('Chart ' + elementId + ' failed to render:', e);
            }
        }

        @if (($sales['by_payment_method'] ?? collect())->isNotEmpty())
            donutChart('#paymentMethodChart', @json($sales['by_payment_method']), 240);
        @endif
        @if (($sales['by_order_type'] ?? collect())->isNotEmpty())
            donutChart('#orderTypeChart', @json($sales['by_order_type']));
        @endif
        @if (($sales['by_order_source'] ?? collect())->isNotEmpty())
            donutChart('#orderSourceChart', @json($sales['by_order_source']));
        @endif

        @if ($sales['top_products']->isNotEmpty())
            try {
                new ApexCharts(document.querySelector('#topProductsChart'), {
                    series: [{ name: 'Revenue', data: @json($sales['top_products']->pluck('total_revenue')) }],
                    chart: { type: 'bar', height: 240, toolbar: { show: false } },
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

        function gauge(elementId, value, color) {
            var el = document.querySelector(elementId);
            if (!el) { return; }
            try {
                new ApexCharts(el, {
                    series: [value],
                    chart: { type: 'radialBar', height: 76, sparkline: { enabled: true } },
                    colors: [color],
                    plotOptions: { radialBar: { hollow: { size: '55%' }, track: { background: config.colors.borderColor }, dataLabels: { name: { show: false }, value: { offsetY: 5, fontSize: '11px', formatter: function (val) { return val + '%'; } } } } },
                }).render();
            } catch (e) {
                console.error('Gauge ' + elementId + ' failed to render:', e);
            }
        }

        @foreach ($gauges as $gauge)
            gauge('#{{ $gauge['id'] }}', {{ $gauge['value'] }}, config.colors.{{ $gauge['color'] }});
        @endforeach
    })();
</script>
@endpush
