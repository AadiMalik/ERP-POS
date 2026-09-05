@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.order_detail') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.order-detail.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.order-detail.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.order-detail.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.order-detail.export-csv')
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
                            <label class="form-label">{{ __('reports.order_source') }}</label>
                            <select id="order_source_id" class="form-select">
                                <option value="">--All Sources--</option>
                                @foreach ($order_sources as $item)
                                    <option value="{{ $item->order_source_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('reports.order_status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                <option value="draft">Draft</option>
                                <option value="hold">Hold</option>
                                <option value="posted">Posted</option>
                                <option value="shipped">Shipped</option>
                                <option value="out_for_delivery">Out for Delivery</option>
                                <option value="delivered">Delivered</option>
                                <option value="returned">Returned</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="void">Void</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('reports.payment_status') }}</label>
                            <select id="payment_status" class="form-select">
                                <option value="">--All--</option>
                                <option value="paid">Paid</option>
                                <option value="partially_paid">Partially Paid</option>
                                <option value="unpaid">Unpaid</option>
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
                    <div class="col-md-2">
                        <div class="alert alert-info mb-0">
                            <strong>Qty:</strong> <span id="grand_qty_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="alert alert-secondary mb-0">
                            <strong>Gross:</strong> <span id="grand_gross_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="alert alert-secondary mb-0">
                            <strong>Discount:</strong> <span id="grand_discount_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="alert alert-secondary mb-0">
                            <strong>Tax:</strong> <span id="grand_tax_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="alert alert-secondary mb-0">
                            <strong>Delivery:</strong> <span id="grand_delivery_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="alert alert-success mb-0">
                            <strong>Net:</strong> <span id="grand_net_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="order_detail_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_order_no') }}</th>
                                <th>{{ __('reports.col_date_time') }}</th>
                                <th>{{ __('common.customer') }}</th>
                                <th>{{ __('common.branch') }}</th>
                                <th>{{ __('reports.col_order_source') }}</th>
                                <th>Order Status</th>
                                <th>{{ __('reports.col_payment_status') }}</th>
                                <th>{{ __('common.product') }}</th>
                                <th>{{ __('reports.col_variation') }}</th>
                                <th>{{ __('reports.col_sku') }}</th>
                                <th class="text-end">{{ __('common.qty') }}</th>
                                <th class="text-end">Unit Price</th>
                                <th class="text-end">{{ __('reports.col_discount') }}</th>
                                <th class="text-end">{{ __('reports.col_tax') }}</th>
                                <th class="text-end">Delivery Charge</th>
                                <th class="text-end">Final Amount</th>
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
                        {data:'customer',name:'customer',sortable:false},
                        {data:'branch',name:'branch',sortable:false},
                        {data:'order_source',name:'order_source',sortable:false},
                        {data:'order_status',name:'order_status',sortable:false},
                        {data:'payment_status',name:'payment_status',sortable:false},
                        {data:'product_name',name:'product_name',sortable:false},
                        {data:'variation_name',name:'variation_name',sortable:false},
                        {data:'sku',name:'sku',sortable:false},
                        {data:'quantity',name:'quantity',sortable:false,className:'text-end'},
                        {data:'unit_price',name:'unit_price',sortable:false,className:'text-end'},
                        {data:'discount_amount',name:'discount_amount',sortable:false,className:'text-end'},
                        {data:'tax_amount',name:'tax_amount',sortable:false,className:'text-end'},
                        {data:'delivery_charge',name:'delivery_charge',sortable:false,className:'text-end'},
                        {data:'total',name:'total',sortable:false,className:'text-end'},
                        {data:'action',name:'action',sortable:false,searchable:false}",
        'route' => 'order-detail/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'order_detail_table',
        'variable' => 'order_detail_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),order_source_id:$('#order_source_id').val(),status:$('#status').val(),payment_status:$('#payment_status').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                order_source_id: $('#order_source_id').val() || '',
                status: $('#status').val() || '',
                payment_status: $('#payment_status').val() || '',
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
            $('#branch_id').select2();
            $('#order_source_id').select2();
            $('#status').select2();
            $('#payment_status').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTableorder_detail_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/order-detail/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#grand_qty_display').text(response.grand_qty ?? '-');
                    $('#grand_gross_display').text(response.grand_gross ?? '-');
                    $('#grand_discount_display').text(response.grand_discount ?? '-');
                    $('#grand_tax_display').text(response.grand_tax ?? '-');
                    $('#grand_delivery_display').text(response.grand_delivery ?? '-');
                    $('#grand_net_display').text(response.grand_net ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/order-detail/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/order-detail/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/order-detail/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/order-detail/export-csv');
        });
    </script>
@endsection
