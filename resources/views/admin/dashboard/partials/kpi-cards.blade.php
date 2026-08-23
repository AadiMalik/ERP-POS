@php
    $heroCards = [];

    $heroCards[] = ['label' => 'Total Sales', 'value' => currency($sales['total_sales'] ?? 0), 'icon' => 'fa-cash-register', 'color' => 'primary', 'route' => route('order.history')];
    $heroCards[] = ['label' => 'Total Orders', 'value' => number_format($sales['total_orders'] ?? 0), 'icon' => 'fa-receipt', 'color' => 'info', 'route' => route('order.index')];

    if (isset($purchases)) {
        $heroCards[] = ['label' => 'Total Purchases', 'value' => number_format($purchases['total_purchases'] ?? 0), 'icon' => 'fa-truck-loading', 'color' => 'warning', 'route' => route('purchase.index')];
    }

    if (isset($finance)) {
        $heroCards[] = ['label' => 'Net Profit', 'value' => currency($finance['net_profit'] ?? 0), 'icon' => 'fa-sack-dollar', 'color' => ($finance['net_profit'] ?? 0) >= 0 ? 'success' : 'danger', 'route' => url('admin/reports/profit-loss')];
    } elseif (isset($inventory)) {
        $heroCards[] = ['label' => 'Stock Value', 'value' => currency($inventory['stock_value'] ?? 0), 'icon' => 'fa-warehouse', 'color' => 'success', 'route' => url('admin/product-variation-stock')];
    }

    $statChips = [];

    if (isset($purchases)) {
        $statChips[] = ['label' => 'Purchase Amount', 'value' => currency($purchases['total_purchase_amount'] ?? 0), 'icon' => 'fa-money-bill-wave', 'color' => 'warning'];
    }

    if (isset($inventory)) {
        $statChips[] = ['label' => 'Stock Value', 'value' => currency($inventory['stock_value'] ?? 0), 'icon' => 'fa-warehouse', 'color' => 'success'];
        $statChips[] = ['label' => 'Low Stock Items', 'value' => number_format($inventory['low_stock_count'] ?? 0), 'icon' => 'fa-exclamation-triangle', 'color' => 'warning'];
        $statChips[] = ['label' => 'Out of Stock', 'value' => number_format($inventory['out_of_stock_count'] ?? 0), 'icon' => 'fa-times-circle', 'color' => 'danger'];
    }

    if (isset($finance)) {
        $statChips[] = ['label' => 'Total Expenses', 'value' => currency($finance['total_expenses'] ?? 0), 'icon' => 'fa-file-invoice-dollar', 'color' => 'danger'];
        $statChips[] = ['label' => 'Gross Profit', 'value' => currency($finance['gross_profit'] ?? 0), 'icon' => 'fa-chart-line', 'color' => ($finance['gross_profit'] ?? 0) >= 0 ? 'success' : 'danger'];
        $statChips[] = ['label' => 'Total Receivables', 'value' => currency($finance['receivables']['total'] ?? 0), 'icon' => 'fa-hand-holding-usd', 'color' => 'info'];
        $statChips[] = ['label' => 'Total Payables', 'value' => currency($finance['payables']['total'] ?? 0), 'icon' => 'fa-file-invoice', 'color' => 'warning'];
        $statChips[] = ['label' => 'Cash/Bank Balance', 'value' => currency($finance['cash_bank_balance'] ?? 0), 'icon' => 'fa-university', 'color' => 'primary'];
    }
@endphp
<div class="row">
    @foreach ($heroCards as $card)
        <div class="col-sm-6 col-xl-3 mb-4">
            <a href="{{ $card['route'] ?? '#' }}" class="text-decoration-none">
                <div class="card h-100 erp-kpi-card erp-kpi-card--gradient"
                    style="--erp-kpi-color: var(--bs-{{ $card['color'] }}); --erp-kpi-color-rgb: var(--bs-{{ $card['color'] }}-rgb);">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2">
                            <div>
                                <span class="erp-kpi-label text-muted">{{ $card['label'] }}</span>
                                <h4 class="erp-kpi-value mb-0 text-body">{{ $card['value'] }}</h4>
                            </div>
                            <div class="erp-kpi-icon">
                                <i class="fa {{ $card['icon'] }}"></i>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

@if (!empty($statChips))
    <div class="row">
        @foreach ($statChips as $chip)
            <div class="col-6 col-md-4 col-xl-2 mb-4">
                <div class="erp-stat-chip" style="--erp-stat-color: var(--bs-{{ $chip['color'] }}); --erp-stat-color-rgb: var(--bs-{{ $chip['color'] }}-rgb);">
                    <div class="erp-stat-icon"><i class="fa {{ $chip['icon'] }}"></i></div>
                    <div>
                        <span class="erp-stat-label">{{ $chip['label'] }}</span>
                        <span class="erp-stat-value">{{ $chip['value'] }}</span>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
@endif
