@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ $report_title ?? 'Inventory Report' }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i> Filters
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.stock-reconciliation.print')
                    <a href="javascript:void(0);" id="btn_print" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.stock-reconciliation.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.stock-reconciliation.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.stock-reconciliation.export-csv')
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
                                        <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
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
                            <label class="form-label">Warehouse</label>
                            <select id="warehouse_id" class="form-select">
                                <option value="">--All Warehouses--</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>
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
                            <label class="form-label">Product Variation</label>
                            <select id="product_variation_id" class="form-select">
                                <option value="">--All Variations--</option>
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
                            <label class="form-label">Brand</label>
                            <select id="brand_id" class="form-select">
                                <option value="">--All Brands--</option>
                                @foreach ($brands as $item)
                                    <option value="{{ $item->brand_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Report View</label>
                            <select id="report_mode" class="form-select">
                                    <option value="stock_take">Stock Taking</option>
                                    <option value="adjustments">Adjustment Movements</option>
                            </select>
                        </div>

                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                        </div>
                    </div>
                </div>
                <div class="row g-3 p-4 pb-0">
                    <div class="col-md-3">
                        <div class="alert alert-info mb-0">
                            <strong>Diff Qty:</strong> <span id="total_diff_qty_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-info mb-0">
                            <strong>Diff Value:</strong> <span id="total_diff_value_display">-</span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="stock_reconciliation_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Doc No</th>
                                <th>Date</th>
                                <th>Type</th>
                                <th>Warehouse</th>
                                <th>Product</th>
                                <th>Variation</th>
                                <th>System</th>
                                <th>Physical</th>
                                <th>Diff Qty</th>
                                <th>Unit Cost</th>
                                <th>Diff Value</th>
                                <th>Status</th>
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
                        {data:'doc_no',name:'doc_no',sortable:false},
                        {data:'doc_date',name:'doc_date',sortable:false},
                        {data:'movement_type',name:'movement_type',sortable:false},
                        {data:'warehouse_name',name:'warehouse_name',sortable:false},
                        {data:'product_name',name:'product_name',sortable:false},
                        {data:'variation_name',name:'variation_name',sortable:false},
                        {data:'system_quantity',name:'system_quantity',sortable:false,className:'text-end'},
                        {data:'physical_quantity',name:'physical_quantity',sortable:false,className:'text-end'},
                        {data:'difference_quantity',name:'difference_quantity',sortable:false,className:'text-end'},
                        {data:'unit_cost',name:'unit_cost',sortable:false,className:'text-end'},
                        {data:'difference_value',name:'difference_value',sortable:false,className:'text-end'},
                        {data:'status',name:'status',sortable:false}",
        'route' => 'stock-reconciliation/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'stock_reconciliation_table',
        'variable' => 'stock_reconciliation_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),warehouse_id:$('#warehouse_id').val(),product_id:$('#product_id').val(),product_variation_id:$('#product_variation_id').val(),report_mode:$('#report_mode').val()",
    ])
    <script>
        function currentReportParams() {
            let p = {};
            business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),warehouse_id:$('#warehouse_id').val(),product_id:$('#product_id').val(),product_variation_id:$('#product_variation_id').val(),report_mode:$('#report_mode').val();
            // rebuild from params expression is awkward; collect known ids
            ['business_id','branch_id','warehouse_id','product_id','product_variation_id','category_id','brand_id','report_mode','age_bucket','movement_class','source_warehouse_id','destination_warehouse_id','batch_no','expiry_within_days','expired_only','transaction_type','quantity'].forEach(function(id) {
                if ($('#' + id).length) p[id] = $('#' + id).val() || '';
            });
            if (typeof filterStartDate !== 'undefined') p.start_date = filterStartDate;
            if (typeof filterEndDate !== 'undefined') p.end_date = filterEndDate;
            return p;
        }
        function buildReportUrl(path) {
            return url_local + path + '?' + $.param(currentReportParams());
        }
        $(document).ready(function() {
            $('select.form-select').select2();
            @if(!empty($preset_mode))
            if ($('#report_mode').length) $('#report_mode').val(@json($preset_mode)).trigger('change');
            @endif
            let q = new URLSearchParams(window.location.search);
            q.forEach(function(v, k) { if ($('#' + k).length) $('#' + k).val(v).trigger('change'); });
        });
        $('#product_id').change(function() {
            let product_id = $(this).val();
            if (!product_id) {
                $('#product_variation_id').html('<option value="">--All Variations--</option>').trigger('change');
                return;
            }
            ajaxRequest({ url: url_local + '/admin/product/variation-by-product/' + product_id, data: {} })
                .then((response) => {
                    let options = '<option value="">--All Variations--</option>';
                    $.each(response.Data || [], function(i, item) {
                        options += '<option value="' + item.product_variation_id + '">' + item.name + '</option>';
                    });
                    $('#product_variation_id').html(options).trigger('change');
                });
        });
        $('#search_btn').click(function() { stock_reconciliation_table.ajax.reload(); refreshTotals(); });
        $('#reset_filter').click(function() {
            $('#filterSection select').val('').trigger('change');
            $('#filterSection input').val('');
            stock_reconciliation_table.ajax.reload();
            refreshTotals();
        });
        $('#toggleFilter').click(function() { $('#filterSection').slideToggle(); });
        $('#btn_print').click(function() { window.open(buildReportUrl('/admin/reports/stock-reconciliation/print'), '_blank'); });
        $('#btn_pdf').click(function() { window.open(buildReportUrl('/admin/reports/stock-reconciliation/pdf'), '_blank'); });
        $('#btn_excel').click(function() { window.location = buildReportUrl('/admin/reports/stock-reconciliation/export'); });
        $('#btn_csv').click(function() { window.location = buildReportUrl('/admin/reports/stock-reconciliation/export-csv'); });
        function refreshTotals() {
            setTimeout(function() {
                let json = stock_reconciliation_table.ajax.json();
                if (!json) return;
                if (json.total_diff_qty !== undefined) $('#total_diff_qty_display').text(json.total_diff_qty);
                if (json.total_diff_value !== undefined) $('#total_diff_value_display').text(json.total_diff_value);
            }, 400);
        }
        stock_reconciliation_table.on('xhr.dt', function() { refreshTotals(); });
    </script>
@endsection