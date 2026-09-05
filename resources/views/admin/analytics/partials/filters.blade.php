@php
    $scope = $scope ?? [];
    $filter_options = $filter_options ?? [];
@endphp
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                <i class="fa fa-filter"></i>
                {{ __('analytics.filters') }}
            </button>
            <span class="badge bg-label-primary" id="analyticsDateBadge">
                <i class="fa fa-calendar-alt me-1"></i>
                {{ $scope['start_date']->format('d M Y') }} - {{ $scope['end_date']->format('d M Y') }}
            </span>
            @if ($scope['can_select_branch'] ?? false)
                <span class="badge bg-label-info" id="analyticsBranchBadge">
                    <i class="fa fa-code-branch me-1"></i>
                    {{ optional($scope['branch_options']->firstWhere('branch_id', $scope['effective_branch_id']))->name ?? __('analytics.all_branches') }}
                </span>
            @elseif (!empty($scope['user']->branch->name ?? null))
                <span class="badge bg-label-secondary">
                    <i class="fa fa-code-branch me-1"></i>
                    {{ $scope['user']->branch->name }}
                </span>
            @endif
        </div>
    </div>
    <div id="filterSection" class="card-body border-top" style="display:none;">
        <form id="analyticsFilterForm" method="GET" action="{{ route('analytics.index') }}">
            <div class="row g-3 align-items-end">
                @if ($scope['can_use_date_filter'] ?? true)
                    <div class="col-md-3">
                        <label class="form-label">{{ __('analytics.date_range') }}</label>
                        <select id="date_filter" name="date_filter" class="form-select">
                            <option value="today" @selected(($scope['date_filter'] ?? '') === 'today')>Today</option>
                            <option value="yesterday" @selected(($scope['date_filter'] ?? '') === 'yesterday')>Yesterday</option>
                            <option value="this_week" @selected(($scope['date_filter'] ?? '') === 'this_week')>This Week</option>
                            <option value="last_week" @selected(($scope['date_filter'] ?? '') === 'last_week')>Last Week</option>
                            <option value="this_month" @selected(($scope['date_filter'] ?? 'this_month') === 'this_month')>This Month</option>
                            <option value="last_month" @selected(($scope['date_filter'] ?? '') === 'last_month')>Last Month</option>
                            <option value="last_3_months" @selected(($scope['date_filter'] ?? '') === 'last_3_months')>Last 3 Months</option>
                            <option value="last_6_months" @selected(($scope['date_filter'] ?? '') === 'last_6_months')>Last 6 Months</option>
                            <option value="this_year" @selected(($scope['date_filter'] ?? '') === 'this_year')>This Year</option>
                            <option value="this_financial_year" @selected(($scope['date_filter'] ?? '') === 'this_financial_year')>This Financial Year</option>
                            <option value="custom" @selected(($scope['date_filter'] ?? '') === 'custom')>Custom Range</option>
                        </select>
                        <input type="hidden" id="dashboard_start_date" name="start_date" value="{{ $scope['start_date']->format('Y-m-d') }}">
                        <input type="hidden" id="dashboard_end_date" name="end_date" value="{{ $scope['end_date']->format('Y-m-d') }}">
                    </div>
                @endif

                @if ($scope['can_select_branch'] ?? false)
                    <div class="col-md-2">
                        <label class="form-label">{{ __('analytics.branch') }}</label>
                        <select id="branch_id" name="branch_id" class="form-select">
                            <option value="">{{ __('analytics.all_branches') }}</option>
                            @foreach ($scope['branch_options'] as $branch)
                                <option value="{{ $branch->branch_id }}" @selected($scope['effective_branch_id'] === $branch->branch_id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (!empty($filter_options['order_types']) && $filter_options['order_types']->isNotEmpty())
                    <div class="col-md-2">
                        <label class="form-label">{{ __('analytics.order_type') }}</label>
                        <select name="order_type_id" id="order_type_id" class="form-select">
                            <option value="">{{ __('analytics.all') }}</option>
                            @foreach ($filter_options['order_types'] as $type)
                                <option value="{{ $type->order_type_id }}" @selected(($scope['order_type_id'] ?? null) === $type->order_type_id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (!empty($filter_options['order_sources']) && $filter_options['order_sources']->isNotEmpty())
                    <div class="col-md-2">
                        <label class="form-label">{{ __('analytics.order_source') }}</label>
                        <select name="order_source_id" id="order_source_id" class="form-select">
                            <option value="">{{ __('analytics.all') }}</option>
                            @foreach ($filter_options['order_sources'] as $source)
                                <option value="{{ $source->order_source_id }}" @selected(($scope['order_source_id'] ?? null) === $source->order_source_id)>{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (!empty($filter_options['payment_methods']) && $filter_options['payment_methods']->isNotEmpty())
                    <div class="col-md-2">
                        <label class="form-label">{{ __('analytics.payment_method') }}</label>
                        <select name="payment_method_id" id="payment_method_id" class="form-select">
                            <option value="">{{ __('analytics.all') }}</option>
                            @foreach ($filter_options['payment_methods'] as $method)
                                <option value="{{ $method->payment_method_id }}" @selected(($scope['payment_method_id'] ?? null) === $method->payment_method_id)>{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-2">
                    <label class="form-label">{{ __('analytics.product') }}</label>
                    <select name="product_id" id="product_id" class="form-select select2">
                        <option value="">{{ __('analytics.all') }}</option>
                        @foreach ($filter_options['products'] ?? [] as $product)
                            <option value="{{ $product->product_id }}" @selected(($scope['product_id'] ?? null) === $product->product_id)>{{ $product->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">{{ __('analytics.category') }}</label>
                    <select name="category_id" id="category_id" class="form-select select2">
                        <option value="">{{ __('analytics.all') }}</option>
                        @foreach ($filter_options['categories'] ?? [] as $category)
                            <option value="{{ $category->category_id }}" @selected(($scope['category_id'] ?? null) === $category->category_id)>{{ $category->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">{{ __('analytics.brand') }}</label>
                    <select name="brand_id" id="brand_id" class="form-select select2">
                        <option value="">{{ __('analytics.all') }}</option>
                        @foreach ($filter_options['brands'] ?? [] as $brand)
                            <option value="{{ $brand->brand_id }}" @selected(($scope['brand_id'] ?? null) === $brand->brand_id)>{{ $brand->name }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2">
                    <label class="form-label">{{ __('analytics.customer') }}</label>
                    <select name="customer_id" id="customer_id" class="form-select select2">
                        <option value="">{{ __('analytics.all') }}</option>
                        @foreach ($filter_options['customers'] ?? [] as $customer)
                            <option value="{{ $customer['user_id'] }}" @selected(($scope['customer_id'] ?? null) == $customer['user_id'])>{{ $customer['name'] }}</option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <div class="form-check mt-4">
                        <input class="form-check-input" type="checkbox" value="1" id="compare_previous_period" name="compare_previous_period" @checked(!empty($scope['compare_previous_period']))>
                        <label class="form-check-label" for="compare_previous_period">{{ __('analytics.compare_previous') }}</label>
                    </div>
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-check"></i> {{ __('analytics.apply') }}
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('analytics.index') }}" class="btn btn-outline-secondary w-100">
                        <i class="fa fa-rotate-left"></i> {{ __('analytics.reset') }}
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
