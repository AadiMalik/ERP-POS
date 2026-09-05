@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ $report_title ?? __('reports.waste_damage_expiry_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i> {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.waste-damage-expiry.print')
                    <a href="javascript:void(0);" id="btn_print" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.waste-damage-expiry.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.waste-damage-expiry.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.waste-damage-expiry.export-csv')
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
                            <label class="form-label">Batch/Lot No.</label>
                            <input type="text" id="batch_no" class="form-control" placeholder="Batch/Lot No.">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Loss Type</label>
                            <select id="loss_type" class="form-select">
                                <option value="">--All Loss Types--</option>
                                @foreach (($loss_types ?? []) as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Reason</label>
                            <select id="loss_reason_id" class="form-select">
                                <option value="">--All Reasons--</option>
                                @foreach (($loss_reasons ?? []) as $item)
                                    <option value="{{ $item->loss_reason_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                @foreach (($statuses ?? []) as $value => $label)
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
                    <div class="col-md-2">
                        <div class="alert alert-info mb-0"><strong>Total Qty:</strong> <span id="total_qty_display">-</span></div>
                    </div>
                    <div class="col-md-2">
                        <div class="alert alert-info mb-0"><strong>Total Value:</strong> <span id="total_value_display">-</span></div>
                    </div>
                    @foreach (($loss_types ?? []) as $value => $label)
                        <div class="col-md-2">
                            <div class="alert alert-secondary mb-0"><strong>{{ $label }}:</strong> <span id="qty_{{ $value }}_display">-</span></div>
                        </div>
                    @endforeach
                </div>
                <div class="table-responsive p-4">
                    <table id="waste_damage_expiry_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_reference_no_alt') }}</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('reports.col_warehouse') }}</th>
                                <th>{{ __('common.product') }}</th>
                                <th>{{ __('reports.col_variation') }}</th>
                                <th>{{ __('reports.col_batch') }}</th>
                                <th>{{ __('reports.col_expiry') }}</th>
                                <th>{{ __('common.qty') }}</th>
                                <th>{{ __('reports.col_unit') }}</th>
                                <th>{{ __('reports.col_unit_cost') }}</th>
                                <th>{{ __('reports.col_value') }}</th>
                                <th>{{ __('reports.col_loss_type') }}</th>
                                <th>{{ __('reports.col_reason') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('reports.col_created_by') }}</th>
                                <th>Approved By</th>
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
                        {data:'reference_no',name:'reference_no',sortable:false},
                        {data:'transaction_date',name:'transaction_date',sortable:false},
                        {data:'warehouse_name',name:'warehouse_name',sortable:false},
                        {data:'product_name',name:'product_name',sortable:false},
                        {data:'variation_name',name:'variation_name',sortable:false},
                        {data:'batch_no',name:'batch_no',sortable:false},
                        {data:'expiry_date',name:'expiry_date',sortable:false},
                        {data:'quantity',name:'quantity',sortable:false,className:'text-end'},
                        {data:'unit_name',name:'unit_name',sortable:false},
                        {data:'unit_cost',name:'unit_cost',sortable:false,className:'text-end'},
                        {data:'value',name:'value',sortable:false,className:'text-end'},
                        {data:'loss_type_label',name:'loss_type_label',sortable:false},
                        {data:'loss_reason',name:'loss_reason',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'created_by',name:'created_by',sortable:false},
                        {data:'approved_by',name:'approved_by',sortable:false}",
        'route' => 'waste-damage-expiry/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'waste_damage_expiry_table',
        'variable' => 'waste_damage_expiry_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),warehouse_id:$('#warehouse_id').val(),product_id:$('#product_id').val(),product_variation_id:$('#product_variation_id').val(),batch_no:$('#batch_no').val(),loss_type:$('#loss_type').val(),loss_reason_id:$('#loss_reason_id').val(),status:$('#status').val()",
    ])
    <script>
        function currentReportParams() {
            let p = {};
            ['business_id','branch_id','warehouse_id','product_id','product_variation_id','batch_no','loss_type','loss_reason_id','status'].forEach(function(id) {
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
            waste_damage_expiry_table.on('xhr.dt', function() { refreshTotals(); });
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
        $('#search_btn').click(function() { waste_damage_expiry_table.ajax.reload(); refreshTotals(); });
        $('#reset_filter').click(function() {
            $('#filterSection select').val('').trigger('change');
            $('#filterSection input').val('');
            waste_damage_expiry_table.ajax.reload();
            refreshTotals();
        });
        $('#toggleFilter').click(function() { $('#filterSection').slideToggle(); });
        $('#btn_print').click(function() { window.open(buildReportUrl('/admin/reports/waste-damage-expiry/print'), '_blank'); });
        $('#btn_pdf').click(function() { window.open(buildReportUrl('/admin/reports/waste-damage-expiry/pdf'), '_blank'); });
        $('#btn_excel').click(function() { window.location = buildReportUrl('/admin/reports/waste-damage-expiry/export'); });
        $('#btn_csv').click(function() { window.location = buildReportUrl('/admin/reports/waste-damage-expiry/export-csv'); });
        function refreshTotals() {
            setTimeout(function() {
                let json = waste_damage_expiry_table.ajax.json();
                if (!json) return;
                if (json.total_qty !== undefined) $('#total_qty_display').text(json.total_qty);
                if (json.total_value !== undefined) $('#total_value_display').text(json.total_value);
                @foreach (($loss_types ?? []) as $value => $label)
                    if (json.qty_{{ $value }} !== undefined) $('#qty_{{ $value }}_display').text(json.qty_{{ $value }});
                @endforeach
            }, 400);
        }
    </script>
@endsection
