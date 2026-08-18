@php
    $hasPurchaseTrend = ($scope['can_use_date_filter'] ?? true) && !empty($purchases['daily_trend']);
@endphp
@if ($hasPurchaseTrend)
<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Purchase Trend</h5>
            </div>
            <div class="card-body">
                <div id="purchaseTrendChart"></div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    (function () {
        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts library not loaded — skipping Purchase Trend chart.');
            return;
        }

        try {
            var trendData = @json($purchases['daily_trend']);
            new ApexCharts(document.querySelector('#purchaseTrendChart'), {
                series: [{ name: 'Purchases', data: Object.values(trendData) }],
                chart: { type: 'area', height: 300, toolbar: { show: false } },
                colors: [config.colors.warning],
                dataLabels: { enabled: false },
                stroke: { curve: 'smooth', width: 2 },
                fill: { type: 'gradient', gradient: { shadeIntensity: 1, opacityFrom: 0.4, opacityTo: 0.05, stops: [0, 90, 100] } },
                xaxis: { categories: Object.keys(trendData), labels: { style: { colors: config.colors.axisColor } } },
                yaxis: { labels: { style: { colors: config.colors.axisColor } } },
                grid: { borderColor: config.colors.borderColor },
                tooltip: { y: { formatter: function (val) { return currency_symbol + ' ' + val.toFixed(2); } } }
            }).render();
        } catch (e) {
            console.error('Purchase Trend chart failed to render:', e);
        }
    })();
</script>
@endpush
@endif
