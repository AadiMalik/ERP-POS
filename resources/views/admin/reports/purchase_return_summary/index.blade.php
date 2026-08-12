@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Purchase Return Summary Report
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
                    <a href="javascript:void(0);" id="btn_print" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    <a href="javascript:void(0);" id="btn_pdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> CSV
                    </a>
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
                            <label class="form-label">Group By</label>
                            <select id="group_by" class="form-select">
                                @foreach ($group_by_options as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Supplier</label>
                            <select id="supplier_id" class="form-select">
                                <option value="">--All Suppliers--</option>
                                @foreach ($suppliers as $item)
                                    <option value="{{ $item->supplier_id }}">{{ $item->code ?? '' }}
                                        {{ $item->name ?? '' }}
                                    </option>
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
                            <label class="form-label">Return Type</label>
                            <select id="return_type" class="form-select">
                                <option value="">--All Types--</option>
                                <option value="direct">Direct Purchase</option>
                                <option value="grn">GRN</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="">--All Statuses--</option>
                                <option value="pending">Pending</option>
                                <option value="approved">Approved</option>
                                <option value="cancelled">Cancelled</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
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
                        <div class="alert alert-secondary mb-0">
                            <strong>Qty:</strong> <span id="grand_qty_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="alert alert-secondary mb-0">
                            <strong>Subtotal:</strong> <span id="grand_subtotal_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="alert alert-info mb-0">
                            <strong>Discount:</strong> <span id="grand_discount_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="alert alert-info mb-0">
                            <strong>Tax:</strong> <span id="grand_tax_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning mb-0">
                            <strong>Total:</strong> <span id="grand_total_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="purchase_return_summary_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Group</th>
                                <th class="text-end">Returns</th>
                                <th class="text-end">Qty</th>
                                <th class="text-end">Subtotal</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end">Total</th>
                                <th>Accounting Status</th>
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
                        {data:'group_label',name:'group_label',sortable:false},
                        {data:'return_count',name:'return_count',sortable:false,className:'text-end'},
                        {data:'total_qty',name:'total_qty',sortable:false,className:'text-end'},
                        {data:'total_subtotal',name:'total_subtotal',sortable:false,className:'text-end'},
                        {data:'total_discount',name:'total_discount',sortable:false,className:'text-end'},
                        {data:'total_tax',name:'total_tax',sortable:false,className:'text-end'},
                        {data:'total_amount',name:'total_amount',sortable:false,className:'text-end'},
                        {data:'reconciliation',name:'reconciliation',sortable:false}",
        'route' => 'purchase-return-summary/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'purchase_return_summary_table',
        'variable' => 'purchase_return_summary_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),group_by:$('#group_by').val(),supplier_id:$('#supplier_id').val(),warehouse_id:$('#warehouse_id').val(),return_type:$('#return_type').val(),status:$('#status').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                group_by: $('#group_by').val() || 'none',
                supplier_id: $('#supplier_id').val() || '',
                warehouse_id: $('#warehouse_id').val() || '',
                return_type: $('#return_type').val() || '',
                status: $('#status').val() || '',
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
            $('#group_by').select2();
            $('#supplier_id').select2();
            $('#warehouse_id').select2();
            $('#return_type').select2();
            $('#status').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTablepurchase_return_summary_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/purchase-return-summary/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#grand_qty_display').text(response.grand_qty ?? '-');
                    $('#grand_subtotal_display').text(response.grand_subtotal ?? '-');
                    $('#grand_discount_display').text(response.grand_discount ?? '-');
                    $('#grand_tax_display').text(response.grand_tax ?? '-');
                    $('#grand_total_display').text(response.grand_total ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/purchase-return-summary/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/purchase-return-summary/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/purchase-return-summary/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/purchase-return-summary/export-csv');
        });
    </script>
@endsection
