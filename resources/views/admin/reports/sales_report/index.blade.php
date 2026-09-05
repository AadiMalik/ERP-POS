@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.sales_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.sales-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.sales-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.sales-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.sales-report.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> {{ __('common.csv') }}
                    </a>
                    @endcanAccess
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">{{ __('common.business') }}</label>
                                <select id="business_id" class="form-select">
                                    <option value="">{{ __('common.all_businesses') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.warehouse') }}</label>
                            <select id="warehouse_id" class="form-select">
                                <option value="">{{ __('common.all_warehouses') }}</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.period') }}</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 p-4 pb-0">
                    <div class="col-md-3">
                        <div class="alert alert-info mb-0">
                            <strong>Orders:</strong> <span id="order_count_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-secondary mb-0">
                            <strong>Order Total:</strong> <span id="order_total_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-secondary mb-0">
                            <strong>Posted Sales Revenue (Ledger):</strong> <span id="ledger_revenue_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert mb-0" id="reconciled_alert">
                            <strong>Reconciliation:</strong> <span id="reconciled_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="sales_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_order_no') }}</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('common.customer') }}</th>
                                <th>{{ __('reports.col_warehouse') }}</th>
                                <th class="text-end">{{ __('reports.col_subtotal') }}</th>
                                <th class="text-end">{{ __('reports.col_discount') }}</th>
                                <th class="text-end">Voucher</th>
                                <th class="text-end">{{ __('reports.col_tax') }}</th>
                                <th class="text-end">{{ __('common.total') }}</th>
                                <th class="text-end">{{ __('reports.col_paid') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'order_no',name:'order_no',sortable:false},
                        {data:'order_date',name:'order_date',sortable:false},
                        {data:'customer',name:'customer',sortable:false},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'subtotal',name:'subtotal',sortable:false,className:'text-end'},
                        {data:'discount_amount',name:'discount_amount',sortable:false,className:'text-end'},
                        {data:'voucher_discount_amount',name:'voucher_discount_amount',sortable:false,className:'text-end'},
                        {data:'tax_amount',name:'tax_amount',sortable:false,className:'text-end'},
                        {data:'total',name:'total',sortable:false,className:'text-end'},
                        {data:'paid_amount',name:'paid_amount',sortable:false,className:'text-end'}",
        'route' => 'sales-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'sales_report_table',
        'variable' => 'sales_report_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),warehouse_id:$('#warehouse_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                warehouse_id: $('#warehouse_id').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id').select2();
            $('#warehouse_id').select2();
            refreshReconciliation();
        });

        $('#search_btn').click(function() {
            initDataTablesales_report_table();
            refreshReconciliation();
        });

        function refreshReconciliation() {
            ajaxRequest({
                    url: url_local + '/admin/reports/sales-report/reconcile',
                    method: 'POST',
                    data: currentReportParams()
                })
                .then((response) => {
                    let data = response.Data;
                    $('#order_count_display').text(data.order_count);
                    $('#order_total_display').text(currency(data.order_total));
                    $('#ledger_revenue_display').text(currency(data.ledger_revenue));

                    let alertEl = $('#reconciled_alert');
                    if (data.reconciled) {
                        $('#reconciled_display').text('Matches (variance ' + currency(data.variance) + ')');
                        alertEl.removeClass('alert-danger').addClass('alert-success');
                    } else {
                        $('#reconciled_display').text('Variance: ' + currency(data.variance));
                        alertEl.removeClass('alert-success').addClass('alert-danger');
                    }
                })
                .catch(() => {});
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/sales-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/sales-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/sales-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/sales-report/export-csv');
        });
    </script>
@endsection
