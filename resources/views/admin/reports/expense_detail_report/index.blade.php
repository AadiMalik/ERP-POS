@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.expense_report') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
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
                            <label class="form-label">OT / User</label>
                            <select id="user_id" class="form-select">
                                <option value="">--All Users--</option>
                                @foreach ($users as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Admin User</label>
                            <select id="createdby_id" class="form-select">
                                <option value="">--All Admin Users--</option>
                                @foreach ($users as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">POS Session</label>
                            <select id="pos_register_session_id" class="form-select">
                                <option value="">--All Sessions--</option>
                                @foreach ($sessions as $item)
                                    <option value="{{ $item->pos_register_session_id }}">
                                        {{ $item->register->name ?? 'Register' }} - {{ $item->cashier->name ?? '' }} -
                                        {{ localDateTime($item->opening_datetime) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.category') }}</label>
                            <select id="expense_category_id" class="form-select">
                                <option value="">{{ __('common.all_categories') }}</option>
                                @foreach ($categories as $item)
                                    <option value="{{ $item->expense_category_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Source</label>
                            <select id="source" class="form-select">
                                <option value="">--All Sources--</option>
                                <option value="pos">POS</option>
                                <option value="admin">Admin</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                <option value="pending">Pending</option>
                                <option value="posted">Posted</option>
                                <option value="cancelled">Cancelled</option>
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
                        <div class="alert alert-info mb-0">
                            <strong>Total Amount:</strong> <span id="total_amount_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-success mb-0">
                            <strong>Posted Amount:</strong> <span id="posted_amount_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="alert alert-secondary mb-0">
                            <strong>Pending Amount:</strong> <span id="pending_amount_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="expense_detail_report_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Expense No.</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('reports.col_category') }}</th>
                                <th class="text-end">{{ __('common.amount') }}</th>
                                <th>{{ __('common.branch') }}</th>
                                <th>OT / User</th>
                                <th>Admin User</th>
                                <th>Session</th>
                                <th>{{ __('reports.col_source') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>Accounting (JV Account)</th>
                                <th>{{ __('common.business') }}</th>
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
                        {data:'expense_no',name:'expense_no'},
                        {data:'expense_date',name:'expense_date'},
                        {data:'category',name:'category',sortable:false},
                        {data:'amount',name:'amount',className:'text-end'},
                        {data:'branch',name:'branch',sortable:false},
                        {data:'user',name:'user',sortable:false},
                        {data:'admin_user',name:'admin_user',sortable:false},
                        {data:'session',name:'session',sortable:false},
                        {data:'source',name:'source',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'accounting',name:'accounting',sortable:false},
                        {data:'business',name:'business',sortable:false}",
        'route' => 'expense-detail-report/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'expense_detail_report_table',
        'variable' => 'expense_detail_report_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),user_id:$('#user_id').val(),createdby_id:$('#createdby_id').val(),pos_register_session_id:$('#pos_register_session_id').val(),expense_category_id:$('#expense_category_id').val(),source:$('#source').val(),status:$('#status').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                user_id: $('#user_id').val() || '',
                createdby_id: $('#createdby_id').val() || '',
                pos_register_session_id: $('#pos_register_session_id').val() || '',
                expense_category_id: $('#expense_category_id').val() || '',
                source: $('#source').val() || '',
                status: $('#status').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }

        $(document).ready(function() {
            $('#business_id, #branch_id, #user_id, #createdby_id, #pos_register_session_id, #expense_category_id, #source, #status').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTableexpense_detail_report_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/expense-detail-report/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#total_amount_display').text(response.total_amount ?? '-');
                    $('#posted_amount_display').text(response.posted_amount ?? '-');
                    $('#pending_amount_display').text(response.pending_amount ?? '-');
                }
            });
        }
    </script>
@endsection
