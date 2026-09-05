@php
    use App\Enums\RoleNames;

    $cards = [
        ['label' => 'Total Employees', 'value' => number_format($stats['total_employees']), 'icon' => 'fa-users', 'color' => 'primary', 'route' => url('/admin/reports/employee-master-report')],
        ['label' => 'Active Employees', 'value' => number_format($stats['active_employees']), 'icon' => 'fa-user-check', 'color' => 'success', 'route' => url('/admin/reports/employee-status-report')],
        ['label' => 'On Leave', 'value' => number_format($stats['on_leave_employees']), 'icon' => 'fa-user-clock', 'color' => 'info', 'route' => url('/admin/reports/employee-status-report')],
        ['label' => 'New Joiners (This Month)', 'value' => number_format($stats['new_joiners']), 'icon' => 'fa-user-plus', 'color' => 'success', 'route' => url('/admin/reports/employee-joining-report')],
        ['label' => 'Resignations (This Month)', 'value' => number_format($stats['resignations_this_month']), 'icon' => 'fa-door-open', 'color' => 'warning', 'route' => url('/admin/reports/resignation-report')],
        ['label' => 'Terminations (This Month)', 'value' => number_format($stats['terminations_this_month']), 'icon' => 'fa-user-slash', 'color' => 'danger', 'route' => url('/admin/reports/termination-report')],
        ['label' => "Present Today", 'value' => number_format($stats['present_today']), 'icon' => 'fa-check-circle', 'color' => 'success', 'route' => url('/admin/reports/daily-attendance-report')],
        ['label' => 'Absent Today', 'value' => number_format($stats['absent_today']), 'icon' => 'fa-times-circle', 'color' => 'danger', 'route' => url('/admin/reports/absent-employees-report')],
        ['label' => 'Late Today', 'value' => number_format($stats['late_today']), 'icon' => 'fa-clock', 'color' => 'warning', 'route' => url('/admin/reports/late-attendance-report')],
        ['label' => 'On Leave Today', 'value' => number_format($stats['on_leave_today']), 'icon' => 'fa-umbrella-beach', 'color' => 'info', 'route' => url('/admin/reports/attendance-summary-report')],
        ['label' => 'Pending Leave Approvals', 'value' => number_format($stats['pending_leave_approvals']), 'icon' => 'fa-hourglass-half', 'color' => 'warning', 'route' => url('/admin/reports/pending-leave-approval-report')],
        ['label' => 'Latest Payroll', 'value' => $stats['latest_payroll_period'] ? ($stats['latest_payroll_period'] . ' (' . ucfirst($stats['latest_payroll_status']) . ')') : 'None', 'icon' => 'fa-money-check-alt', 'color' => 'primary', 'route' => url('/admin/reports/payroll-summary-report')],
        ['label' => 'Outstanding Advances', 'value' => currency($stats['outstanding_advances']), 'icon' => 'fa-hand-holding-usd', 'color' => 'danger', 'route' => url('/admin/reports/employee-advance-report')],
        ['label' => 'Pending Clearances', 'value' => number_format($stats['pending_clearances']), 'icon' => 'fa-clipboard-check', 'color' => 'warning', 'route' => url('/admin/reports/employee-clearance-report')],
        ['label' => 'Active Asset Allocations', 'value' => number_format($stats['active_asset_allocations']), 'icon' => 'fa-laptop', 'color' => 'info', 'route' => url('/admin/reports/asset-allocation-report')],
    ];
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 py-3 mb-1">
            <h4 class="fw-bold mb-0">{{ __('reports.hr_dashboard_report') }}</h4>
            @if (RoleNames::SUPERADMIN == getRoleName())
                <div style="min-width: 260px;">
                    <select id="business_id" class="form-select">
                        <option value="">{{ __('common.all_businesses') }}</option>
                        @foreach ($business as $item)
                            <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
            @endif
        </div>

        <div class="row">
            @foreach ($cards as $card)
                <div class="col-sm-6 col-lg-4 col-xl-3 mb-4">
                    <a href="{{ $card['route'] }}" class="text-decoration-none">
                        <div class="card h-100 dashboard-kpi-card">
                            <div class="card-body">
                                <div class="d-flex justify-content-between align-items-start">
                                    <div>
                                        <span class="text-muted d-block mb-1">{{ $card['label'] }}</span>
                                        <h5 class="mb-0 text-body">{{ $card['value'] }}</h5>
                                    </div>
                                    <div class="avatar flex-shrink-0">
                                        <span class="avatar-initial rounded bg-label-{{ $card['color'] }}">
                                            <i class="fa {{ $card['icon'] }}"></i>
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </a>
                </div>
            @endforeach
        </div>
    </div>
@endsection
@section('js')
    <script>
        $(document).ready(function() {
            $('#business_id').select2();
        });

        $('#business_id').on('change', function() {
            let params = $.param({ business_id: $(this).val() || '' });
            window.location.href = url_local + '/admin/reports/hr-dashboard-report?' + params;
        });
    </script>
@endsection
