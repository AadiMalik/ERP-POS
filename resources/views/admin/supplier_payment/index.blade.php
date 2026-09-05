@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('supplier_payments.title') }}
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
                        'importExportModule' => 'supplier-payment',
                        'importExportLabel' => __('supplier_payments.title'),
                        'importExportRefreshFn' => 'initDataTablesupplier_payment_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                    <a href="{{ url('admin/supplier-payment/create') }}" class="btn btn-primary rounded-pill">
                        <i class="fa fa-plus"></i>
                        Add New
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
                            <label class="form-label">{{ __('common.supplier') }}</label>
                            <select id="supplier_id" class="form-select">
                                <option value="">--All Suppliers--</option>
                                @foreach ($suppliers as $item)
                                    <option value="{{ $item->supplier_id }}">{{ isset($item->code) ? $item->code : '' }}
                                        {{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Method</label>
                            <select id="payment_method" class="form-select">
                                <option value="">--All Methods--</option>
                                <option value="cash">Cash</option>
                                <option value="bank_transfer">Bank Transfer</option>
                                <option value="cheque">Cheque</option>
                                <option value="online">Online Payment</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">--All Statuses--</option>
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
                                Search
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="supplier_payment_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Payment No.</th>
                                <th>Payment Date</th>
                                <th>Supplier</th>
                                <th>Reference Purchase</th>
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
        @include('admin.partials.import-export-modal')
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'payment_no',name:'payment_no'},
                        {data:'payment_date',name:'payment_date'},
                        {data:'supplier',name:'supplier',sortable:false},
                        {data:'purchase_no',name:'purchase_no',sortable:false},
                        {data:'payment_method',name:'payment_method',sortable:false},
                        {data:'amount',name:'amount'},
                        {data:'net_amount',name:'net_amount'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'supplier-payment/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'supplier_payment_table',
        'variable' => 'supplier_payment_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),supplier_id:$('#supplier_id').val(),payment_method:$('#payment_method').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#supplier_id').select2();
            $('#payment_method').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTablesupplier_payment_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let supplier_payment_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/supplier-payment/change-status",
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    supplier_payment_id: supplier_payment_id,
                    status: status
                },
                success: function(response) {

                    successMessage(response.Message);
                    initDataTablesupplier_payment_table();
                },
                error: function(error) {

                    errorMessage(error.responseJSON?.Message || 'Something went wrong.');
                    initDataTablesupplier_payment_table();
                    select.val(select.data('old'));
                }
            });

        });
        //delete
        deleteRecord({
            buttonClass: "#deleteSupplierPayment",
            url: url_local + "/admin/supplier-payment",

            tableCallback: function() {
                initDataTablesupplier_payment_table();
            }
        });
    </script>
@endsection
