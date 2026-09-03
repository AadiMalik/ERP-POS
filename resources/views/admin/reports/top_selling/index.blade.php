@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Top Selling Report
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
                    @canAccess('reports.top-selling.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.top-selling.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.top-selling.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.top-selling.export-csv')
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
                            <label class="form-label">Product</label>
                            <select id="product_id" class="form-select">
                                <option value="">--All Products--</option>
                                @foreach ($products as $item)
                                    <option value="{{ $item->product_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Group By</label>
                            <select id="group_by" class="form-select">
                                <option value="variation">Variation</option>
                                <option value="product">Product</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Rank By</label>
                            <select id="rank_by" class="form-select">
                                <option value="net">Net Sales</option>
                                <option value="quantity">Quantity</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Limit</label>
                            <select id="limit" class="form-select">
                                <option value="25">Top 25</option>
                                <option value="50" selected>Top 50</option>
                                <option value="100">Top 100</option>
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
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>Qty Sold:</strong> <span id="grand_qty_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success mb-0">
                            <strong>Net Sales:</strong> <span id="grand_net_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="top_selling_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Rank</th>
                                <th>Product</th>
                                <th>Variation</th>
                                <th>SKU</th>
                                <th class="text-end">Qty Sold</th>
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
                        {data:'rank',name:'rank',sortable:false},
                        {data:'product_name',name:'product_name',sortable:false},
                        {data:'variation_name',name:'variation_name',sortable:false},
                        {data:'sku',name:'sku',sortable:false},
                        {data:'total_qty',name:'total_qty',sortable:false,className:'text-end'},
                        {data:'net',name:'net',sortable:false,className:'text-end'}",
        'route' => 'top-selling/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'top_selling_table',
        'variable' => 'top_selling_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),order_source_id:$('#order_source_id').val(),product_id:$('#product_id').val(),group_by:$('#group_by').val(),rank_by:$('#rank_by').val(),limit:$('#limit').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                order_source_id: $('#order_source_id').val() || '',
                product_id: $('#product_id').val() || '',
                group_by: $('#group_by').val() || 'variation',
                rank_by: $('#rank_by').val() || 'net',
                limit: $('#limit').val() || '50',
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
            $('#product_id').select2();
            $('#group_by').select2();
            $('#rank_by').select2();
            $('#limit').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTabletop_selling_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/top-selling/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#grand_qty_display').text(response.grand_qty ?? '-');
                    $('#grand_net_display').text(response.grand_net ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/top-selling/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/top-selling/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/top-selling/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/top-selling/export-csv');
        });
    </script>
@endsection
