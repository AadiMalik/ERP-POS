@php
    use App\Enums\RoleNames;
    use App\Enums\SerialStatus;
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
                    @canAccess('reports.serial-number-register.print')
                    <a href="javascript:void(0);" id="btn_print" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.serial-number-register.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.serial-number-register.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.serial-number-register.export-csv')
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
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                @foreach (SerialStatus::getOptions() as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Serial Number</label>
                            <input type="text" id="serial_no" class="form-control" placeholder="Search serial no.">
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="serial_number_register_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_serial_no') }}</th>
                                <th>{{ __('common.product') }}</th>
                                <th>{{ __('reports.col_variation') }}</th>
                                <th>{{ __('reports.col_warehouse') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('reports.col_unit_cost') }}</th>
                                <th>{{ __('common.customer') }}</th>
                                <th>{{ __('reports.col_received_on') }}</th>
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
                        {data:'warehouse_name',name:'warehouse_name',sortable:false},
                        {data:'status_label',name:'status_label',sortable:false},
                        {data:'avg_price',name:'avg_price',sortable:false,className:'text-end'},
                        {data:'customer_name',name:'customer_name',sortable:false},
                        {data:'date_created',name:'date_created',sortable:false}",
        'route' => 'serial-number-register/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'serial_number_register_table',
        'variable' => 'serial_number_register_table',
        'params' => "business_id:$('#business_id').val(),warehouse_id:$('#warehouse_id').val(),product_id:$('#product_id').val(),product_variation_id:$('#product_variation_id').val(),status:$('#status').val(),serial_no:$('#serial_no').val()",
    ])
    <script>
        function currentReportParams() {
            let p = {};
            ['business_id', 'warehouse_id', 'product_id', 'product_variation_id', 'status', 'serial_no'].forEach(function(id) {
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
        $('#search_btn').click(function() { serial_number_register_table.ajax.reload(); });
        $('#reset_filter').click(function() {
            $('#filterSection select').val('').trigger('change');
            $('#filterSection input').val('');
            serial_number_register_table.ajax.reload();
        });
        $('#toggleFilter').click(function() { $('#filterSection').slideToggle(); });
        $('#btn_print').click(function() { window.open(buildReportUrl('/admin/reports/serial-number-register/print'), '_blank'); });
        $('#btn_pdf').click(function() { window.open(buildReportUrl('/admin/reports/serial-number-register/pdf'), '_blank'); });
        $('#btn_excel').click(function() { window.location = buildReportUrl('/admin/reports/serial-number-register/export'); });
        $('#btn_csv').click(function() { window.location = buildReportUrl('/admin/reports/serial-number-register/export-csv'); });
    </script>
@endsection
