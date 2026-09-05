@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.customer_ledger') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.customer-ledger.print')
                    <a href="javascript:void(0);" id="btn_print" target="_blank" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.customer-ledger.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" target="_blank" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.customer-ledger.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.customer-ledger.export-csv')
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
                            <label class="form-label">{{ __('common.customer') }} <span class="text-danger">*</span></label>
                            <select id="user_id" class="form-select">
                                <option value="">{{ __('common.select_customer') }}</option>
                                @foreach ($customers as $item)
                                    <option value="{{ $item->user_id }}">{{ $item->code ?? '' }}
                                        {{ $item->user->name ?? '' }}
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
                    <table id="customer_ledger_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_document_date') }}</th>
                                <th>Voucher Date</th>
                                <th>{{ __('reports.col_voucher_type') }}</th>
                                <th>Voucher Number</th>
                                <th>Reference Number</th>
                                <th>{{ __('common.description') }}</th>
                                <th class="text-end">{{ __('common.debit') }}</th>
                                <th class="text-end">{{ __('common.credit') }}</th>
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
        'route' => 'customer-ledger/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'customer_ledger_table',
        'variable' => 'customer_ledger_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),user_id:$('#user_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                user_id: $('#user_id').val() || '',
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
            $('#user_id').select2();
        });

        $('#search_btn').click(function() {
            initDataTablecustomer_ledger_table();
            refreshBalanceSummary();
        });

        function refreshBalanceSummary() {
            let params = currentReportParams();

            if (!params.user_id) {
                $('#opening_balance_display, #closing_balance_display, #totals_display').text('-');
                return;
            }

            $.ajax({
                url: url_local + '/admin/reports/customer-ledger/data',
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

        $('#user_id').on('change', refreshBalanceSummary);

        $('#btn_print').click(function() {
            if (!$('#user_id').val()) {
                errorMessage('Please select a customer first.');
                return;
            }
            window.open(buildReportUrl('/admin/reports/customer-ledger/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            if (!$('#user_id').val()) {
                errorMessage('Please select a customer first.');
                return;
            }
            window.open(buildReportUrl('/admin/reports/customer-ledger/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            if (!$('#user_id').val()) {
                errorMessage('Please select a customer first.');
                return;
            }
            window.location.href = buildReportUrl('/admin/reports/customer-ledger/export');
        });
        $('#btn_csv').click(function() {
            if (!$('#user_id').val()) {
                errorMessage('Please select a customer first.');
                return;
            }
            window.location.href = buildReportUrl('/admin/reports/customer-ledger/export-csv');
        });
    </script>
@endsection
