<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">
                    {{ __('analytics.chart_product_margin') }}
                    <span class="badge bg-label-warning ms-1">{{ __('analytics.estimated') }}</span>
                </h5>
                @canAccess('analytics.export')
                    <a href="#" class="btn btn-sm btn-outline-primary analytics-export" data-widget="product-margin">
                        <i class="fa fa-file-excel"></i> {{ __('analytics.export') }}
                    </a>
                @endcanAccess
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm" id="analyticsMarginTable">
                    <thead>
                        <tr>
                            <th>{{ __('analytics.col_product') }}</th>
                            <th>{{ __('analytics.col_qty') }}</th>
                            <th>{{ __('analytics.col_revenue') }}</th>
                            <th>{{ __('analytics.col_cogs') }}</th>
                            <th>{{ __('analytics.col_margin') }}</th>
                            <th>{{ __('analytics.col_margin_pct') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-12 mb-4">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">{{ __('analytics.chart_slow_moving') }}</h5>
                @canAccess('analytics.export')
                    <a href="#" class="btn btn-sm btn-outline-primary analytics-export" data-widget="slow-moving">
                        <i class="fa fa-file-excel"></i> {{ __('analytics.export') }}
                    </a>
                @endcanAccess
            </div>
            <div class="card-body table-responsive">
                <table class="table table-sm" id="analyticsSlowMovingTable">
                    <thead>
                        <tr>
                            <th>{{ __('analytics.col_product') }}</th>
                            <th>{{ __('analytics.col_warehouse') }}</th>
                            <th>{{ __('analytics.col_qty') }}</th>
                            <th>{{ __('analytics.col_days_idle') }}</th>
                            <th>{{ __('analytics.col_movement') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>
