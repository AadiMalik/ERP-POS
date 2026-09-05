<div class="row">
    <div class="col-lg-8 mb-4">
        <div class="card h-100">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">{{ __('analytics.chart_sales_trend') }}</h5>
            </div>
            <div class="card-body">
                <div id="analyticsSalesTrendChart"></div>
                <p class="text-muted mb-0 d-none" id="analyticsSalesTrendEmpty">{{ __('analytics.no_data') }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('analytics.chart_customer_segments') }}</h5></div>
            <div class="card-body">
                <div id="analyticsSegmentsChart"></div>
                <p class="text-muted mb-0 d-none" id="analyticsSegmentsEmpty">{{ __('analytics.no_data') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('analytics.chart_top_products') }}</h5></div>
            <div class="card-body">
                <div id="analyticsTopProductsChart"></div>
                <p class="text-muted mb-0 d-none" id="analyticsTopProductsEmpty">{{ __('analytics.no_data') }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('analytics.chart_top_customers') }}</h5></div>
            <div class="card-body">
                <div id="analyticsTopCustomersChart"></div>
                <p class="text-muted mb-0 d-none" id="analyticsTopCustomersEmpty">{{ __('analytics.no_data') }}</p>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('analytics.chart_order_source') }}</h5></div>
            <div class="card-body">
                <div id="analyticsOrderSourceChart"></div>
                <p class="text-muted mb-0 d-none" id="analyticsOrderSourceEmpty">{{ __('analytics.no_data') }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('analytics.chart_payment_method') }}</h5></div>
            <div class="card-body">
                <div id="analyticsPaymentMethodChart"></div>
                <p class="text-muted mb-0 d-none" id="analyticsPaymentMethodEmpty">{{ __('analytics.no_data') }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-4 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('analytics.chart_branch') }}</h5></div>
            <div class="card-body">
                <div id="analyticsBranchChart"></div>
                <p class="text-muted mb-0 d-none" id="analyticsBranchEmpty">{{ __('analytics.no_data') }}</p>
            </div>
        </div>
    </div>
</div>

@if (!empty($scope['is_finance']))
<div class="row" id="analyticsFinanceRow">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header"><h5 class="mb-0">{{ __('analytics.chart_finance') }}</h5></div>
            <div class="card-body" id="analyticsFinanceBody">
                <p class="text-muted mb-0">{{ __('analytics.loading') }}</p>
            </div>
        </div>
    </div>
</div>
@endif

<div class="row">
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('analytics.chart_inventory') }}</h5></div>
            <div class="card-body" id="analyticsInventoryBody">
                <p class="text-muted mb-0">{{ __('analytics.loading') }}</p>
            </div>
        </div>
    </div>
    <div class="col-lg-6 mb-4">
        <div class="card h-100">
            <div class="card-header"><h5 class="mb-0">{{ __('analytics.chart_loyalty') }}</h5></div>
            <div class="card-body" id="analyticsLoyaltyBody">
                <p class="text-muted mb-0">{{ __('analytics.loading') }}</p>
            </div>
        </div>
    </div>
</div>
