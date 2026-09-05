@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    @if ($restricted ?? false)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fa fa-lock fa-2x text-muted mb-3"></i>
                        <h5 class="mb-2">{{ __('dashboard.restricted_title') }}</h5>
                        <p class="text-muted mb-0">{{ __('dashboard.restricted_text') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="erp-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="fw-bold mb-1">{{ __('dashboard.welcome_back') }}, {{ Auth::user()->name ?? 'Admin' }} 👋</h4>
                <p class="text-muted mb-0">{{ __('dashboard.welcome_subtitle') }}</p>
            </div>
        </div>

        @include('admin.dashboard.partials.filters')

        @include('admin.dashboard.partials.kpi-cards')

        @include('admin.dashboard.partials.overview-widgets')

        @include('admin.dashboard.partials.sales-charts')

        <div class="row">
            <div class="col-lg-5 mb-4">
                @include('admin.dashboard.partials.top-products-panel')
            </div>
            <div class="col-lg-7 mb-4">
                @include('admin.dashboard.partials.recent-activity')
            </div>
        </div>

        @if (isset($purchases))
            @include('admin.dashboard.partials.purchase-charts')
        @endif

        @if (isset($comparison))
            @include('admin.dashboard.partials.sales-purchase-comparison')
        @endif

        @if (isset($finance))
            @include('admin.dashboard.partials.finance-summary')
        @endif

        @if (isset($parties))
            @include('admin.dashboard.partials.parties-summary')
        @endif

        @if (Auth::user()->business_id && isset($subscription))
            <div class="row">
                @include('admin.dashboard.partials.subscription-widget', ['subscription' => $subscription, 'display_status' => $display_status])
            </div>
        @endif

        @if ($scope['tier'] === 1)
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">{{ __('dashboard.quick_access') }}</h5>
                </div>
                <div class="card-body">
                    <div class="row g-3">
                        @canAccess('pos.access')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('order.index') }}" class="erp-quick-action w-100"><i class="fa fa-shopping-cart"></i> {{ __('dashboard.qa_orders') }}</a>
                        </div>
                        @endcanAccess
                        @canAccess('purchase.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('purchase.index') }}" class="erp-quick-action w-100"><i class="fa fa-truck"></i> {{ __('dashboard.qa_purchases') }}</a>
                        </div>
                        @endcanAccess
                        @canAccess('user.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('users.index') }}" class="erp-quick-action w-100"><i class="fa fa-users"></i> {{ __('dashboard.qa_customers') }}</a>
                        </div>
                        @endcanAccess
                        @canAccess('supplier.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('supplier.index') }}" class="erp-quick-action w-100"><i class="fa fa-industry"></i> {{ __('dashboard.qa_suppliers') }}</a>
                        </div>
                        @endcanAccess
                        @canAccess('product.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('product.index') }}" class="erp-quick-action w-100"><i class="fa fa-box"></i> {{ __('dashboard.qa_products') }}</a>
                        </div>
                        @endcanAccess
                        @canAccess('stock.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ url('admin/product-variation-stock') }}" class="erp-quick-action w-100"><i class="fa fa-warehouse"></i> {{ __('dashboard.qa_inventory') }}</a>
                        </div>
                        @endcanAccess
                        @canAccess('transfer-note.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('transfer-note.index') }}" class="erp-quick-action w-100"><i class="fa fa-exchange-alt"></i> {{ __('dashboard.qa_transfers') }}</a>
                        </div>
                        @endcanAccess
                        @canAccess('account.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ url('admin/account') }}" class="erp-quick-action w-100"><i class="fa fa-sitemap"></i> {{ __('dashboard.qa_accounts') }}</a>
                        </div>
                        @endcanAccess
                        @canAccess('journal-entry.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('journal-entry.index') }}" class="erp-quick-action w-100"><i class="fa fa-book"></i> {{ __('dashboard.qa_ledgers') }}</a>
                        </div>
                        @endcanAccess
                        @canAccess('supplier-payment.view')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('supplier-payment.index') }}" class="erp-quick-action w-100"><i class="fa fa-money-check-alt"></i> {{ __('dashboard.qa_payments') }}</a>
                        </div>
                        @endcanAccess
                        @canAccess('reports.balance-sheet.view', 'accounting')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ url('admin/reports/balance-sheet') }}" class="erp-quick-action w-100"><i class="fa fa-chart-bar"></i> {{ __('dashboard.qa_reports') }}</a>
                        </div>
                        @endcanAccess
                        @canAccess('my-subscription.manage')
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('my-subscription.index') }}" class="erp-quick-action w-100"><i class="fa fa-crown"></i> {{ __('dashboard.qa_subscription') }}</a>
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
