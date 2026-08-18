@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Expense Details
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>
                </div>
                <a href="{{ url('admin/expense/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    Add New
                </a>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="business_id" class="form-select">
                                    <option value="">--All Businesses--</option>
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
                                <option value="">--All Categories--</option>
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
                            <label class="form-label">Status</label>
                            <select id="status" class="form-select">
                                <option value="">--All Statuses--</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                Search
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="expense_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Expense No.</th>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Amount</th>
                                <th>OT / User</th>
                                <th>Session</th>
                                <th>Branch</th>
                                <th>Source</th>
                                <th>Status</th>
                                <th>Business</th>
                                <th>Action</th>
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
                        {data:'amount',name:'amount'},
                        {data:'user',name:'user',sortable:false},
                        {data:'session',name:'session',sortable:false},
                        {data:'branch',name:'branch',sortable:false},
                        {data:'source',name:'source',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'expense/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'expense_table',
        'variable' => 'expense_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),expense_category_id:$('#expense_category_id').val(),source:$('#source').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#expense_category_id').select2();
            $('#source').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTableexpense_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let expense_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/expense/change-status",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    expense_id: expense_id,
                    status: status
                },
                success: function(response) {

                    if (response.Success === false) {
                        errorMessage(response.Message || 'Something went wrong.');
                        initDataTableexpense_table();
                        select.val(select.data('old'));
                        return;
                    }

                    successMessage(response.Message);
                    initDataTableexpense_table();
                },
                error: function(error) {

                    errorMessage(error.responseJSON?.Message || 'Something went wrong.');
                    initDataTableexpense_table();
                    select.val(select.data('old'));
                }
            });

        });
        //delete
        deleteRecord({
            buttonClass: "#deleteExpense",
            url: url_local + "/admin/expense",

            tableCallback: function() {
                initDataTableexpense_table();
            }
        });
    </script>
@endsection
