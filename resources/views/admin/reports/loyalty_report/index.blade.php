@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.loyalty_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.loyalty-report.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.loyalty-report.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.loyalty-report.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.loyalty-report.export-csv')
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
                            <label class="form-label">Order No</label>
                            <input type="text" id="order_no" class="form-control" placeholder="Order #">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                <option value="draft">Draft</option>
                                <option value="hold">Hold</option>
                                <option value="posted">Posted</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="void">Void</option>
                                <option value="returned">Returned</option>
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
                            <strong>Total Points Redeemed:</strong> <span id="grand_points_used_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success mb-0">
                            <strong>Total Loyalty Discount:</strong> <span id="grand_discount_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-info mb-0">
                            <strong>Total Points Earned:</strong> <span id="grand_points_earned_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="loyalty_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('reports.col_order_no') }}</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('common.customer') }}</th>
                                <th class="text-end">Order Total</th>
                                <th class="text-end">Points Redeemed</th>
                                <th class="text-end">Loyalty Discount</th>
                                <th class="text-end">Points Earned</th>
                                <th>{{ __('common.status') }}</th>
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
                        {data:'order_no',name:'order_no',sortable:false},
                        {data:'order_date',name:'order_date',sortable:false},
                        {data:'customer_name',name:'customer_name',sortable:false},
                        {data:'total',name:'total',sortable:false,className:'text-end'},
                        {data:'loyalty_points_used',name:'loyalty_points_used',sortable:false,className:'text-end'},
                        {data:'loyalty_discount_amount',name:'loyalty_discount_amount',sortable:false,className:'text-end'},
                        {data:'loyalty_points_earned',name:'loyalty_points_earned',sortable:false,className:'text-end'},
                        {data:'status',name:'status',sortable:false},
                        {data:'action',name:'action',sortable:false,searchable:false}",
        'route' => 'loyalty-report/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'loyalty_report_table',
        'variable' => 'loyalty_report_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),user_id:$('#user_id').val(),order_no:$('#order_no').val(),status:$('#status').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                user_id: $('#user_id').val() || '',
                order_no: $('#order_no').val() || '',
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
            $('#business_id').select2();
            $('#branch_id').select2();
            $('#user_id').select2();
            $('#status').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTableloyalty_report_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/loyalty-report/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#grand_points_used_display').text(response.grand_points_used ?? '-');
                    $('#grand_discount_display').text(response.grand_discount ?? '-');
                    $('#grand_points_earned_display').text(response.grand_points_earned ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/loyalty-report/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/loyalty-report/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/loyalty-report/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/loyalty-report/export-csv');
        });
    </script>
@endsection
