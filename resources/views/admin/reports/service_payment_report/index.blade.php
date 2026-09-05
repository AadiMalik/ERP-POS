@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.service_payment_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.service-payment-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.service-payment-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.service-payment-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.service-payment-report.export-csv')
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
                            <label class="form-label">{{ __('common.branch') }}</label>
                            <select id="branch_id" class="form-select">
                                <option value="">{{ __('common.all_branches') }}</option>
                                @foreach ($branches as $item)
                                    <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('reports.payment_type') }}</label>
                            <select id="payment_type" class="form-select">
                                @foreach ($payment_type_options as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
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

                <div class="row g-3 p-4 pb-0">
                    <div class="col-md-4">
                        <div class="alert alert-secondary mb-0">
                            <strong>Total Receipts:</strong> <span id="total_receipts_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>Total Payments:</strong> <span id="total_payments_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-warning mb-0">
                            <strong>Net Cash Flow:</strong> <span id="net_cash_flow_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="service_payment_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('reports.col_type') }}</th>
                                <th>{{ __('reports.col_payment_no_alt') }}</th>
                                <th>{{ __('reports.col_party') }}</th>
                                <th>{{ __('reports.col_reference') }}</th>
                                <th>{{ __('reports.col_method') }}</th>
                                <th>{{ __('reports.col_account') }}</th>
                                <th class="text-end">{{ __('reports.col_tax') }}</th>
                                <th class="text-end">{{ __('reports.col_discount') }}</th>
                                <th class="text-end">Net Amount</th>
                                <th>Posted By</th>
                                <th>{{ __('common.action') }}</th>
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
                        {data:'payment_date',name:'payment_date',sortable:false},
                        {data:'payment_type',name:'payment_type',sortable:false},
                        {data:'payment_no',name:'payment_no',sortable:false},
                        {data:'party_name',name:'party_name',sortable:false},
                        {data:'reference_no',name:'reference_no',sortable:false},
                        {data:'payment_method',name:'payment_method',sortable:false},
                        {data:'payment_account',name:'payment_account',sortable:false},
                        {data:'tax_amount',name:'tax_amount',sortable:false,className:'text-end'},
                        {data:'discount_amount',name:'discount_amount',sortable:false,className:'text-end'},
                        {data:'net_amount',name:'net_amount',sortable:false,className:'text-end'},
                        {data:'postedby',name:'postedby',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'service-payment-report/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'service_payment_report_table',
        'variable' => 'service_payment_report_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),payment_type:$('#payment_type').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                payment_type: $('#payment_type').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id, #branch_id, #payment_type').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTableservice_payment_report_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/service-payment-report/data',
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
                    $('#net_cash_flow_display').text(response.net_cash_flow ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/service-payment-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/service-payment-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/service-payment-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/service-payment-report/export-csv');
        });
    </script>
@endsection
