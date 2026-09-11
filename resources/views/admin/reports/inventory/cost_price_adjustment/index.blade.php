@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ $report_title ?? __('reports.cost_price_adjustment') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i> {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.cost-price-adjustment.print')
                    <a href="javascript:void(0);" id="btn_print" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.cost-price-adjustment.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.cost-price-adjustment.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.cost-price-adjustment.export-csv')
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
                            <label class="form-label">{{ __('reports.col_created_by') }}</label>
                            <select id="createdby_id" class="form-select">
                                <option value="">{{ __('common.all') }}</option>
                                @foreach ($users as $item)
                                    <option value="{{ $item->id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                <option value="pending">{{ __('common.pending') }}</option>
                                <option value="approved">{{ __('common.approved') }}</option>
                                <option value="cancelled">{{ __('common.cancelled') }}</option>
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
                    <div class="col-md-4">
                        <div class="alert alert-success mb-0">
                            <strong>{{ __('reports.positive_adjustment_total') }}:</strong> <span id="positive_total_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-danger mb-0">
                            <strong>{{ __('reports.negative_adjustment_total') }}:</strong> <span id="negative_total_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>{{ __('reports.net_adjustment_total') }}:</strong> <span id="net_total_display">-</span>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="cost_price_adjustment_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_reference_no') }}</th>
                                <th>{{ __('reports.col_date') }}</th>
                                <th>{{ __('common.product') }}</th>
                                <th>{{ __('reports.col_variation') }}</th>
                                <th>{{ __('reports.col_warehouse') }}</th>
                                <th>{{ __('common.branch') }}</th>
                                <th>{{ __('reports.col_previous_cost') }}</th>
                                <th>{{ __('reports.col_new_cost') }}</th>
                                <th>{{ __('reports.col_qty') }}</th>
                                <th>{{ __('reports.col_difference_per_unit') }}</th>
                                <th>{{ __('reports.col_total_adjustment') }}</th>
                                <th>{{ __('reports.col_reason') }}</th>
                                <th>{{ __('reports.col_status') }}</th>
                                <th>{{ __('reports.col_created_by') }}</th>
                                <th>{{ __('reports.col_approved_by') }}</th>
                                <th>{{ __('reports.col_jv_no') }}</th>
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
                        {data:'reference_no',name:'reference_no'},
                        {data:'adjustment_date',name:'adjustment_date'},
                        {data:'product_name',name:'product_name',sortable:false},
                        {data:'variation_name',name:'variation_name',sortable:false},
                        {data:'warehouse_name',name:'warehouse_name',sortable:false},
                        {data:'branch_name',name:'branch_name',sortable:false},
                        {data:'previous_cost_price',name:'previous_cost_price',sortable:false,className:'text-end'},
                        {data:'new_cost_price',name:'new_cost_price',sortable:false,className:'text-end'},
                        {data:'quantity_on_hand',name:'quantity_on_hand',sortable:false,className:'text-end'},
                        {data:'difference_per_unit',name:'difference_per_unit',sortable:false,className:'text-end'},
                        {data:'total_adjustment_amount',name:'total_adjustment_amount',sortable:false,className:'text-end'},
                        {data:'reason',name:'reason',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'created_by_name',name:'created_by_name',sortable:false},
                        {data:'approved_by_name',name:'approved_by_name',sortable:false},
                        {data:'journal_entry_no',name:'journal_entry_no',sortable:false}",
        'route' => 'reports/cost-price-adjustment/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'cost_price_adjustment_report_table',
        'variable' => 'cost_price_adjustment_report_table',
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),warehouse_id:$('#warehouse_id').val(),product_id:$('#product_id').val(),product_variation_id:$('#product_variation_id').val(),createdby_id:$('#createdby_id').val(),status:$('#status').val()",
    ])
    <script>
        function currentReportParams() {
            let p = {};
            ['business_id','branch_id','warehouse_id','product_id','product_variation_id','createdby_id','status'].forEach(function(id) {
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
            let q = new URLSearchParams(window.location.search);
            q.forEach(function(v, k) { if ($('#' + k).length) $('#' + k).val(v).trigger('change'); });
            cost_price_adjustment_report_table.on('xhr.dt', function() { refreshTotals(); });
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
        $('#search_btn').click(function() { cost_price_adjustment_report_table.ajax.reload(); refreshTotals(); });
        $('#reset_filter').click(function() {
            $('#filterSection select').val('').trigger('change');
            $('#filterSection input').val('');
            cost_price_adjustment_report_table.ajax.reload();
            refreshTotals();
        });
        $('#toggleFilter').click(function() { $('#filterSection').slideToggle(); });
        $('#btn_print').click(function() { window.open(buildReportUrl('/admin/reports/cost-price-adjustment/print'), '_blank'); });
        $('#btn_pdf').click(function() { window.open(buildReportUrl('/admin/reports/cost-price-adjustment/pdf'), '_blank'); });
        $('#btn_excel').click(function() { window.location = buildReportUrl('/admin/reports/cost-price-adjustment/export'); });
        $('#btn_csv').click(function() { window.location = buildReportUrl('/admin/reports/cost-price-adjustment/export-csv'); });
        function refreshTotals() {
            setTimeout(function() {
                let json = cost_price_adjustment_report_table.ajax.json();
                if (!json) return;
                if (json.positive_total !== undefined) $('#positive_total_display').text(json.positive_total);
                if (json.negative_total !== undefined) $('#negative_total_display').text(json.negative_total);
                if (json.net_total !== undefined) $('#net_total_display').text(json.net_total);
            }, 400);
        }
    </script>
@endsection
