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
                    @canAccess('reports.serial-number-sold.print')
                    <a href="javascript:void(0);" id="btn_print" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.serial-number-sold.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.serial-number-sold.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.serial-number-sold.export-csv')
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
                            <label class="form-label">Serial Number</label>
                            <input type="text" id="serial_no" class="form-control" placeholder="Search serial no.">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="serial_number_sold_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Serial No.</th>
                                <th>Product</th>
                                <th>Variation</th>
                                <th>Customer</th>
                                <th>Order #</th>
                                <th>Unit Cost</th>
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
                        {data:'serial_no',name:'serial_no',sortable:false},
                        {data:'product_name',name:'product_name',sortable:false},
                        {data:'variation_name',name:'variation_name',sortable:false},
                        {data:'customer_name',name:'customer_name',sortable:false},
                        {data:'order_daily_id',name:'order_daily_id',sortable:false},
                        {data:'avg_price',name:'avg_price',sortable:false,className:'text-end'}",
        'route' => 'serial-number-sold/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'serial_number_sold_table',
        'variable' => 'serial_number_sold_table',
        'params' => "business_id:$('#business_id').val(),product_id:$('#product_id').val(),product_variation_id:$('#product_variation_id').val(),serial_no:$('#serial_no').val()",
    ])
    <script>
        function currentReportParams() {
            let p = {};
            ['business_id', 'product_id', 'product_variation_id', 'serial_no'].forEach(function(id) {
                if ($('#' + id).length) p[id] = $('#' + id).val() || '';
            });
            return p;
        }
        function buildReportUrl(path) {
            return url_local + path + '?' + $.param(currentReportParams());
        }
        $(document).ready(function() {
            $('select.form-select').select2();
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
        $('#search_btn').click(function() { serial_number_sold_table.ajax.reload(); });
        $('#reset_filter').click(function() {
            $('#filterSection select').val('').trigger('change');
            $('#filterSection input').val('');
            serial_number_sold_table.ajax.reload();
        });
        $('#toggleFilter').click(function() { $('#filterSection').slideToggle(); });
        $('#btn_print').click(function() { window.open(buildReportUrl('/admin/reports/serial-number-sold/print'), '_blank'); });
        $('#btn_pdf').click(function() { window.open(buildReportUrl('/admin/reports/serial-number-sold/pdf'), '_blank'); });
        $('#btn_excel').click(function() { window.location = buildReportUrl('/admin/reports/serial-number-sold/export'); });
        $('#btn_csv').click(function() { window.location = buildReportUrl('/admin/reports/serial-number-sold/export-csv'); });
    </script>
@endsection
