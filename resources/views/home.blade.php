@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    @if ($restricted ?? false)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fa fa-lock fa-2x text-muted mb-3"></i>
                        <h5 class="mb-2">Dashboard access is not enabled for your role</h5>
                        <p class="text-muted mb-0">Contact your Business Admin to enable dashboard access for you.</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="erp-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="fw-bold mb-1">Welcome back, {{ Auth::user()->name ?? 'Admin' }} 👋</h4>
                <p class="text-muted mb-0">Here's what's happening with your business today.</p>
            </div>
        </div>

        @include('admin.dashboard.partials.filters')

        @include('admin.dashboard.partials.kpi-cards')

        @if (isset($parties) || isset($inventory) || isset($finance))
            @include('admin.dashboard.partials.overview-widgets')
        @endif

        @include('admin.dashboard.partials.sales-charts')

        @if (isset($purchases))
            @include('admin.dashboard.partials.purchase-charts')
        @endif

        @if (isset($comparison))
            @include('admin.dashboard.partials.sales-purchase-comparison')
        @endif

        @if (isset($inventory))
            @include('admin.dashboard.partials.inventory-summary')
        @endif

        @if (isset($finance))
            @include('admin.dashboard.partials.finance-summary')
        @endif

        @if (isset($parties))
            @include('admin.dashboard.partials.parties-summary')
        @endif

        @include('admin.dashboard.partials.recent-activity')

        @if (Auth::user()->business_id && isset($subscription))
            <div class="row">
                @include('admin.dashboard.partials.subscription-widget', ['subscription' => $subscription, 'display_status' => $display_status])
            </div>
        @endif

        @if ($scope['tier'] === 1)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Quick Access</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @canAccess('pos.access')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('order.index') }}" class="erp-quick-action w-100"><i class="fa fa-shopping-cart"></i> Orders</a>
                        </div>
                        @endcanAccess
                        @canAccess('purchase.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('purchase.index') }}" class="erp-quick-action w-100"><i class="fa fa-truck"></i> Purchases</a>
                        </div>
                        @endcanAccess
                        @canAccess('user.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('users.index') }}" class="erp-quick-action w-100"><i class="fa fa-users"></i> Customers</a>
                        </div>
                        @endcanAccess
                        @canAccess('supplier.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('supplier.index') }}" class="erp-quick-action w-100"><i class="fa fa-industry"></i> Suppliers</a>
                        </div>
                        @endcanAccess
                        @canAccess('product.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('product.index') }}" class="erp-quick-action w-100"><i class="fa fa-box"></i> Products</a>
                        </div>
                        @endcanAccess
                        @canAccess('stock.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ url('admin/product-variation-stock') }}" class="erp-quick-action w-100"><i class="fa fa-warehouse"></i> Inventory</a>
                        </div>
                        @endcanAccess
                        @canAccess('transfer-note.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('transfer-note.index') }}" class="erp-quick-action w-100"><i class="fa fa-exchange-alt"></i> Transfers</a>
                        </div>
                        @endcanAccess
                        @canAccess('account.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ url('admin/account') }}" class="erp-quick-action w-100"><i class="fa fa-sitemap"></i> Accounts</a>
                        </div>
                        @endcanAccess
                        @canAccess('journal-entry.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('journal-entry.index') }}" class="erp-quick-action w-100"><i class="fa fa-book"></i> Ledgers</a>
                        </div>
                        @endcanAccess
                        @canAccess('supplier-payment.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('supplier-payment.index') }}" class="erp-quick-action w-100"><i class="fa fa-money-check-alt"></i> Payments</a>
                        </div>
                        @endcanAccess
                        @canAccess('reports.balance-sheet.view', 'accounting')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ url('admin/reports/balance-sheet') }}" class="erp-quick-action w-100"><i class="fa fa-chart-bar"></i> Reports</a>
                        </div>
                        @endcanAccess
                        @canAccess('my-subscription.manage')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('my-subscription.index') }}" class="erp-quick-action w-100"><i class="fa fa-crown"></i> Subscription</a>
                        </div>
                        @endcanAccess
                    </div>
                </div>
            </div>
        @endif
    @endif
</div>
@endsection

@section('js')
<script src="{{ asset('public/assets/js/admin/dashboard.js') }}"></script>
@endsection
