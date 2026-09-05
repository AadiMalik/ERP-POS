@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.trial_balance') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.trial-balance.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.trial-balance.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.trial-balance.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.trial-balance.export-csv')
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
                            <label class="form-label">{{ __('common.type') }}</label>
                            <select id="account_type_id" class="form-select">
                                <option value="">--All Account Types--</option>
                                @foreach ($account_types as $item)
                                    <option value="{{ $item->account_type_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.period') }}</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="trial_balance_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_account') }}</th>
                                <th>{{ __('reports.col_account_type') }}</th>
                                <th class="text-end">Opening Debit</th>
                                <th class="text-end">Opening Credit</th>
                                <th class="text-end">Period Debit</th>
                                <th class="text-end">Period Credit</th>
                                <th class="text-end">Closing Debit</th>
                                <th class="text-end">Closing Credit</th>
                            </tr>
                        </thead>
                        <tfoot>
                            <tr>
                                <th colspan="2">Total</th>
                                <th class="text-end" id="total_opening_debit_display">-</th>
                                <th class="text-end" id="total_opening_credit_display">-</th>
                                <th class="text-end" id="total_period_debit_display">-</th>
                                <th class="text-end" id="total_period_credit_display">-</th>
                                <th class="text-end" id="total_closing_debit_display">-</th>
                                <th class="text-end" id="total_closing_credit_display">-</th>
                            </tr>
                        </tfoot>
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
                        {data:'account_type',name:'account_type',sortable:false},
                        {data:'opening_debit',name:'opening_debit',sortable:false,className:'text-end'},
                        {data:'opening_credit',name:'opening_credit',sortable:false,className:'text-end'},
                        {data:'period_debit',name:'period_debit',sortable:false,className:'text-end'},
                        {data:'period_credit',name:'period_credit',sortable:false,className:'text-end'},
                        {data:'closing_debit',name:'closing_debit',sortable:false,className:'text-end'},
                        {data:'closing_credit',name:'closing_credit',sortable:false,className:'text-end'}",
        'route' => 'trial-balance/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'trial_balance_table',
        'variable' => 'trial_balance_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),account_type_id:$('#account_type_id').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                account_type_id: $('#account_type_id').val() || '',
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
            $('#account_type_id').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTabletrial_balance_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/trial-balance/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#total_opening_debit_display').text(response.total_opening_debit ?? '-');
                    $('#total_opening_credit_display').text(response.total_opening_credit ?? '-');
                    $('#total_period_debit_display').text(response.total_period_debit ?? '-');
                    $('#total_period_credit_display').text(response.total_period_credit ?? '-');
                    $('#total_closing_debit_display').text(response.total_closing_debit ?? '-');
                    $('#total_closing_credit_display').text(response.total_closing_credit ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/trial-balance/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/trial-balance/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/trial-balance/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/trial-balance/export-csv');
        });
    </script>
@endsection
