@if (isset($comparison))
<div class="row">
    <div class="col-lg-12 mb-4">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">{{ __('Sales vs Purchases') }}</h5>
            </div>
            <div class="card-body">
                <div id="salesPurchaseComparisonChart"></div>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    (function () {
        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts library not loaded — skipping Sales vs Purchases chart.');
            return;
        }

        try {
            new ApexCharts(document.querySelector('#salesPurchaseComparisonChart'), {
                series: [
                    { name: 'Sales', data: @json($comparison['sales']) },
                    { name: 'Purchases', data: @json($comparison['purchases']) },
                ],
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                colors: [config.colors.primary, config.colors.warning],
                plotOptions: { bar: { columnWidth: '55%', borderRadius: 4 } },
                dataLabels: { enabled: false },
                xaxis: { categories: @json($comparison['categories']), labels: { style: { colors: config.colors.axisColor } } },
                yaxis: { labels: { style: { colors: config.colors.axisColor } } },
                legend: { position: 'bottom', labels: { colors: config.colors.headingColor } },
                grid: { borderColor: config.colors.borderColor },
                tooltip: { y: { formatter: function (val) { return currency_symbol + ' ' + val.toFixed(2); } } }
            }).render();
        } catch (e) {
            console.error('Sales vs Purchases chart failed to render:', e);
        }
    })();
</script>
@endpush
@endif
