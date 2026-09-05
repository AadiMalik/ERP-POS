@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.stock_ledger') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.stock-ledger.print')
                    <a href="javascript:void(0);" id="btn_print" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.stock-ledger.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.stock-ledger.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.stock-ledger.export-csv')
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
                            <label class="form-label">{{ __('reports.transaction_type') }}</label>
                            <select id="transaction_type" class="form-select">
                                <option value="">--All Transaction Types--</option>
                                @foreach ($transaction_types as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('reports.source_module') }}</label>
                            <select id="reference_type" class="form-select">
                                <option value="">--All Source Modules--</option>
                                @foreach ($reference_types as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.date') }}</label>
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
                            <strong>Total Qty In:</strong> <span id="total_qty_in_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-warning mb-0">
                            <strong>Total Qty Out:</strong> <span id="total_qty_out_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-secondary mb-0">
                            <strong>Total Value:</strong> <span id="total_value_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-success mb-0">
                            <strong>Opening / Closing Balance:</strong> <span id="balance_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-primary mb-0">
                            <strong>Reserved / Available:</strong> <span id="reserved_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="stock_ledger_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('common.date') }}</th>
                                <th>Source Module</th>
                                <th>{{ __('reports.col_reference_no') }}</th>
                                <th>{{ __('reports.col_warehouse') }}</th>
                                <th>{{ __('common.product') }}</th>
                                <th>{{ __('reports.col_variation') }}</th>
                                <th>Movement Type</th>
                                <th class="text-end">Qty In</th>
                                <th class="text-end">Qty Out</th>
                                <th class="text-end">{{ __('reports.col_unit_cost') }}</th>
                                <th class="text-end">{{ __('reports.col_value') }}</th>
                                <th class="text-end">{{ __('common.balance') }}</th>
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
                        {data:'transaction_date',name:'transaction_date',sortable:false},
                        {data:'reference_type',name:'reference_type',sortable:false},
                        {data:'reference_no',name:'reference_no',sortable:false},
                        {data:'warehouse_name',name:'warehouse_name',sortable:false},
                        {data:'product_name',name:'product_name',sortable:false},
                        {data:'variation_name',name:'variation_name',sortable:false},
                        {data:'transaction_type_label',name:'transaction_type_label',sortable:false},
                        {data:'quantity_in',name:'quantity_in',sortable:false,className:'text-end'},
                        {data:'quantity_out',name:'quantity_out',sortable:false,className:'text-end'},
                        {data:'unit_price',name:'unit_price',sortable:false,className:'text-end'},
                        {data:'value',name:'value',sortable:false,className:'text-end'},
                        {data:'quantity_after',name:'quantity_after',sortable:false,className:'text-end'}",
        'route' => 'stock-ledger/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'stock_ledger_table',
        'variable' => 'stock_ledger_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),warehouse_id:$('#warehouse_id').val(),product_id:$('#product_id').val(),product_variation_id:$('#product_variation_id').val(),category_id:$('#category_id').val(),brand_id:$('#brand_id').val(),transaction_type:$('#transaction_type').val(),reference_type:$('#reference_type').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                warehouse_id: $('#warehouse_id').val() || '',
                product_id: $('#product_id').val() || '',
                product_variation_id: $('#product_variation_id').val() || '',
                category_id: $('#category_id').val() || '',
                brand_id: $('#brand_id').val() || '',
                transaction_type: $('#transaction_type').val() || '',
                reference_type: $('#reference_type').val() || '',
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
            $('#product_id').select2();
            $('#product_variation_id').select2();
            $('#category_id').select2();
            $('#brand_id').select2();
            $('#transaction_type').select2();
            $('#reference_type').select2();
            refreshTotals();
        });

        $('#product_id').change(function() {
            let product_id = $(this).val();
            if (!product_id) {
                $('#product_variation_id').html('<option value="">{{ __('common.all_variations') }}</option>');
                return;
            }
            ajaxRequest({
                    url: url_local + '/admin/product/variation-by-product/' + product_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">{{ __('common.all_variations') }}</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.product_variation_id}">${item.name}</option>`;
                    });
                    $('#product_variation_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });

        $('#search_btn').click(function() {
            initDataTablestock_ledger_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/stock-ledger/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#total_qty_in_display').text(response.total_qty_in ?? '-');
                    $('#total_qty_out_display').text(response.total_qty_out ?? '-');
                    $('#total_value_display').text(response.total_value ?? '-');

                    if (response.opening_balance !== undefined) {
                        $('#balance_display').text(response.opening_balance + ' / ' + response.closing_balance);
                        $('#reserved_display').text(response.reserved_quantity + ' / ' + response.available_quantity);
                    } else {
                        $('#balance_display').text('Select a single Product + Variation + Warehouse to view');
                        $('#reserved_display').text('-');
                    }
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/stock-ledger/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/stock-ledger/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/stock-ledger/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/stock-ledger/export-csv');
        });
    </script>
@endsection
