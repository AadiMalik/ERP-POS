@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Cancelled Orders Report
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.cancelled-orders.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.cancelled-orders.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.cancelled-orders.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.cancelled-orders.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> CSV
                    </a>
                    @endcanAccess
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="business_id" class="form-select">
                                    <option value="">--All Businesses--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Branch</label>
                            <select id="branch_id" class="form-select">
                                <option value="">--All Branches--</option>
                                @foreach ($branches as $item)
                                    <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Order Source</label>
                            <select id="order_source_id" class="form-select">
                                <option value="">--All Sources--</option>
                                @foreach ($order_sources as $item)
                                    <option value="{{ $item->order_source_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Customer</label>
                            <select id="user_id" class="form-select">
                                <option value="">--All Customers--</option>
                                @foreach ($customers as $item)
                                    <option value="{{ $item->user_id }}">{{ $item->code ?? '' }}
                                        {{ $item->user->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Period</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                Search
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 p-4 pb-0">
                    <div class="col-md-3">
                        <div class="alert alert-info mb-0">
                            <strong>Cancelled Orders:</strong> <span id="grand_orders_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-warning mb-0">
                            <strong>Total Amount:</strong> <span id="grand_amount_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="cancelled_orders_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Order No</th>
                                <th>Date</th>
                                <th>Customer</th>
                                <th>Branch</th>
                                <th>Order Source</th>
                                <th>Status</th>
                                <th class="text-end">Amount</th>
                                <th>Cancellation Reason</th>
                                <th>Cancelled By</th>
                                <th>Action</th>
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
                        {data:'status',name:'status',sortable:false},
                        {data:'total',name:'total',sortable:false,className:'text-end'},
                        {data:'cancellation_reason',name:'cancellation_reason',sortable:false},
                        {data:'cancelled_by',name:'cancelled_by',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'cancelled-orders/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'cancelled_orders_table',
        'variable' => 'cancelled_orders_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),order_source_id:$('#order_source_id').val(),user_id:$('#user_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                order_source_id: $('#order_source_id').val() || '',
                user_id: $('#user_id').val() || '',
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
            $('#user_id').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTablecancelled_orders_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/cancelled-orders/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#grand_orders_display').text(response.grand_orders ?? '-');
                    $('#grand_amount_display').text(response.grand_amount ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/cancelled-orders/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/cancelled-orders/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/cancelled-orders/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/cancelled-orders/export-csv');
        });
    </script>
@endsection
