@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Supplier Ledger Report
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
                    @canAccess('reports.supplier-ledger.print')
                    <a href="javascript:void(0);" id="btn_print" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> Print
                    </a>
                    @endcanAccess
                    @canAccess('reports.supplier-ledger.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> PDF
                    </a>
                    @endcanAccess
                    @canAccess('reports.supplier-ledger.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> Excel
                    </a>
                    @endcanAccess
                    @canAccess('reports.supplier-ledger.export-csv')
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
                            <label class="form-label">Supplier <span class="text-danger">*</span></label>
                            <select id="supplier_id" class="form-select">
                                <option value="">--Select Supplier--</option>
                                @foreach ($suppliers as $item)
                                    <option value="{{ $item->supplier_id }}">{{ $item->code ?? '' }}
                                        {{ $item->name ?? '' }}
                                    </option>
                                @endforeach
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

                <div class="row g-3 p-4 pb-0" id="balance_summary">
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>Opening Balance:</strong> <span id="opening_balance_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-secondary mb-0">
                            <strong>Total Debit / Credit:</strong> <span id="totals_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning mb-0">
                            <strong>Closing Balance:</strong> <span id="closing_balance_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="supplier_ledger_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Document Date</th>
                                <th>Voucher Date</th>
                                <th>Voucher Type</th>
                                <th>Voucher Number</th>
                                <th>Reference Number</th>
                                <th>Description</th>
                                <th class="text-end">Debit</th>
                                <th class="text-end">Credit</th>
                                <th class="text-end">Running Balance</th>
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
                        {data:'document_date',name:'document_date',sortable:false},
                        {data:'voucher_date',name:'voucher_date',sortable:false},
                        {data:'voucher_type',name:'voucher_type',sortable:false},
                        {data:'voucher_number',name:'voucher_number',sortable:false},
                        {data:'reference_number',name:'reference_number',sortable:false},
                        {data:'description',name:'description',sortable:false},
                        {data:'debit',name:'debit',sortable:false,className:'text-end'},
                        {data:'credit',name:'credit',sortable:false,className:'text-end'},
                        {data:'running_balance',name:'running_balance',sortable:false,className:'text-end'}",
        'route' => 'supplier-ledger/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'supplier_ledger_table',
        'variable' => 'supplier_ledger_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),supplier_id:$('#supplier_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                supplier_id: $('#supplier_id').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }

        function buildReportUrl(path) {
            let params = currentReportParams();
            let query = $.param(params);
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id').select2();
            $('#supplier_id').select2();
        });

        $('#search_btn').click(function() {
            initDataTablesupplier_ledger_table();
            refreshBalanceSummary();
        });

        function refreshBalanceSummary() {
            let params = currentReportParams();

            if (!params.supplier_id) {
                $('#opening_balance_display, #closing_balance_display, #totals_display').text('-');
                return;
            }

            $.ajax({
                url: url_local + '/admin/reports/supplier-ledger/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, params),
                success: function(response) {
                    $('#opening_balance_display').text(response.opening_balance ?? '-');
                    $('#closing_balance_display').text(response.closing_balance ?? '-');
                    $('#totals_display').text((response.total_debit ?? '-') + ' / ' + (response.total_credit ?? '-'));
                }
            });
        }

        $('#supplier_id').on('change', refreshBalanceSummary);

        $('#btn_print').click(function() {
            if (!$('#supplier_id').val()) {
                errorMessage('Please select a supplier first.');
                return;
            }
            window.open(buildReportUrl('/admin/reports/supplier-ledger/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            if (!$('#supplier_id').val()) {
                errorMessage('Please select a supplier first.');
                return;
            }
            window.open(buildReportUrl('/admin/reports/supplier-ledger/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            if (!$('#supplier_id').val()) {
                errorMessage('Please select a supplier first.');
                return;
            }
            window.location.href = buildReportUrl('/admin/reports/supplier-ledger/export');
        });
        $('#btn_csv').click(function() {
            if (!$('#supplier_id').val()) {
                errorMessage('Please select a supplier first.');
                return;
            }
            window.location.href = buildReportUrl('/admin/reports/supplier-ledger/export-csv');
        });
    </script>
@endsection
