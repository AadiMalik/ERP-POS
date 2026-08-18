@php
    $scope = $scope ?? [];
@endphp
<div class="card mb-4">
    <div class="card-header d-flex align-items-center justify-content-between flex-wrap gap-2">
        <div class="d-flex align-items-center gap-2">
            <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                <i class="fa fa-filter"></i>
                Filters
            </button>
            <span class="badge bg-label-primary">
                <i class="fa fa-calendar-alt me-1"></i>
                {{ $scope['start_date']->format('d M Y') }} - {{ $scope['end_date']->format('d M Y') }}
            </span>
            @if ($scope['can_select_branch'] ?? false)
                <span class="badge bg-label-info">
                    <i class="fa fa-code-branch me-1"></i>
                    {{ optional($scope['branch_options']->firstWhere('branch_id', $scope['effective_branch_id']))->name ?? 'All Branches' }}
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
        <form id="dashboardFilterForm" method="GET" action="{{ route('home') }}">
            <div class="row g-3 align-items-end">
                @if ($scope['can_use_date_filter'] ?? true)
                    <div class="col-md-3">
                        <label class="form-label">Date Range</label>
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
                @else
                    <div class="col-md-3">
                        <label class="form-label d-block">Date Range</label>
                        <span class="badge bg-label-warning">Today only ({{ $scope['start_date']->format('d M Y') }})</span>
                    </div>
                @endif

                @if ($scope['can_select_branch'] ?? false)
                    <div class="col-md-3">
                        <label class="form-label">Branch</label>
                        <select id="branch_id" name="branch_id" class="form-select">
                            <option value="">-- All Branches --</option>
                            @foreach ($scope['branch_options'] as $branch)
                                <option value="{{ $branch->branch_id }}" @selected($scope['effective_branch_id'] === $branch->branch_id)>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (!empty($filter_options['order_types']) && $filter_options['order_types']->isNotEmpty())
                    <div class="col-md-2">
                        <label class="form-label">Order Type</label>
                        <select id="order_type_id" name="order_type_id" class="form-select">
                            <option value="">-- All --</option>
                            @foreach ($filter_options['order_types'] as $type)
                                <option value="{{ $type->order_type_id }}" @selected(($scope['order_type_id'] ?? null) === $type->order_type_id)>{{ $type->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (!empty($filter_options['order_sources']) && $filter_options['order_sources']->isNotEmpty())
                    <div class="col-md-2">
                        <label class="form-label">Order Source</label>
                        <select id="order_source_id" name="order_source_id" class="form-select">
                            <option value="">-- All --</option>
                            @foreach ($filter_options['order_sources'] as $source)
                                <option value="{{ $source->order_source_id }}" @selected(($scope['order_source_id'] ?? null) === $source->order_source_id)>{{ $source->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                @if (!empty($filter_options['payment_methods']) && $filter_options['payment_methods']->isNotEmpty())
                    <div class="col-md-2">
                        <label class="form-label">Payment Method</label>
                        <select id="payment_method_id" name="payment_method_id" class="form-select">
                            <option value="">-- All --</option>
                            @foreach ($filter_options['payment_methods'] as $method)
                                <option value="{{ $method->payment_method_id }}" @selected(($scope['payment_method_id'] ?? null) === $method->payment_method_id)>{{ $method->name }}</option>
                            @endforeach
                        </select>
                    </div>
                @endif

                <div class="col-md-2">
                    <a href="{{ route('home') }}" class="btn btn-outline-secondary w-100">
                        <i class="fa fa-rotate-left"></i>
                        Reset
                    </a>
                </div>
            </div>
        </form>
    </div>
</div>
