@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.customer_loyalty_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.customer-loyalty-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.customer-loyalty-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.customer-loyalty-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.customer-loyalty-report.export-csv')
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
                            <label class="form-label">{{ __('common.customer') }}</label>
                            <select id="user_id" class="form-select">
                                <option value="">{{ __('common.all_customers') }}</option>
                                @foreach ($customers as $item)
                                    <option value="{{ $item->user_id }}">{{ $item->code ?? '' }}
                                        {{ $item->user->name ?? '' }}
                                    </option>
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
                    <table id="customer_loyalty_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('common.customer') }}</th>
                                <th>{{ __('reports.col_reference') }}</th>
                                <th>{{ __('reports.col_transaction_type') }}</th>
                                <th class="text-end">Points</th>
                                <th class="text-end">Monetary Value</th>
                                <th>{{ __('common.date') }}</th>
                                <th class="text-end">Balance After</th>
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
                        {data:'customer_name',name:'customer_name',sortable:false},
                        {data:'reference',name:'reference',sortable:false},
                        {data:'transaction_type',name:'transaction_type',sortable:false},
                        {data:'points',name:'points',sortable:false,className:'text-end'},
                        {data:'monetary_value',name:'monetary_value',sortable:false,className:'text-end'},
                        {data:'date_created',name:'date_created',sortable:false},
                        {data:'available_balance_after',name:'available_balance_after',sortable:false,className:'text-end'}",
        'route' => 'customer-loyalty-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'customer_loyalty_report_table',
        'variable' => 'customer_loyalty_report_table',
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
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id').select2();
            $('#user_id').select2();
        });

        $('#search_btn').click(function() {
            initDataTablecustomer_loyalty_report_table();
        });

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/customer-loyalty-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/customer-loyalty-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/customer-loyalty-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/customer-loyalty-report/export-csv');
        });
    </script>
@endsection
