@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.cash_bank_ledger') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.cash-bank-ledger.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.cash-bank-ledger.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.cash-bank-ledger.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.cash-bank-ledger.export-csv')
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
                            <label class="form-label">Cash / Bank Account</label>
                            <select id="account_id" class="form-select">
                                <option value="">--All Cash & Bank Accounts--</option>
                                @foreach ($accounts as $item)
                                    <option value="{{ $item->account_id }}">{{ $item->code ?? '' }}
                                        {{ $item->name ?? '' }}
                                    </option>
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
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0">
                            <strong>Total Receipts:</strong> <span id="total_receipts_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-warning mb-0">
                            <strong>Total Payments:</strong> <span id="total_payments_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="cash_bank_ledger_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_account') }}</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('reports.col_voucher_type') }}</th>
                                <th>{{ __('reports.col_jv_number') }}</th>
                                <th>Reference Number</th>
                                <th>{{ __('reports.col_narration') }}</th>
                                <th class="text-end">Receipt</th>
                                <th class="text-end">Payment</th>
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
                        {data:'account',name:'account',sortable:false},
                        {data:'voucher_date',name:'voucher_date',sortable:false},
                        {data:'voucher_type',name:'voucher_type',sortable:false},
                        {data:'voucher_number',name:'voucher_number',sortable:false},
                        {data:'reference_number',name:'reference_number',sortable:false},
                        {data:'description',name:'description',sortable:false},
                        {data:'receipt',name:'receipt',sortable:false,className:'text-end'},
                        {data:'payment',name:'payment',sortable:false,className:'text-end'},
                        {data:'running_balance',name:'running_balance',sortable:false,className:'text-end'}",
        'route' => 'cash-bank-ledger/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'cash_bank_ledger_table',
        'variable' => 'cash_bank_ledger_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),account_id:$('#account_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                account_id: $('#account_id').val() || '',
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
            $('#account_id').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTablecash_bank_ledger_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/cash-bank-ledger/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#total_receipts_display').text(response.total_receipts ?? '-');
                    $('#total_payments_display').text(response.total_payments ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/cash-bank-ledger/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/cash-bank-ledger/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/cash-bank-ledger/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/cash-bank-ledger/export-csv');
        });
    </script>
@endsection
