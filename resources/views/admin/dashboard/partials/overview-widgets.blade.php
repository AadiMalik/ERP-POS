@php
    $showBusinessOverview = isset($parties) || isset($inventory);
    $showAccountSummary = isset($finance);
    $overviewColClass = ($showBusinessOverview && $showAccountSummary) ? 'col-lg-6' : 'col-lg-12';
@endphp
<div class="row">
    @if ($showBusinessOverview)
        <div class="{{ $overviewColClass }} mb-4">
            <div class="card h-100 erp-widget-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fa fa-building me-2 text-primary"></i>Business Overview</h5>
                </div>
                <div class="card-body">
                    @if (isset($parties))
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="fa fa-users me-2 text-primary"></i>Total Customers</span>
                            <span class="fw-semibold">{{ number_format($parties['customers']['total']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="fa fa-industry me-2 text-info"></i>Total Suppliers</span>
                            <span class="fw-semibold">{{ number_format($parties['suppliers']['total']) }}</span>
                        </div>
                    @endif
                    @if (isset($inventory))
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="fa fa-box me-2 text-secondary"></i>Total Products</span>
                            <span class="fw-semibold">{{ number_format($inventory['total_products']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <span><i class="fa fa-exclamation-triangle me-2 text-warning"></i>Low Stock Items</span>
                            <span class="fw-semibold text-warning">{{ number_format($inventory['low_stock_count']) }}</span>
                        </div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span><i class="fa fa-times-circle me-2 text-danger"></i>Out of Stock Items</span>
                            <span class="fw-semibold text-danger">{{ number_format($inventory['out_of_stock_count']) }}</span>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    @endif

    @if ($showAccountSummary)
        <div class="{{ $overviewColClass }} mb-4">
            <div class="card h-100 erp-widget-card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fa fa-wallet me-2 text-success"></i>Account Summary</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3 text-center">
                        <div class="col-6">
                            <div class="erp-widget-stat p-2 rounded" style="background: rgba(var(--bs-success-rgb), .08);">
                                <div class="fs-5 fw-semibold text-success">{{ currency($finance['cash_bank_balance'] ?? 0) }}</div>
                                <small class="text-muted">Cash/Bank Balance</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="erp-widget-stat p-2 rounded" style="background: rgba(var(--bs-info-rgb), .08);">
                                <div class="fs-5 fw-semibold text-info">{{ currency($finance['receivables']['total'] ?? 0) }}</div>
                                <small class="text-muted">Total Receivables</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="erp-widget-stat p-2 rounded" style="background: rgba(var(--bs-warning-rgb), .08);">
                                <div class="fs-5 fw-semibold text-warning">{{ currency($finance['payables']['total'] ?? 0) }}</div>
                                <small class="text-muted">Total Payables</small>
                            </div>
                        </div>
                        <div class="col-6">
                            <div class="erp-widget-stat p-2 rounded" style="background: rgba(var(--erp-primary-rgb), .08);">
                                <div class="fs-5 fw-semibold" style="color: var(--erp-primary);">{{ currency($finance['total_expenses'] ?? 0) }}</div>
                                <small class="text-muted">Total Expenses</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>
