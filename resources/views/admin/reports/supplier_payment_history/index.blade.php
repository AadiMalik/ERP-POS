@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Supplier Payment History Report
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
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
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
                            <label class="form-label">Payment Method</label>
                            <select id="payment_method" class="form-select">
                                <option value="">--All Methods--</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online Payment</option>
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
                    <div class="col-md-4">
                        <div class="alert alert-secondary mb-0">
                            <strong>Total Net Payment:</strong> <span id="total_net_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>Total Tax:</strong> <span id="total_tax_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>Total Discount:</strong> <span id="total_discount_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="supplier_payment_history_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Payment Date</th>
                                <th>Payment No.</th>
                                <th>Supplier</th>
                                <th>Payment Method</th>
                                <th>Reference Purchase</th>
                                <th>Payment Reference No.</th>
                                <th>Bank/Cash Account</th>
                                <th class="text-end">Tax</th>
                                <th class="text-end">Discount</th>
                                <th class="text-end">Net Payment</th>
                                <th>Posted By</th>
                                <th>Status</th>
                                <th>Remarks</th>
                                <th>Action</th>
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
                        {data:'payment_date',name:'payment_date'},
                        {data:'payment_no',name:'payment_no'},
                        {data:'supplier',name:'supplier',sortable:false},
                        {data:'payment_method',name:'payment_method',sortable:false},
                        {data:'purchase_no',name:'purchase_no',sortable:false},
                        {data:'reference_no',name:'reference_no',sortable:false},
                        {data:'payment_account',name:'payment_account',sortable:false},
                        {data:'tax_amount',name:'tax_amount',sortable:false,className:'text-end'},
                        {data:'discount_amount',name:'discount_amount',sortable:false,className:'text-end'},
                        {data:'net_amount',name:'net_amount',className:'text-end'},
                        {data:'postedby',name:'postedby',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'remarks',name:'remarks',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'supplier-payment-history/data',
        'buttons' => false,
        'pageLength' => 25,
        'class' => 'supplier_payment_history_table',
        'variable' => 'supplier_payment_history_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),supplier_id:$('#supplier_id').val(),payment_method:$('#payment_method').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                supplier_id: $('#supplier_id').val() || '',
                payment_method: $('#payment_method').val() || '',
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
            $('#supplier_id').select2();
            $('#payment_method').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTablesupplier_payment_history_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/supplier-payment-history/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#total_net_display').text(response.total_net ?? '-');
                    $('#total_tax_display').text(response.total_tax ?? '-');
                    $('#total_discount_display').text(response.total_discount ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/supplier-payment-history/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/supplier-payment-history/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/supplier-payment-history/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/supplier-payment-history/export-csv');
        });
    </script>
@endsection
