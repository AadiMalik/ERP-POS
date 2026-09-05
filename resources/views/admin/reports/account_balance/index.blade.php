@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.account_balance') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.account-balance.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.account-balance.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.account-balance.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.account-balance.export-csv')
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
                    <table id="account_balance_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_account') }}</th>
                                <th class="text-end">Opening Balance</th>
                                <th class="text-end">Period Debit</th>
                                <th class="text-end">Period Credit</th>
                                <th class="text-end">Closing Balance</th>
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
                        {data:'opening_balance',name:'opening_balance',sortable:false,className:'text-end'},
                        {data:'period_debit',name:'period_debit',sortable:false,className:'text-end'},
                        {data:'period_credit',name:'period_credit',sortable:false,className:'text-end'},
                        {data:'closing_balance',name:'closing_balance',sortable:false,className:'text-end'}",
        'route' => 'account-balance/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'account_balance_table',
        'variable' => 'account_balance_table',
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
        });

        $('#search_btn').click(function() {
            initDataTableaccount_balance_table();
        });

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/account-balance/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/account-balance/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/account-balance/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/account-balance/export-csv');
        });
    </script>
@endsection
