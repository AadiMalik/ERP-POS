@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center py-3 mb-4">
            <h4 class="fw-bold mb-0">{{ __('subscriptions.title') }}</h4>
            <div>
                <a href="{{ route('subscription-renewal-requests.index') }}" class="btn btn-outline-primary">
                    <i class="fa fa-clock"></i> {{ __('subscriptions.renewal_requests') }}
                </a>
                <a href="{{ route('subscription-invoices.index') }}" class="btn btn-outline-primary">
                    <i class="fa fa-file-text"></i> {{ __('subscriptions.invoices') }}
                </a>
                <a href="{{ route('subscription-settings.edit') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-cog"></i> {{ __('subscriptions.settings') }}
                </a>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100"><div class="card-body">
                    <span class="text-muted d-block">{{ __('subscriptions.total_businesses') }}</span>
                    <h4 class="mb-0">{{ $metrics['total_businesses'] }}</h4>
                </div></div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100 border-success"><div class="card-body">
                    <span class="text-muted d-block">{{ __('subscriptions.active') }}</span>
                    <h4 class="mb-0 text-success">{{ $metrics['active'] }}</h4>
                </div></div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100 border-info"><div class="card-body">
                    <span class="text-muted d-block">{{ __('subscriptions.trial') }}</span>
                    <h4 class="mb-0 text-info">{{ $metrics['trial'] }}</h4>
                </div></div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100 border-warning"><div class="card-body">
                    <span class="text-muted d-block">{{ __('subscriptions.expiring_soon') }}</span>
                    <h4 class="mb-0 text-warning">{{ $metrics['expiring_soon'] }}</h4>
                </div></div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100 border-warning"><div class="card-body">
                    <span class="text-muted d-block">{{ __('subscriptions.payment_pending') }}</span>
                    <h4 class="mb-0 text-warning">{{ $metrics['payment_pending'] }}</h4>
                </div></div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100 border-danger"><div class="card-body">
                    <span class="text-muted d-block">{{ __('subscriptions.expired') }}</span>
                    <h4 class="mb-0 text-danger">{{ $metrics['expired'] }}</h4>
                </div></div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100"><div class="card-body">
                    <span class="text-muted d-block">{{ __('subscriptions.grace_period') }}</span>
                    <h4 class="mb-0">{{ $metrics['grace_period'] }}</h4>
                </div></div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100"><div class="card-body">
                    <span class="text-muted d-block">{{ __('subscriptions.suspended') }}</span>
                    <h4 class="mb-0">{{ $metrics['suspended'] }}</h4>
                </div></div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100"><div class="card-body">
                    <span class="text-muted d-block">{{ __('subscriptions.cancelled') }}</span>
                    <h4 class="mb-0">{{ $metrics['cancelled'] }}</h4>
                </div></div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100 border-primary"><div class="card-body">
                    <span class="text-muted d-block">{{ __('subscriptions.pending_approvals') }}</span>
                    <h4 class="mb-0 text-primary">{{ $metrics['pending_approvals'] }}</h4>
                </div></div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100"><div class="card-body">
                    <span class="text-muted d-block">Upcoming Renewals (30d)</span>
                    <h4 class="mb-0">{{ $metrics['upcoming_renewals'] }}</h4>
                </div></div>
            </div>
            <div class="col-6 col-md-3 col-xl-2">
                <div class="card h-100"><div class="card-body">
                    <span class="text-muted d-block">Pending Payments</span>
                    <h4 class="mb-0">{{ $metrics['pending_payments_count'] }} ({{ currency($metrics['pending_payments_amount']) }})</h4>
                </div></div>
            </div>
        </div>

        <div class="row g-4 mb-4">
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-body">
                        <span class="text-muted d-block">This Month's Revenue</span>
                        <h3 class="mb-0 text-success">{{ currency($metrics['current_month_revenue']) }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card h-100">
                    <div class="card-header bg-light"><h6 class="mb-0">Renewal &amp; Revenue Trend (6 months)</h6></div>
                    <div class="card-body">
                        <div id="trendChart"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <h5 class="mb-0">Businesses</h5>
                <div class="d-flex gap-2">
                    <select id="filter_package" class="form-select form-select-sm" style="width:180px">
                        <option value="">All Packages</option>
                        @foreach ($packages as $item)
                            <option value="{{ $item->package_id }}">{{ $item->name }}</option>
                        @endforeach
                    </select>
                    <select id="filter_status" class="form-select form-select-sm" style="width:180px">
                        <option value="">All Statuses</option>
                        <option value="trial">Trial</option>
                        <option value="active">Active</option>
                        <option value="expiring_soon">{{ __('subscriptions.expiring_soon') }}</option>
                        <option value="payment_pending">{{ __('subscriptions.payment_pending') }}</option>
                        <option value="grace_period">{{ __('subscriptions.grace_period') }}</option>
                        <option value="suspended">Suspended</option>
                        <option value="cancelled">Cancelled</option>
                        <option value="expired">Expired</option>
                    </select>
                </div>
            </div>
            <div class="table-responsive p-4">
                <table id="subscriptions_table" class="table datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>{{ __('common.business') }}</th>
                            <th>Package</th>
                            <th>Sub. Start</th>
                            <th>Sub. End</th>
                            <th>Rem. Days</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @if (session('success'))
        <script>successMessage("{{ session('success') }}");</script>
    @endif
    @if (session('error'))
        <script>errorMessage("{{ session('error') }}");</script>
    @endif

    @include('admin.partials.datatable', [
        'columns' => "
        {data:'code',name:'code'},
        {data:'name',name:'name'},
        {data:'package',name:'package'},
        {data:'subscription_start',name:'subscription_start'},
        {data:'subscription_end',name:'subscription_end'},
        {data:'remaining_days',name:'remaining_days', searchable:false, orderable:false},
        {data:'display_status',name:'display_status', searchable:false, orderable:false},
        {data:'action',name:'action',sortable:false}",
        'route' => 'subscriptions/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'subscriptions_table',
        'variable' => 'subscriptions_table',
        'params' => "package_id: $('#filter_package').val(), status_filter: $('#filter_status').val()",
    ])

    <script>
        $('#filter_package, #filter_status').on('change', function() {
            subscriptions_table.destroy();
            initDataTablesubscriptions_table();
        });

        var trendChartEl = document.querySelector('#trendChart');
        if (trendChartEl && typeof ApexCharts !== 'undefined') {
            var trendChart = new ApexCharts(trendChartEl, {
                chart: { type: 'line', height: 250, toolbar: { show: false } },
                series: [
                    { name: 'Renewals', type: 'column', data: @json($trend['renewals']) },
                    { name: 'Revenue', type: 'line', data: @json($trend['revenue']) },
                ],
                xaxis: { categories: @json($trend['labels']) },
                stroke: { width: [0, 3] },
            });
            trendChart.render();
        }
    </script>
@endsection
