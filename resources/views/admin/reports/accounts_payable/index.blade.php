@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Accounts Payable Report
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
                    @canAccess('reports.accounts-payable.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.accounts-payable.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.accounts-payable.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.accounts-payable.export-csv')
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
                            <label class="form-label">Supplier</label>
                            <select id="supplier_id" class="form-select">
                                <option value="">--All Suppliers--</option>
                                @foreach ($suppliers as $item)
                                    <option value="{{ $item->supplier_id }}"
                                        {{ request('supplier_id') == $item->supplier_id ? 'selected' : '' }}>
                                        {{ $item->code ?? '' }} {{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="">--Outstanding Only--</option>
                                <option value="all">All</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date Basis</label>
                            <select id="date_basis" class="form-select">
                                <option value="invoice_date">Invoice Date</option>
                                <option value="due_date">Due Date</option>
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
                            <strong>Total Invoiced:</strong> <span id="total_invoiced_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>Total Paid:</strong> <span id="total_paid_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning mb-0">
                            <strong>Total Outstanding:</strong> <span id="total_outstanding_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="accounts_payable_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Supplier Name</th>
                                <th>Purchase Number</th>
                                <th>Invoice Number</th>
                                <th>Invoice Date</th>
                                <th>Due Date</th>
                                <th class="text-end">Invoiced Amount</th>
                                <th class="text-end">Paid Amount</th>
                                <th class="text-end">Remaining Balance</th>
                                <th>Status</th>
                                <th>Payment References</th>
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
                        {data:'supplier_name',name:'supplier_name',sortable:false},
                        {data:'purchase_no',name:'purchase_no',sortable:false},
                        {data:'invoice_number',name:'invoice_number',sortable:false},
                        {data:'invoice_date',name:'invoice_date',sortable:false},
                        {data:'due_date',name:'due_date',sortable:false},
                        {data:'invoiced_amount',name:'invoiced_amount',sortable:false,className:'text-end'},
                        {data:'paid_amount',name:'paid_amount',sortable:false,className:'text-end'},
                        {data:'remaining_balance',name:'remaining_balance',sortable:false,className:'text-end'},
                        {data:'status',name:'status',sortable:false},
                        {data:'payment_references',name:'payment_references',sortable:false}",
        'route' => 'accounts-payable/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'accounts_payable_table',
        'variable' => 'accounts_payable_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),supplier_id:$('#supplier_id').val(),status:$('#status').val(),date_basis:$('#date_basis').val(),bucket:'" .
            request('bucket') .
            "',as_of_date:'" .
            request('as_of_date') .
            "',aging_basis:'" .
            request('aging_basis') .
            "'",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                supplier_id: $('#supplier_id').val() || '',
                status: $('#status').val() || '',
                date_basis: $('#date_basis').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
                bucket: '{{ request('bucket') }}',
                as_of_date: '{{ request('as_of_date') }}',
                aging_basis: '{{ request('aging_basis') }}',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id').select2();
            $('#supplier_id').select2();
            $('#status').select2();
            $('#date_basis').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTableaccounts_payable_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/accounts-payable/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#total_invoiced_display').text(response.total_invoiced ?? '-');
                    $('#total_paid_display').text(response.total_paid ?? '-');
                    $('#total_outstanding_display').text(response.total_outstanding ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/accounts-payable/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/accounts-payable/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/accounts-payable/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/accounts-payable/export-csv');
        });
    </script>
@endsection
