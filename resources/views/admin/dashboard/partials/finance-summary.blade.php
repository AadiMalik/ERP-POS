<div class="row">
    <div class="col-12 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('Revenue, Expenses &amp; Profit Overview') }}</h5></div>
            <div class="card-body">
                <div id="expenseProfitChart"></div>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Account/COA Summary</h5>
                <a href="{{ url('admin/reports/balance-sheet') }}" class="btn btn-sm btn-outline-primary">Balance Sheet</a>
            </div>
            <div class="card-body">
                <div id="coaSummaryChart"></div>
                <hr>
                <div class="d-flex justify-content-between mb-1"><span>Total Assets</span><span class="fw-semibold">{{ currency($finance['total_assets']) }}</span></div>
                <div class="d-flex justify-content-between mb-1"><span>Total Liabilities</span><span class="fw-semibold">{{ currency($finance['total_liabilities']) }}</span></div>
                <div class="d-flex justify-content-between"><span>Total Equity</span><span class="fw-semibold">{{ currency($finance['total_equity']) }}</span></div>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Outstanding Customers</h5>
                <a href="{{ route('users.index') }}" class="btn btn-sm btn-outline-primary">Customers</a>
            </div>
            <div class="card-body">
                @forelse ($finance['receivables']['top'] as $row)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>{{ $row['name'] }}</span>
                        <span class="badge bg-label-info">{{ currency($row['balance']) }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No outstanding receivables.</p>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Outstanding Suppliers</h5>
                <a href="{{ url('admin/reports/accounts-payable') }}" class="btn btn-sm btn-outline-primary">Payables</a>
            </div>
            <div class="card-body">
                @forelse ($finance['payables']['top'] as $row)
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span>{{ $row['name'] }}</span>
                        <span class="badge bg-label-warning">{{ currency($row['balance']) }}</span>
                    </div>
                @empty
                    <p class="text-muted mb-0">No outstanding payables.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Ledger Activity</h5>
                <a href="{{ route('journal-entry.index') }}" class="btn btn-sm btn-outline-primary">Journal Entries</a>
            </div>
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Date</th>
                            <th>Voucher</th>
                            <th>Description</th>
                            <th class="text-end">Debit</th>
                            <th class="text-end">Credit</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($finance['ledger_activity'] as $row)
                            <tr>
                                <td>{{ \Carbon\Carbon::parse($row->entry_date)->format('d M Y') }}</td>
                                <td>{{ $row->voucher_name ?? $row->voucher_short ?? '-' }}</td>
                                <td>{{ $row->detail_description ?? $row->entry_description ?? '-' }}</td>
                                <td class="text-end">{{ $row->debit > 0 ? currency($row->debit) : '' }}</td>
                                <td class="text-end">{{ $row->credit > 0 ? currency($row->credit) : '' }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted">No ledger activity in the selected period.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

@push('js')
<script>
    (function () {
        if (typeof ApexCharts === 'undefined') {
            console.error('ApexCharts library not loaded — skipping Finance charts.');
            return;
        }

        var el = document.querySelector('#coaSummaryChart');
        if (el) {
            try {
                new ApexCharts(el, {
                    series: [{ name: 'Amount', data: [{{ (float) $finance['total_assets'] }}, {{ (float) $finance['total_liabilities'] }}, {{ (float) $finance['total_equity'] }}] }],
                    chart: { type: 'bar', height: 200, toolbar: { show: false } },
                    plotOptions: { bar: { horizontal: true, borderRadius: 6, distributed: true } },
                    colors: [config.colors.success, config.colors.danger, config.colors.info],
                    legend: { show: false },
                    dataLabels: { enabled: false },
                    xaxis: { categories: ['Assets', 'Liabilities', 'Equity'], labels: { style: { colors: config.colors.axisColor } } },
                    grid: { borderColor: config.colors.borderColor },
                    tooltip: { y: { formatter: function (val) { return currency_symbol + ' ' + val.toFixed(2); } } }
                }).render();
            } catch (e) {
                console.error('COA Summary chart failed to render:', e);
            }
        }

        try {
            new ApexCharts(document.querySelector('#expenseProfitChart'), {
                series: [{ name: 'Amount', data: [{{ (float) ($sales['total_sales'] ?? 0) }}, {{ (float) ($finance['total_expenses'] ?? 0) }}, {{ (float) ($finance['net_profit'] ?? 0) }}] }],
                chart: { type: 'bar', height: 260, toolbar: { show: false } },
                plotOptions: { bar: { horizontal: false, borderRadius: 6, distributed: true, columnWidth: '45%' } },
                colors: [config.colors.primary, config.colors.danger, config.colors.success],
                legend: { show: false },
                dataLabels: { enabled: false },
                xaxis: { categories: ['Revenue', 'Expenses', 'Net Profit'], labels: { style: { colors: config.colors.axisColor } } },
                grid: { borderColor: config.colors.borderColor },
                tooltip: { y: { formatter: function (val) { return currency_symbol + ' ' + val.toFixed(2); } } }
            }).render();
        } catch (e) {
            console.error('Expense/Profit Overview chart failed to render:', e);
        }
    })();
</script>
@endpush
