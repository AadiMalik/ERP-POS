@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Purchase Service Report
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
                    @canAccess('reports.service-purchase-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.service-purchase-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.service-purchase-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.service-purchase-report.export-csv')
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
                            <label class="form-label">Transaction Type</label>
                            <select id="transaction_type" class="form-select">
                                @foreach ($transaction_type_options as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Group By</label>
                            <select id="group_by" class="form-select">
                                @foreach ($group_by_options as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
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
                    <div class="col-md-4">
                        <div class="alert alert-secondary mb-0">
                            <strong>Purchase Amount:</strong> <span id="grand_purchase_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>Purchase Return Amount:</strong> <span id="grand_purchase_return_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning mb-0">
                            <strong>Net Amount:</strong> <span id="grand_net_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="service_purchase_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Group</th>
                                <th class="text-end">Transactions</th>
                                <th class="text-end">Purchase Amount</th>
                                <th class="text-end">Purchase Return Amount</th>
                                <th class="text-end">Net Amount</th>
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
                        {data:'transaction_count',name:'transaction_count',sortable:false,className:'text-end'},
                        {data:'purchase_amount',name:'purchase_amount',sortable:false,className:'text-end'},
                        {data:'purchase_return_amount',name:'purchase_return_amount',sortable:false,className:'text-end'},
                        {data:'net_amount',name:'net_amount',sortable:false,className:'text-end'}",
        'route' => 'service-purchase-report/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'service_purchase_report_table',
        'variable' => 'service_purchase_report_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),supplier_id:$('#supplier_id').val(),transaction_type:$('#transaction_type').val(),group_by:$('#group_by').val(),status:$('#status').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                supplier_id: $('#supplier_id').val() || '',
                transaction_type: $('#transaction_type').val() || '',
                group_by: $('#group_by').val() || 'none',
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
            $('#business_id, #branch_id, #supplier_id, #transaction_type, #group_by, #status').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTableservice_purchase_report_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/service-purchase-report/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#grand_purchase_display').text(response.grand_purchase ?? '-');
                    $('#grand_purchase_return_display').text(response.grand_purchase_return ?? '-');
                    $('#grand_net_display').text(response.grand_net ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/service-purchase-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/service-purchase-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/service-purchase-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/service-purchase-report/export-csv');
        });
    </script>
@endsection
