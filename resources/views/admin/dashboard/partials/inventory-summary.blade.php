<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Inventory Summary</h5>
                <a href="{{ url('admin/product-variation-stock') }}" class="btn btn-sm btn-outline-primary">Manage Stock</a>
            </div>
            <div class="card-body">
                <div class="row text-center g-3">
                    <div class="col-4">
                        <div class="fs-4 fw-semibold">{{ number_format($inventory['total_products']) }}</div>
                        <small class="text-muted">Tracked Products</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-semibold text-warning">{{ number_format($inventory['low_stock_count']) }}</div>
                        <small class="text-muted">Low Stock</small>
                    </div>
                    <div class="col-4">
                        <div class="fs-4 fw-semibold text-danger">{{ number_format($inventory['out_of_stock_count']) }}</div>
                        <small class="text-muted">Out of Stock</small>
                    </div>
                </div>
                <hr>
                @if (($inventory['total_products'] ?? 0) > 0)
                    <div id="stockDistributionChart" class="mb-2"></div>
                @else
                    <p class="text-muted mb-2">No tracked products yet.</p>
                @endif
                @if ($inventory['low_stock_count'] > 0 || $inventory['out_of_stock_count'] > 0)
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <span class="badge bg-label-warning"><i class="fa fa-exclamation-triangle me-1"></i>{{ $inventory['low_stock_count'] }} Low Stock</span>
                        <span class="badge bg-label-danger"><i class="fa fa-times-circle me-1"></i>{{ $inventory['out_of_stock_count'] }} Out of Stock</span>
                    </div>
                    <a href="{{ url('admin/product-variation-stock') }}" class="btn btn-sm btn-outline-warning">Review Stock Levels</a>
                @else
                    <p class="text-success mb-0"><i class="fa fa-check-circle me-1"></i>All tracked products are sufficiently stocked.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header">
                <h5 class="mb-0">Inventory Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <a href="{{ route('product.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-box me-1"></i> Products</a>
                    </div>
                    <div class="col-6">
                        <a href="{{ url('admin/product-variation-stock') }}" class="btn btn-outline-primary w-100"><i class="fa fa-warehouse me-1"></i> Stock</a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('transfer-note.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-exchange-alt me-1"></i> Stock Transfers</a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('opening-stock.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-dolly me-1"></i> Opening Stock</a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('stock-taking.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-clipboard-check me-1"></i> Stock Taking</a>
                    </div>
                    <div class="col-6">
                        <a href="{{ route('brands.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-tags me-1"></i> Brands</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if (($inventory['total_products'] ?? 0) > 0)
@push('js')
<script>
    (function () {
        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts library not loaded — skipping Stock Distribution chart.');
            return;
        }

        var el = document.querySelector('#stockDistributionChart');
        if (!el) { return; }

        try {
            new ApexCharts(el, {
                series: [{{ (int) $inventory['in_stock_count'] }}, {{ (int) $inventory['low_stock_count'] }}, {{ (int) $inventory['out_of_stock_count'] }}],
                labels: ['In Stock', 'Low Stock', 'Out of Stock'],
                chart: { type: 'donut', height: 220 },
                colors: [config.colors.success, config.colors.warning, config.colors.danger],
                legend: { position: 'bottom', labels: { colors: config.colors.headingColor } },
                dataLabels: { enabled: true, formatter: function (val) { return val.toFixed(1) + '%'; } }
            }).render();
        } catch (e) {
            console.error('Stock Distribution chart failed to render:', e);
        }
    })();
</script>
@endpush
@endif
