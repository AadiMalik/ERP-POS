@php
    use App\Enums\RoleNames;
    use App\Enums\ComplimentaryStatus;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.complimentary_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.complimentary-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.complimentary-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.complimentary-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.complimentary-report.export-csv')
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
                            <label class="form-label">{{ __('common.branch') }}</label>
                            <select id="branch_id" class="form-select">
                                <option value="">{{ __('common.all_branches') }}</option>
                                @foreach ($branches as $item)
                                    <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
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
                            <label class="form-label">{{ __('common.customer') }}</label>
                            <select id="user_id" class="form-select">
                                <option value="">{{ __('common.all_customers') }}</option>
                                @foreach ($customers as $item)
                                    <option value="{{ $item->user_id }}">{{ $item->user->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('complimentary.status') }}</label>
                            <select id="complimentary_status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                @foreach (ComplimentaryStatus::getOptions() as $value => $label)
                                    @if ($value !== ComplimentaryStatus::NONE)
                                        <option value="{{ $value }}">{{ $label }}</option>
                                    @endif
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('complimentary.reason') }}</label>
                            <select id="complimentary_reason_id" class="form-select">
                                <option value="">{{ __('common.all') }}</option>
                                @foreach ($reasons as $item)
                                    <option value="{{ $item->complimentary_reason_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('complimentary.issued_by') }}</label>
                            <select id="issued_by_id" class="form-select">
                                <option value="">{{ __('common.all') }}</option>
                                @foreach ($users as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('orders.daily_order_id') }}</label>
                            <input type="text" id="daily_order_id" class="form-control">
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
                        <div class="alert alert-secondary mb-0">
                            <strong>{{ __('complimentary.kpi_orders') }}:</strong> <span id="kpi_orders_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-info mb-0">
                            <strong>{{ __('complimentary.kpi_qty') }}:</strong> <span id="kpi_qty_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-warning mb-0">
                            <strong>{{ __('complimentary.kpi_retail') }}:</strong> <span id="kpi_retail_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-success mb-0">
                            <strong>{{ __('complimentary.kpi_cost') }}:</strong> <span id="kpi_cost_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="complimentary_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_order_no') }}</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('common.customer') }}</th>
                                <th>{{ __('complimentary.status') }}</th>
                                <th>{{ __('common.product') }}</th>
                                <th>{{ __('reports.col_variation') }}</th>
                                <th class="text-end">{{ __('common.qty') }}</th>
                                <th class="text-end">{{ __('common.unit_price') }}</th>
                                <th class="text-end">{{ __('complimentary.retail_value') }}</th>
                                <th class="text-end">{{ __('complimentary.actual_cost') }}</th>
                                <th>{{ __('complimentary.reason') }}</th>
                                <th>{{ __('common.warehouse') }}</th>
                                <th>{{ __('complimentary.issued_by') }}</th>
                                <th>{{ __('common.action') }}</th>
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
                        {data:'customer_name',name:'customer_name',sortable:false},
                        {data:'complimentary_status_label',name:'complimentary_status_label',sortable:false},
                        {data:'product_name',name:'product_name',sortable:false},
                        {data:'variation_name',name:'variation_name',sortable:false},
                        {data:'quantity',name:'quantity',sortable:false,className:'text-end'},
                        {data:'unit_price',name:'unit_price',sortable:false,className:'text-end'},
                        {data:'retail_value',name:'retail_value',sortable:false,className:'text-end'},
                        {data:'actual_cost',name:'actual_cost',sortable:false,className:'text-end'},
                        {data:'reason_name',name:'reason_name',sortable:false},
                        {data:'warehouse_name',name:'warehouse_name',sortable:false},
                        {data:'issued_by_name',name:'issued_by_name',sortable:false},
                        {data:'action',name:'action',sortable:false,searchable:false}",
        'route' => 'complimentary-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'complimentary_report_table',
        'variable' => 'complimentary_report_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),warehouse_id:$('#warehouse_id').val(),user_id:$('#user_id').val(),complimentary_status:$('#complimentary_status').val(),complimentary_reason_id:$('#complimentary_reason_id').val(),issued_by_id:$('#issued_by_id').val(),daily_order_id:$('#daily_order_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                warehouse_id: $('#warehouse_id').val() || '',
                user_id: $('#user_id').val() || '',
                complimentary_status: $('#complimentary_status').val() || '',
                complimentary_reason_id: $('#complimentary_reason_id').val() || '',
                issued_by_id: $('#issued_by_id').val() || '',
                daily_order_id: $('#daily_order_id').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #branch_id, #warehouse_id, #user_id, #complimentary_status, #complimentary_reason_id, #issued_by_id').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTablecomplimentary_report_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/complimentary-report/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#kpi_orders_display').text(response.kpi_orders ?? '-');
                    $('#kpi_qty_display').text(response.kpi_qty ?? '-');
                    $('#kpi_retail_display').text(response.kpi_retail ?? '-');
                    $('#kpi_cost_display').text(response.kpi_cost ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/complimentary-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/complimentary-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/complimentary-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/complimentary-report/export-csv');
        });
    </script>
@endsection
