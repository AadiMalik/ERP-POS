@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">

    @if ($restricted ?? false)
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-body text-center py-5">
                        <i class="fa fa-lock fa-2x text-muted mb-3"></i>
                        <h5 class="mb-2">{{ __('analytics.restricted_title') }}</h5>
                        <p class="text-muted mb-0">{{ __('analytics.restricted_text') }}</p>
                    </div>
                </div>
            </div>
        </div>
    @else
        <div class="erp-page-header d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
            <div>
                <h4 class="fw-bold mb-1">{{ __('analytics.title') }}</h4>
                <p class="text-muted mb-0">{{ __('analytics.subtitle') }}</p>
            </div>
        </div>

        @include('admin.analytics.partials.filters')
        @include('admin.analytics.partials.kpi-cards')
        @include('admin.analytics.partials.charts')
        @include('admin.analytics.partials.drilldown-tables')
    @endif
</div>
@endsection

@section('js')
@unless ($restricted ?? false)
<script>
    window.AnalyticsConfig = {
        dataUrl: @json(url('admin/analytics/data')),
        tableUrl: @json(url('admin/analytics/table')),
        exportUrl: @json(url('admin/analytics/export')),
        currencySymbol: @json(session('accounting_setting.currency_symbol', 'Rs')),
        isFinance: @json(!empty($scope['is_finance'])),
        labels: {
            estimated: @json(__('analytics.estimated')),
            noData: @json(__('analytics.no_data')),
            loading: @json(__('analytics.loading')),
            deltaVsPrevious: @json(__('analytics.delta_vs_previous')),
            segmentNew: @json(__('analytics.segment_new')),
            segmentReturning: @json(__('analytics.segment_returning')),
            segmentWalkin: @json(__('analytics.segment_walkin')),
            salesTrend: @json(__('analytics.chart_sales_trend')),
            purchasesTrend: @json(__('analytics.chart_purchases_trend')),
            previous: @json(__('analytics.delta_vs_previous')),
        }
    };
</script>
<script src="{{ asset('public/assets/js/admin/analytics.js') }}"></script>
@endunless
@endsection
