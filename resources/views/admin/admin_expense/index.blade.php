@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('admin_expenses.title') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @include('admin.partials.import-export-buttons', [
                        'importExportModule' => 'admin-expense',
                        'importExportLabel' => __('admin_expenses.title'),
                        'importExportRefreshFn' => 'initDataTableadmin_expense_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                    <a href="{{ url('admin/admin-expense/create') }}" class="btn btn-primary rounded-pill">
                        <i class="fa fa-plus"></i>
                        {{ __('common.add_new') }}
                    </a>
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">{{ __('common.business') }}</label>
                                <select id="business_id" class="form-select">
                                    <option value="">{{ __('common.all_businesses') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select id="expense_category_id" class="form-select">
                                <option value="">{{ __('common.all_categories') }}</option>
                                @foreach ($categories as $item)
                                    <option value="{{ $item->expense_category_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label ?? '' }}</option>
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
                <div class="table-responsive p-4">
                    <table id="admin_expense_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Expense No.</th>
                                <th>{{ __('common.date') }}</th>
                                <th>Category</th>
                                <th>{{ __('common.amount') }}</th>
                                <th>Branch</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('common.business') }}</th>
                                <th>{{ __('common.action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        @include('admin.partials.import-export-modal')
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'expense_no',name:'expense_no'},
                        {data:'expense_date',name:'expense_date'},
                        {data:'category',name:'category',sortable:false},
                        {data:'amount',name:'amount'},
                        {data:'branch',name:'branch',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'admin-expense/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'admin_expense_table',
        'variable' => 'admin_expense_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),expense_category_id:$('#expense_category_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#expense_category_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTableadmin_expense_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let expense_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/admin-expense/change-status",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    expense_id: expense_id,
                    status: status
                },
                success: function(response) {

                    if (response.Success === false) {
                        errorMessage(response.Message || 'Something went wrong.');
                        initDataTableadmin_expense_table();
                        select.val(select.data('old'));
                        return;
                    }

                    successMessage(response.Message);
                    initDataTableadmin_expense_table();
                },
                error: function(error) {

                    errorMessage(error.responseJSON?.Message || 'Something went wrong.');
                    initDataTableadmin_expense_table();
                    select.val(select.data('old'));
                }
            });

        });
        //delete
        deleteRecord({
            buttonClass: "#deleteExpense",
            url: url_local + "/admin/admin-expense",

            tableCallback: function() {
                initDataTableadmin_expense_table();
            }
        });
    </script>
@endsection
