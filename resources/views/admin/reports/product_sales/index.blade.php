@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Product-wise Sales Report
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
                    @canAccess('reports.product-sales.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.product-sales.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.product-sales.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.product-sales.export-csv')
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
                            <label class="form-label">Category</label>
                            <select id="category_id" class="form-select">
                                <option value="">--All Categories--</option>
                                @foreach ($categories as $item)
                                    <option value="{{ $item->category_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Product</label>
                            <select id="product_id" class="form-select">
                                <option value="">--All Products--</option>
                                @foreach ($products as $item)
                                    <option value="{{ $item->product_id }}">{{ $item->name ?? '' }}</option>
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
                    <div class="col-md-2">
                        <div class="alert alert-info mb-0">
                            <strong>Qty Sold:</strong> <span id="grand_qty_display">-</span>
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
                        <div class="alert alert-success mb-0">
                            <strong>Net Sales:</strong> <span id="grand_net_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="product_sales_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th class="text-end">Total Qty Sold</th>
                                <th class="text-end">Gross Sales</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end">Net Sales</th>
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
                        {data:'product_name',name:'product_name',sortable:false},
                        {data:'total_qty',name:'total_qty',sortable:false,className:'text-end'},
                        {data:'gross',name:'gross',sortable:false,className:'text-end'},
                        {data:'discount',name:'discount',sortable:false,className:'text-end'},
                        {data:'tax',name:'tax',sortable:false,className:'text-end'},
                        {data:'net',name:'net',sortable:false,className:'text-end'}",
        'route' => 'product-sales/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'product_sales_table',
        'variable' => 'product_sales_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),order_source_id:$('#order_source_id').val(),category_id:$('#category_id').val(),product_id:$('#product_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                order_source_id: $('#order_source_id').val() || '',
                category_id: $('#category_id').val() || '',
                product_id: $('#product_id').val() || '',
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
            $('#category_id').select2();
            $('#product_id').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTableproduct_sales_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/product-sales/data',
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
                    $('#grand_net_display').text(response.grand_net ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/product-sales/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/product-sales/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/product-sales/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/product-sales/export-csv');
        });
    </script>
@endsection
