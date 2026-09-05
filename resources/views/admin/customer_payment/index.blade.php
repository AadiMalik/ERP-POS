@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('customer_payments.title') }}
        </h4>
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
                        'importExportModule' => 'customer-payment',
                        'importExportLabel' => __('customer_payments.title'),
                        'importExportRefreshFn' => 'initDataTablecustomer_payment_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                    <a href="{{ url('admin/customer-payment/create') }}" class="btn btn-primary rounded-pill">
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
                            <label class="form-label">{{ __('common.customer') }}</label>
                            <select id="user_id" class="form-select">
                                <option value="">{{ __('customer_payments.select_customer') }}</option>
                                @foreach ($customers as $item)
                                    <option value="{{ $item->user_id }}">{{ isset($item->code) ? $item->code : '' }}
                                        {{ $item->user->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('customer_payments.payment_method') }}</label>
                            <select id="payment_method" class="form-select">
                                <option value="">{{ __('common.all') }}</option>
                                <option value="cash">{{ __('payment_methods.cash') }}</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online Payment</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.date') }}</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                {{ __('common.search') }}
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                {{ __('common.reset') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="customer_payment_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Payment No.</th>
                                <th>Payment Date</th>
                                <th>Customer</th>
                                <th>Reference Order</th>
                                <th>Method</th>
                                <th>{{ __('common.amount') }}</th>
                                <th>Net Payment</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('common.business') }}</th>
                                <th>{{ __('common.action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
    @include('admin.partials.import-export-modal')
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'payment_no',name:'payment_no'},
                        {data:'payment_date',name:'payment_date'},
                        {data:'customer',name:'customer',sortable:false},
                        {data:'order_no',name:'order_no',sortable:false},
                        {data:'payment_method',name:'payment_method',sortable:false},
                        {data:'amount',name:'amount'},
                        {data:'net_amount',name:'net_amount'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'customer-payment/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'customer_payment_table',
        'variable' => 'customer_payment_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),user_id:$('#user_id').val(),payment_method:$('#payment_method').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#user_id').select2();
            $('#payment_method').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTablecustomer_payment_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let customer_payment_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/customer-payment/change-status",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    customer_payment_id: customer_payment_id,
                    status: status
                },
                success: function(response) {

                    successMessage(response.Message);
                    initDataTablecustomer_payment_table();
                },
                error: function(error) {

                    errorMessage(error.responseJSON?.Message || 'Something went wrong.');
                    initDataTablecustomer_payment_table();
                    select.val(select.data('old'));
                }
            });

        });
        //delete
        deleteRecord({
            buttonClass: "#deleteCustomerPayment",
            url: url_local + "/admin/customer-payment",

            tableCallback: function() {
                initDataTablecustomer_payment_table();
            }
        });
    </script>
@endsection
