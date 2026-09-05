@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.customer_aging') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.customer-aging.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.customer-aging.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.customer-aging.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.customer-aging.export-csv')
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
                        <div class="col-md-2">
                            <label class="form-label">{{ __('common.as_of_date') }}</label>
                            <input type="date" id="as_of_date" class="form-control"
                                value="{{ now()->format('Y-m-d') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">{{ __('reports.aging_basis') }}</label>
                            <select id="aging_basis" class="form-select">
                                <option value="">--System Default--</option>
                                <option value="due_date">Due Date</option>
                                <option value="invoice_date">Sale Date</option>
                            </select>
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 p-4 pb-0">
                    <div class="col-md-4">
                        <div class="alert alert-warning mb-0">
                            <strong>Grand Total Outstanding:</strong> <span id="grand_total_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="customer_aging_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Customer Name</th>
                                <th class="text-end">Current</th>
                                <th class="text-end">1-30 Days</th>
                                <th class="text-end">31-60 Days</th>
                                <th class="text-end">61-90 Days</th>
                                <th class="text-end">91-120 Days</th>
                                <th class="text-end">120+ Days</th>
                                <th class="text-end">Total Outstanding</th>
                                <th>Last Payment Date</th>
                                <th class="text-end">Total Balance</th>
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
                        {data:'bucket_current',name:'bucket_current',sortable:false,className:'text-end'},
                        {data:'bucket_1_30',name:'bucket_1_30',sortable:false,className:'text-end'},
                        {data:'bucket_31_60',name:'bucket_31_60',sortable:false,className:'text-end'},
                        {data:'bucket_61_90',name:'bucket_61_90',sortable:false,className:'text-end'},
                        {data:'bucket_91_120',name:'bucket_91_120',sortable:false,className:'text-end'},
                        {data:'bucket_120_plus',name:'bucket_120_plus',sortable:false,className:'text-end'},
                        {data:'total_outstanding',name:'total_outstanding',sortable:false,className:'text-end'},
                        {data:'last_payment_date',name:'last_payment_date',sortable:false},
                        {data:'total_balance',name:'total_balance',sortable:false,className:'text-end'}",
        'route' => 'customer-aging/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'customer_aging_table',
        'variable' => 'customer_aging_table',
        'params' =>
            "business_id:$('#business_id').val(),user_id:$('#user_id').val(),as_of_date:$('#as_of_date').val(),aging_basis:$('#aging_basis').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                user_id: $('#user_id').val() || '',
                as_of_date: $('#as_of_date').val() || '',
                aging_basis: $('#aging_basis').val() || '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id').select2();
            $('#user_id').select2();
            $('#aging_basis').select2();
            refreshGrandTotal();
        });

        $('#search_btn').click(function() {
            initDataTablecustomer_aging_table();
            refreshGrandTotal();
        });

        function refreshGrandTotal() {
            $.ajax({
                url: url_local + '/admin/reports/customer-aging/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#grand_total_display').text(response.grand_total_outstanding ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/customer-aging/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/customer-aging/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/customer-aging/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/customer-aging/export-csv');
        });
    </script>
@endsection
