@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ $report_title ?? __('reports.inventory_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i> {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.batch-expiry.print')
                    <a href="javascript:void(0);" id="btn_print" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.batch-expiry.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.batch-expiry.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.batch-expiry.export-csv')
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
                                        <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
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
                            <label class="form-label">{{ __('common.product') }}</label>
                            <select id="product_id" class="form-select">
                                <option value="">{{ __('common.all_products') }}</option>
                                @foreach ($products as $item)
                                    <option value="{{ $item->product_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.variation') }}</label>
                            <select id="product_variation_id" class="form-select">
                                <option value="">{{ __('common.all_variations') }}</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.category') }}</label>
                            <select id="category_id" class="form-select">
                                <option value="">{{ __('common.all_categories') }}</option>
                                @foreach ($categories as $item)
                                    <option value="{{ $item->category_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.brand') }}</label>
                            <select id="brand_id" class="form-select">
                                <option value="">{{ __('common.all_brands') }}</option>
                                @foreach ($brands as $item)
                                    <option value="{{ $item->brand_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('reports.report_view') }}</label>
                            <select id="report_mode" class="form-select">
                                    <option value="batch_stock">Batch/Lot Stock</option>
                                    <option value="expiry">Expiry & Near Expiry</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Batch No</label>
                            <input type="text" id="batch_no" class="form-control" placeholder="Batch no">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Expiry Within (days)</label>
                            <input type="number" id="expiry_within_days" class="form-control" min="0" placeholder="e.g. 30">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Expired Only</label>
                            <select id="expired_only" class="form-select">
                                <option value="">No</option>
                                <option value="1">Yes</option>
                            </select>
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
                            <strong>Total Qty:</strong> <span id="total_qty_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-info mb-0">
                            <strong>Total Value:</strong> <span id="total_value_display">-</span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="batch_expiry_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_batch') }}</th>
                                <th>{{ __('common.product') }}</th>
                                <th>{{ __('reports.col_variation') }}</th>
                                <th>{{ __('reports.col_warehouse') }}</th>
                                <th>{{ __('common.branch') }}</th>
                                <th>{{ __('common.qty') }}</th>
                                <th>{{ __('reports.col_avg_price') }}</th>
                                <th>{{ __('reports.col_value') }}</th>
                                <th>{{ __('reports.col_mfg_date') }}</th>
                                <th>{{ __('reports.col_expiry') }}</th>
                                <th>{{ __('reports.col_days_to_expiry') }}</th>
                                <th>{{ __('common.status') }}</th>
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
                        {data:'batch_no',name:'batch_no',sortable:false},
                        {data:'product_name',name:'product_name',sortable:false},
                        {data:'variation_name',name:'variation_name',sortable:false},
                        {data:'warehouse_name',name:'warehouse_name',sortable:false},
                        {data:'branch_name',name:'branch_name',sortable:false},
                        {data:'quantity',name:'quantity',sortable:false,className:'text-end'},
                        {data:'avg_price',name:'avg_price',sortable:false,className:'text-end'},
                        {data:'stock_value',name:'stock_value',sortable:false,className:'text-end'},
                        {data:'manufacturing_date',name:'manufacturing_date',sortable:false},
                        {data:'expiry_date',name:'expiry_date',sortable:false},
                        {data:'days_to_expiry',name:'days_to_expiry',sortable:false},
                        {data:'status',name:'status',sortable:false}",
        'route' => 'batch-expiry/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'batch_expiry_table',
        'variable' => 'batch_expiry_table',
        
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),warehouse_id:$('#warehouse_id').val(),product_id:$('#product_id').val(),product_variation_id:$('#product_variation_id').val(),report_mode:$('#report_mode').val(),batch_no:$('#batch_no').val(),expiry_within_days:$('#expiry_within_days').val(),expired_only:$('#expired_only').val()",
    ])
    <script>
        function currentReportParams() {
            let p = {};
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
            batch_expiry_table.on('xhr.dt', function() { refreshTotals(); });
        });
        $('#product_id').change(function() {
            let product_id = $(this).val();
            if (!product_id) {
                $('#product_variation_id').html('<option value="">{{ __('common.all_variations') }}</option>').trigger('change');
                return;
            }
            ajaxRequest({ url: url_local + '/admin/product/variation-by-product/' + product_id, data: {} })
                .then((response) => {
                    let options = '<option value="">{{ __('common.all_variations') }}</option>';
                    $.each(response.Data || [], function(i, item) {
                        options += '<option value="' + item.product_variation_id + '">' + item.name + '</option>';
                    });
                    $('#product_variation_id').html(options).trigger('change');
                });
        });
        $('#search_btn').click(function() { batch_expiry_table.ajax.reload(); refreshTotals(); });
        $('#reset_filter').click(function() {
            $('#filterSection select').val('').trigger('change');
            $('#filterSection input').val('');
            batch_expiry_table.ajax.reload();
            refreshTotals();
        });
        $('#toggleFilter').click(function() { $('#filterSection').slideToggle(); });
        $('#btn_print').click(function() { window.open(buildReportUrl('/admin/reports/batch-expiry/print'), '_blank'); });
        $('#btn_pdf').click(function() { window.open(buildReportUrl('/admin/reports/batch-expiry/pdf'), '_blank'); });
        $('#btn_excel').click(function() { window.location = buildReportUrl('/admin/reports/batch-expiry/export'); });
        $('#btn_csv').click(function() { window.location = buildReportUrl('/admin/reports/batch-expiry/export-csv'); });
        function refreshTotals() {
            setTimeout(function() {
                let json = batch_expiry_table.ajax.json();
                if (!json) return;
                if (json.total_qty !== undefined) $('#total_qty_display').text(json.total_qty);
                if (json.total_value !== undefined) $('#total_value_display').text(json.total_value);
            }, 400);
        }
    </script>
@endsection