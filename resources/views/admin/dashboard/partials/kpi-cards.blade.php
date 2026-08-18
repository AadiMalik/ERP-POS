@php
    $cards = [];

    $cards[] = ['label' => 'Total Sales', 'value' => currency($sales['total_sales'] ?? 0), 'icon' => 'fa-cash-register', 'color' => 'primary', 'route' => route('order.history')];
    $cards[] = ['label' => 'Total Orders', 'value' => number_format($sales['total_orders'] ?? 0), 'icon' => 'fa-receipt', 'color' => 'info', 'route' => route('order.index')];

    if (isset($purchases)) {
        $cards[] = ['label' => 'Total Purchases', 'value' => number_format($purchases['total_purchases'] ?? 0), 'icon' => 'fa-truck-loading', 'color' => 'secondary', 'route' => route('purchase.index')];
        $cards[] = ['label' => 'Purchase Amount', 'value' => currency($purchases['total_purchase_amount'] ?? 0), 'icon' => 'fa-money-bill-wave', 'color' => 'warning', 'route' => route('purchase.index')];
    }

    if (isset($inventory)) {
        $cards[] = ['label' => 'Stock Value', 'value' => currency($inventory['stock_value'] ?? 0), 'icon' => 'fa-warehouse', 'color' => 'success', 'route' => url('admin/product-variation-stock')];
    }

    if (isset($finance)) {
        $cards[] = ['label' => 'Total Expenses', 'value' => currency($finance['total_expenses'] ?? 0), 'icon' => 'fa-file-invoice-dollar', 'color' => 'danger', 'route' => url('admin/reports/expense-report')];
        $cards[] = ['label' => 'Gross Profit', 'value' => currency($finance['gross_profit'] ?? 0), 'icon' => 'fa-chart-line', 'color' => ($finance['gross_profit'] ?? 0) >= 0 ? 'success' : 'danger', 'route' => url('admin/reports/profit-loss')];
        $cards[] = ['label' => 'Net Profit', 'value' => currency($finance['net_profit'] ?? 0), 'icon' => 'fa-sack-dollar', 'color' => ($finance['net_profit'] ?? 0) >= 0 ? 'success' : 'danger', 'route' => url('admin/reports/profit-loss')];
        $cards[] = ['label' => 'Total Receivables', 'value' => currency($finance['receivables']['total'] ?? 0), 'icon' => 'fa-hand-holding-usd', 'color' => 'info', 'route' => route('users.index')];
        $cards[] = ['label' => 'Total Payables', 'value' => currency($finance['payables']['total'] ?? 0), 'icon' => 'fa-file-invoice', 'color' => 'warning', 'route' => url('admin/reports/accounts-payable')];
        $cards[] = ['label' => 'Cash/Bank Balance', 'value' => currency($finance['cash_bank_balance'] ?? 0), 'icon' => 'fa-university', 'color' => 'primary', 'route' => url('admin/reports/cash-bank-ledger')];
    }
@endphp
<div class="row">
    @foreach ($cards as $card)
        <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
            <a href="{{ $card['route'] ?? '#' }}" class="text-decoration-none">
                <div class="card h-100 dashboard-kpi-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <span class="text-muted d-block mb-1">{{ $card['label'] }}</span>
                                <h4 class="mb-0 text-body">{{ $card['value'] }}</h4>
                            </div>
                            <div class="avatar flex-shrink-0">
                                <span class="avatar-initial rounded bg-label-{{ $card['color'] }}">
                                    <i class="fa {{ $card['icon'] }}"></i>
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>
