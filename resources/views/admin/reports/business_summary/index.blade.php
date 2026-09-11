@php
    use App\Enums\RoleNames;
    $meta = $result['meta'] ?? [];
@endphp
@extends('layouts.app')

@section('css')
    <style>
        .bs-toolbar {
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            background: var(--bs-card-bg, #fff);
            padding: 1rem 1.25rem;
        }
        .bs-meta-chip {
            display: inline-flex;
            align-items: center;
            gap: .35rem;
            background: rgba(var(--bs-primary-rgb), .08);
            color: var(--bs-body-color);
            border-radius: 999px;
            padding: .2rem .7rem;
            font-size: .78rem;
        }
        .bs-jump {
            position: sticky;
            top: 4.25rem;
            z-index: 8;
            background: var(--bs-body-bg);
            padding: .45rem 0 .7rem;
            margin-bottom: .5rem;
        }
        .bs-jump .nav-link {
            font-size: .8rem;
            font-weight: 600;
            padding: .35rem .7rem;
            white-space: nowrap;
        }
        .bs-panel {
            border: 1px solid var(--bs-border-color);
            border-radius: 12px;
            background: var(--bs-card-bg, #fff);
            overflow: hidden;
        }
        .bs-panel__head {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: .75rem;
            flex-wrap: wrap;
            padding: .9rem 1.15rem;
            border-bottom: 1px solid var(--bs-border-color);
            border-inline-start: 4px solid var(--bs-primary);
        }
        .bs-panel__icon {
            width: 36px;
            height: 36px;
            border-radius: 10px;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex-shrink: 0;
        }
        .bs-kpi-wrap { padding: 1rem 1.15rem 1.15rem; }
        .bs-chart-block {
            border: 1px solid var(--bs-border-color);
            border-radius: 10px;
            height: 100%;
        }
        .bs-chart-block .card-header {
            font-size: .82rem;
            font-weight: 600;
            background: transparent;
            border-bottom-color: var(--bs-border-color);
        }
        .bs-faq .accordion-item {
            border: 1px solid var(--bs-border-color);
            border-radius: 12px !important;
            overflow: hidden;
        }
        .bs-faq .accordion-button {
            font-weight: 700;
            box-shadow: none;
        }
        .bs-faq .accordion-button:focus { box-shadow: none; }
        .bs-faq-item--critical .accordion-button,
        .bs-faq-item--critical .accordion-button:not(.collapsed) {
            background: #f8d7da;
            color: #842029;
        }
        .bs-faq-item--important .accordion-button,
        .bs-faq-item--important .accordion-button:not(.collapsed) {
            background: #fff3cd;
            color: #664d03;
        }
        .bs-faq-item--informational .accordion-button,
        .bs-faq-item--informational .accordion-button:not(.collapsed) {
            background: #cff4fc;
            color: #055160;
        }
        .bs-faq-item--good .accordion-button,
        .bs-faq-item--good .accordion-button:not(.collapsed) {
            background: #d1e7dd;
            color: #0a3622;
        }
        .bs-faq-item--excellent .accordion-button,
        .bs-faq-item--excellent .accordion-button:not(.collapsed) {
            background: #e0cffc;
            color: #432874;
        }
        .bs-faq-area {
            font-size: .72rem;
            font-weight: 700;
            letter-spacing: .05em;
            text-transform: uppercase;
            color: var(--bs-secondary-color, #697a8d);
            margin: .35rem 0 .4rem;
        }
        .bs-faq-list {
            list-style: none;
            padding: 0;
            margin: 0 0 1rem;
        }
        .bs-faq-list > li {
            display: flex;
            align-items: flex-start;
            gap: .7rem;
            padding: .55rem 0;
            border-bottom: 1px solid var(--bs-border-color);
        }
        .bs-faq-list > li:last-child { border-bottom: 0; }
        .bs-dot {
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background: #111;
            margin-top: .45rem;
            flex-shrink: 0;
        }
        .bs-faq-copy a { font-weight: 600; }
        .bs-faq-sub {
            list-style: disc;
            margin: .25rem 0 0 1.1rem;
            padding: 0;
            font-size: .8rem;
            color: var(--bs-secondary-color, #697a8d);
        }
        body[data-content-bg="dark"] .bs-faq-item--critical .accordion-button,
        body[data-content-bg="dark"] .bs-faq-item--critical .accordion-button:not(.collapsed) {
            background: rgba(var(--bs-danger-rgb), .28);
            color: var(--bs-body-color);
        }
        body[data-content-bg="dark"] .bs-faq-item--important .accordion-button,
        body[data-content-bg="dark"] .bs-faq-item--important .accordion-button:not(.collapsed) {
            background: rgba(var(--bs-warning-rgb), .28);
            color: var(--bs-body-color);
        }
        body[data-content-bg="dark"] .bs-faq-item--informational .accordion-button,
        body[data-content-bg="dark"] .bs-faq-item--informational .accordion-button:not(.collapsed) {
            background: rgba(var(--bs-info-rgb), .28);
            color: var(--bs-body-color);
        }
        body[data-content-bg="dark"] .bs-faq-item--good .accordion-button,
        body[data-content-bg="dark"] .bs-faq-item--good .accordion-button:not(.collapsed) {
            background: rgba(var(--bs-success-rgb), .28);
            color: var(--bs-body-color);
        }
        body[data-content-bg="dark"] .bs-faq-item--excellent .accordion-button,
        body[data-content-bg="dark"] .bs-faq-item--excellent .accordion-button:not(.collapsed) {
            background: rgba(var(--bs-primary-rgb), .28);
            color: var(--bs-body-color);
        }
        body[data-content-bg="dark"] .bs-dot { background: #fff; }
        @media (max-width: 767.98px) {
            .bs-jump { top: 3.75rem; }
        }
        @media print {
            .bs-jump, #bsFilterForm, .bs-toolbar .btn { display: none !important; }
            .bs-faq .accordion-collapse { display: block !important; height: auto !important; }
        }
    </style>
@endsection

@section('content')
    @php
        $charts = $result['charts'] ?? [];
        $hasSalesCharts = !empty($charts['sales_by_branch']['labels']) || !empty($charts['sales_by_channel']['labels']) || !empty($charts['order_types']['labels']);
        $hasDiscountChart = !empty($charts['discount_voucher']['series']) && array_sum($charts['discount_voucher']['series']) > 0;
        $hasAttendanceChart = !empty($charts['attendance']['labels']);
        $hasCharts = $hasSalesCharts || $hasDiscountChart || $hasAttendanceChart;
        $kpiColors = [
            'sales' => 'primary',
            'orders' => 'info',
            'gross_profit' => 'success',
            'expenses' => 'danger',
            'receivables' => 'warning',
            'purchases' => 'warning',
            'stock_value' => 'success',
            'cash_bank' => 'primary',
        ];
        $jumpSections = ['critical' => 'danger', 'important' => 'warning', 'informational' => 'info', 'good' => 'success', 'excellent' => 'primary'];
    @endphp
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="bs-toolbar d-flex justify-content-between align-items-start flex-wrap gap-3 mb-3">
            <div>
                <h4 class="fw-bold mb-2">{{ $meta['title'] ?? __('reports.business_summary_report') }}</h4>
                <div class="d-flex flex-wrap gap-2">
                    @if (!empty($meta['business_name']))
                        <span class="bs-meta-chip"><i class="fa fa-building"></i> {{ $meta['business_name'] }}</span>
                    @endif
                    <span class="bs-meta-chip">
                        <i class="fa fa-calendar"></i>
                        {{ localDate($meta['start_date'] ?? null) }} — {{ localDate($meta['end_date'] ?? null) }}
                    </span>
                    @if (!empty($meta['timezone']))
                        <span class="bs-meta-chip"><i class="fa fa-clock"></i> {{ $meta['timezone'] }}</span>
                    @endif
                    @if (!empty($meta['generated_at']))
                        <span class="bs-meta-chip"><i class="fa fa-rotate"></i> {{ $meta['generated_at'] }}</span>
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" id="btn_refresh" class="btn btn-outline-primary">
                    <i class="fa fa-rotate"></i> {{ __('common.refresh') }}
                </button>
                @canAccess('reports.business-summary.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                @endcanAccess
                @canAccess('reports.business-summary.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                @endcanAccess
                @canAccess('reports.business-summary.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                @endcanAccess
                @canAccess('reports.business-summary.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> {{ __('common.csv') }}
                    </a>
                @endcanAccess
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-body">
                <form id="bsFilterForm" method="GET" action="{{ url('/admin/reports/business-summary') }}" class="row g-3">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.business') }}</label>
                            <select name="business_id" id="business_id" class="form-select">
                                <option value="">{{ __('common.all_businesses') }}</option>
                                @foreach ($business as $item)
                                    <option value="{{ $item->business_id }}" {{ request('business_id') == $item->business_id ? 'selected' : '' }}>
                                        {{ $item->code ?? '' }} {{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.branch') }}</label>
                        <select name="branch_id" id="branch_id" class="form-select">
                            <option value="">{{ __('common.all_branches') }}</option>
                            @foreach ($branches as $item)
                                <option value="{{ $item->branch_id }}" {{ request('branch_id') == $item->branch_id ? 'selected' : '' }}>
                                    {{ $item->name ?? '' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">{{ __('common.period') }}</label>
                        @include('admin.partials.date_filter')
                    </div>
                    <input type="hidden" name="start_date" id="bs_start_date" value="{{ request('start_date', $meta['start_date'] ?? '') }}">
                    <input type="hidden" name="end_date" id="bs_end_date" value="{{ request('end_date', $meta['end_date'] ?? '') }}">
                    <div class="col-md-3 d-flex align-items-end gap-2">
                        <button type="submit" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                        <a href="{{ url('/admin/reports/business-summary') }}" class="btn btn-outline-secondary">{{ __('common.reset') }}</a>
                    </div>
                </form>
            </div>
        </div>

        <nav class="bs-jump" aria-label="{{ __('reports.business_summary.contents') }}">
            <div class="d-flex align-items-center gap-2 flex-wrap">
                <span class="text-muted small fw-semibold">{{ __('reports.business_summary.contents') }}</span>
                <ul class="nav nav-pills flex-nowrap overflow-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#bs-section-overview">{{ __('reports.business_summary.jump_overview') }}</a>
                    </li>
                    @if ($hasCharts)
                        <li class="nav-item">
                            <a class="nav-link" href="#bs-section-charts">{{ __('reports.business_summary.breakdown') }}</a>
                        </li>
                    @endif
                    @foreach ($jumpSections as $section => $tone)
                        <li class="nav-item">
                            <a class="nav-link" href="#bs-section-{{ $section }}">
                                {{ __('reports.business_summary.section_' . $section) }}
                                <span class="badge bg-label-{{ $tone }} ms-1">{{ count($result[$section] ?? []) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </div>
        </nav>

        <section class="bs-panel bs-panel--overview mb-4" id="bs-section-overview">
            <div class="bs-panel__head">
                <div class="d-flex align-items-center gap-2">
                    <span class="bs-panel__icon bg-label-primary"><i class="fa fa-gauge-high"></i></span>
                    <div>
                        <h5 class="mb-0">{{ __('reports.business_summary.executive_overview') }}</h5>
                        <small class="text-muted">{{ localDate($meta['start_date'] ?? null) }} — {{ localDate($meta['end_date'] ?? null) }}</small>
                    </div>
                </div>
            </div>
            <div class="bs-kpi-wrap">
                <div class="row g-3">
                    @forelse ($result['executive_overview'] ?? [] as $card)
                        @php $color = $kpiColors[$card['id'] ?? ''] ?? 'primary'; @endphp
                        <div class="col-sm-6 col-xl-3">
                            <div class="card h-100 erp-kpi-card" style="--erp-kpi-color: var(--bs-{{ $color }}); --erp-kpi-color-rgb: var(--bs-{{ $color }}-rgb);">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-2">
                                        <div>
                                            <span class="erp-kpi-label">{{ $card['label'] }}</span>
                                            <h4 class="erp-kpi-value mb-0">{{ $card['value'] }}</h4>
                                            @if (!empty($card['delta']))
                                                <small class="text-muted">{{ $card['delta'] }}</small>
                                            @endif
                                        </div>
                                        <div class="erp-kpi-icon"><i class="fa {{ $card['icon'] }}"></i></div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12">
                            <div class="text-muted">{{ __('reports.business_summary.no_kpis') }}</div>
                        </div>
                    @endforelse
                </div>
            </div>
        </section>

        @if ($hasCharts)
            <section class="bs-panel bs-panel--charts mb-4" id="bs-section-charts">
                <div class="bs-panel__head">
                    <div class="d-flex align-items-center gap-2">
                        <span class="bs-panel__icon bg-label-info"><i class="fa fa-chart-pie"></i></span>
                        <div>
                            <h5 class="mb-0">{{ __('reports.business_summary.breakdown') }}</h5>
                            <small class="text-muted">{{ __('reports.business_summary.area_sales') }}</small>
                        </div>
                    </div>
                </div>
                <div class="p-3">
                    <div class="row g-3">
                        @if (!empty($charts['sales_by_branch']['labels']))
                            <div class="col-lg-6">
                                <div class="card bs-chart-block">
                                    <div class="card-header">{{ __('reports.business_summary.chart_sales_by_branch') }}</div>
                                    <div class="card-body"><div id="bsChartBranch"></div></div>
                                </div>
                            </div>
                        @endif
                        @if (!empty($charts['sales_by_channel']['labels']))
                            <div class="col-lg-6">
                                <div class="card bs-chart-block">
                                    <div class="card-header">{{ __('reports.business_summary.chart_sales_by_channel') }}</div>
                                    <div class="card-body"><div id="bsChartChannel"></div></div>
                                </div>
                            </div>
                        @endif
                        @if (!empty($charts['order_types']['labels']))
                            <div class="col-lg-6">
                                <div class="card bs-chart-block">
                                    <div class="card-header">{{ __('reports.business_summary.chart_order_types') }}</div>
                                    <div class="card-body"><div id="bsChartTypes"></div></div>
                                </div>
                            </div>
                        @endif
                        @if ($hasDiscountChart)
                            <div class="col-lg-6">
                                <div class="card bs-chart-block">
                                    <div class="card-header">{{ __('reports.business_summary.chart_discounts') }}</div>
                                    <div class="card-body"><div id="bsChartDiscount"></div></div>
                                </div>
                            </div>
                        @endif
                        @if ($hasAttendanceChart)
                            <div class="col-lg-6">
                                <div class="card bs-chart-block">
                                    <div class="card-header">{{ __('reports.business_summary.chart_attendance') }}</div>
                                    <div class="card-body"><div id="bsChartAttendance"></div></div>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </section>
        @endif

        @include('admin.reports.business_summary.partials.sections', ['result' => $result])
    </div>
@endsection

@section('js')
    <script>
        const bsCharts = @json($result['charts'] ?? []);

        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                start_date: (typeof filterStartDate !== 'undefined' && filterStartDate) ? filterStartDate : $('#bs_start_date').val(),
                end_date: (typeof filterEndDate !== 'undefined' && filterEndDate) ? filterEndDate : $('#bs_end_date').val(),
            };
        }

        function buildReportUrl(path) {
            return url_local + path + '?' + $.param(currentReportParams());
        }

        function renderBsChart(el, type, payload) {
            if (!el || typeof ApexCharts === 'undefined' || !payload || !payload.labels || !payload.labels.length) {
                return;
            }
            new ApexCharts(el, {
                chart: { type: type, height: 280, toolbar: { show: false }, animations: { speed: 400 } },
                series: type === 'pie' || type === 'donut'
                    ? (payload.series || [])
                    : [{ name: '', data: payload.series || [] }],
                labels: payload.labels,
                xaxis: type === 'bar' ? { categories: payload.labels } : undefined,
                legend: { position: 'bottom' },
                dataLabels: { enabled: type !== 'bar' },
            }).render();
        }

        $(document).ready(function() {
            if ($.fn.select2) {
                $('#business_id').select2();
                $('#branch_id').select2();
            }
            if (typeof filterStartDate !== 'undefined') {
                filterStartDate = $('#bs_start_date').val() || filterStartDate;
                filterEndDate = $('#bs_end_date').val() || filterEndDate;
            }
            renderBsChart(document.querySelector('#bsChartBranch'), 'bar', bsCharts.sales_by_branch);
            renderBsChart(document.querySelector('#bsChartChannel'), 'donut', bsCharts.sales_by_channel);
            renderBsChart(document.querySelector('#bsChartTypes'), 'donut', bsCharts.order_types);
            renderBsChart(document.querySelector('#bsChartDiscount'), 'donut', bsCharts.discount_voucher);
            renderBsChart(document.querySelector('#bsChartAttendance'), 'donut', bsCharts.attendance);
        });

        $('#bsFilterForm').on('submit', function() {
            if (typeof filterStartDate !== 'undefined' && filterStartDate) {
                $('#bs_start_date').val(filterStartDate);
            }
            if (typeof filterEndDate !== 'undefined' && filterEndDate) {
                $('#bs_end_date').val(filterEndDate);
            }
        });

        $('#btn_refresh').on('click', function() {
            $('#bsFilterForm').trigger('submit');
        });
        $('#btn_print').on('click', function() {
            window.open(buildReportUrl('/admin/reports/business-summary/print'), '_blank');
        });
        $('#btn_pdf').on('click', function() {
            window.open(buildReportUrl('/admin/reports/business-summary/pdf'), '_blank');
        });
        $('#btn_excel').on('click', function() {
            window.location.href = buildReportUrl('/admin/reports/business-summary/export');
        });
        $('#btn_csv').on('click', function() {
            window.location.href = buildReportUrl('/admin/reports/business-summary/export-csv');
        });
    </script>
@endsection
