@extends('layouts.app')

@section('css')
<style>
    .dashboard-kpi-card {
        transition: transform 0.15s ease, box-shadow 0.15s ease;
    }
    .dashboard-kpi-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 0.5rem 1rem rgba(67, 89, 113, 0.15);
    }
</style>
@endsection

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
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <h4 class="fw-bold mb-0">Business Dashboard</h4>
        </div>

        @include('admin.dashboard.partials.filters')

        @include('admin.dashboard.partials.kpi-cards')

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
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('order.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-shopping-cart me-1"></i> Orders</a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('purchase.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-truck me-1"></i> Purchases</a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('users.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-users me-1"></i> Customers</a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('supplier.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-industry me-1"></i> Suppliers</a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('product.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-box me-1"></i> Products</a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ url('admin/product-variation-stock') }}" class="btn btn-outline-primary w-100"><i class="fa fa-warehouse me-1"></i> Inventory</a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('transfer-note.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-exchange-alt me-1"></i> Transfers</a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ url('admin/account') }}" class="btn btn-outline-primary w-100"><i class="fa fa-sitemap me-1"></i> Accounts</a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('journal-entry.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-book me-1"></i> Ledgers</a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('supplier-payment.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-money-check-alt me-1"></i> Payments</a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ url('admin/reports/balance-sheet') }}" class="btn btn-outline-primary w-100"><i class="fa fa-chart-bar me-1"></i> Reports</a>
                        </div>
                        <div class="col-6 col-md-3 col-lg-2">
                            <a href="{{ route('my-subscription.index') }}" class="btn btn-outline-primary w-100"><i class="fa fa-crown me-1"></i> Subscription</a>
                        </div>
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
