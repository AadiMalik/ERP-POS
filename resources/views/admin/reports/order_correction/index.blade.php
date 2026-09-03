@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Order Correction Report
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
                    @canAccess('reports.order-correction-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.order-correction-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.order-correction-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.order-correction-report.export-csv')
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
                            <label class="form-label">Corrected By</label>
                            <select id="causer_id" class="form-select">
                                <option value="">--All Managers--</option>
                                @foreach ($managers as $item)
                                    <option value="{{ $item->user_id }}">{{ $item->name ?? '' }}</option>
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
                    <div class="col-md-3">
                        <div class="alert alert-info mb-0">
                            <strong>Corrected Orders:</strong> <span id="grand_corrections_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-warning mb-0">
                            <strong>Total Before:</strong> <span id="grand_old_total_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="alert alert-warning mb-0">
                            <strong>Total After:</strong> <span id="grand_new_total_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="order_correction_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Order No</th>
                                <th>Branch</th>
                                <th>Corrected By</th>
                                <th>Reason</th>
                                <th class="text-end">Previous Total</th>
                                <th class="text-end">New Total</th>
                                <th class="text-end">Difference</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Before/After diff modal - reads old_values/new_values straight off the
    row data returned by the datatable (already resolved to product/payment
    names server-side by OrderCorrectionReportService), no extra request. --}}
    <div class="modal fade" id="correctionDiffModal" tabindex="-1">
        <div class="modal-dialog modal-xl modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order Correction Details - <span id="cdOrderNo"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3"><strong>Reason:</strong> <span id="cdReason"></span></p>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6 class="text-danger">Before Correction</h6>
                            <table class="table table-sm table-bordered">
                                <tbody id="cdBeforeTotals"></tbody>
                            </table>
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="cdBeforeItems"></tbody>
                            </table>
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Payment Method</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="cdBeforePayments"></tbody>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-success">After Correction</h6>
                            <table class="table table-sm table-bordered">
                                <tbody id="cdAfterTotals"></tbody>
                            </table>
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Product</th>
                                        <th class="text-end">Qty</th>
                                        <th class="text-end">Price</th>
                                        <th class="text-end">Total</th>
                                    </tr>
                                </thead>
                                <tbody id="cdAfterItems"></tbody>
                            </table>
                            <table class="table table-sm table-bordered">
                                <thead>
                                    <tr>
                                        <th>Payment Method</th>
                                        <th class="text-end">Amount</th>
                                    </tr>
                                </thead>
                                <tbody id="cdAfterPayments"></tbody>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'date_created',name:'date_created',sortable:false},
                        {data:'order_no',name:'order_no',sortable:false},
                        {data:'branch',name:'branch',sortable:false},
                        {data:'corrected_by',name:'corrected_by',sortable:false},
                        {data:'reason',name:'reason',sortable:false},
                        {data:'old_total',name:'old_total',sortable:false,className:'text-end'},
                        {data:'new_total',name:'new_total',sortable:false,className:'text-end'},
                        {data:'difference',name:'difference',sortable:false,className:'text-end'},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'order-correction-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'order_correction_table',
        'variable' => 'order_correction_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),causer_id:$('#causer_id').val()",
    ])

    <script>
        function money(v) {
            v = parseFloat(v || 0);
            if (isNaN(v)) v = 0;
            return v.toFixed(2);
        }

        function escapeHtml(str) {
            return $('<div>').text(str == null ? '' : str).html();
        }

        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                causer_id: $('#causer_id').val() || '',
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
            $('#causer_id').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTableorder_correction_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/order-correction-report/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#grand_corrections_display').text(response.grand_corrections ?? '-');
                    $('#grand_old_total_display').text(response.grand_old_total ?? '-');
                    $('#grand_new_total_display').text(response.grand_new_total ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/order-correction-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/order-correction-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/order-correction-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/order-correction-report/export-csv');
        });

        function renderDiffTotals(containerId, snapshot) {
            var $body = $(containerId).empty();
            var rows = [
                ['Subtotal', snapshot.subtotal],
                ['Discount', snapshot.discount_amount],
                ['Tax', snapshot.tax_amount],
                ['Total', snapshot.total],
                ['Paid', snapshot.paid_amount],
            ];
            rows.forEach(function(r) {
                $body.append('<tr><th>' + r[0] + '</th><td class="text-end">' + money(r[1]) + '</td></tr>');
            });
        }

        function renderDiffItems(containerId, snapshot) {
            var $body = $(containerId).empty();
            var details = (snapshot && snapshot.details) || [];
            if (!details.length) {
                $body.append('<tr><td colspan="4" class="text-center text-muted">No items</td></tr>');
                return;
            }
            details.forEach(function(line) {
                $body.append(
                    '<tr>' +
                        '<td>' + escapeHtml(line.product_name) + (line.product_variation_name ? ' (' + escapeHtml(line.product_variation_name) + ')' : '') + '</td>' +
                        '<td class="text-end">' + escapeHtml(line.quantity) + '</td>' +
                        '<td class="text-end">' + money(line.unit_price) + '</td>' +
                        '<td class="text-end">' + money(line.total) + '</td>' +
                    '</tr>'
                );
            });
        }

        function renderDiffPayments(containerId, snapshot) {
            var $body = $(containerId).empty();
            var payments = (snapshot && snapshot.payments) || [];
            if (!payments.length) {
                $body.append('<tr><td colspan="2" class="text-center text-muted">No payments</td></tr>');
                return;
            }
            payments.forEach(function(payment) {
                $body.append(
                    '<tr>' +
                        '<td>' + escapeHtml(payment.payment_method_name) + '</td>' +
                        '<td class="text-end">' + money(payment.amount) + '</td>' +
                    '</tr>'
                );
            });
        }

        $('#order_correction_table').on('click', 'a[data-action="view-diff"]', function(e) {
            e.preventDefault();

            var tr = $(this).closest('tr');
            var rowData = order_correction_table.row(tr).data();
            if (!rowData) {
                return;
            }

            var oldValues = rowData.old_values || {};
            var newValues = rowData.new_values || {};

            $('#cdOrderNo').text(rowData.order_no || '');
            $('#cdReason').text(newValues.reason || '-');

            renderDiffTotals('#cdBeforeTotals', oldValues);
            renderDiffTotals('#cdAfterTotals', newValues);
            renderDiffItems('#cdBeforeItems', oldValues);
            renderDiffItems('#cdAfterItems', newValues);
            renderDiffPayments('#cdBeforePayments', oldValues);
            renderDiffPayments('#cdAfterPayments', newValues);

            bootstrap.Modal.getOrCreateInstance(document.getElementById('correctionDiffModal')).show();
        });
    </script>
@endsection
