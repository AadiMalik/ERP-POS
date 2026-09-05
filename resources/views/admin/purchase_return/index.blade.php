@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('purchase_returns.title') }}
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>

                </div>
                <a href="{{ url('admin/purchase-return/create') }}" class="btn btn-primary rounded-pill">
                    <i class="fa fa-plus"></i>
                    {{ __('common.add_new') }}
                </a>
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
                            <label class="form-label">{{ __('purchase_returns.return_type') }}</label>
                            <select id="return_type" class="form-select">
                                <option value="">{{ __('common.all_types') }}</option>
                                @foreach ($return_types as $value => $label)
                                    <option value="{{ $value }}">{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.supplier') }}</label>
                            <select id="supplier_id" class="form-select">
                                <option value="">{{ __('common.all_suppliers') }}</option>
                                @foreach ($suppliers as $item)
                                    <option value="{{ $item->supplier_id }}">{{ isset($item->code) ? $item->code : '' }}
                                        {{ $item->name ?? '' }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.warehouse') }}</label>
                            <select id="warehouse_id" class="form-select">
                                <option value="">{{ __('common.all_warehouses') }}</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}
                                    </option>
                                @endforeach
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
                    <table id="purchase_return_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('purchase_returns.return_no') }}</th>
                                <th>{{ __('purchase_returns.return_date') }}</th>
                                <th>{{ __('common.type') }}</th>
                                <th>{{ __('purchase_returns.source_no') }}</th>
                                <th>{{ __('common.supplier') }}</th>
                                <th>{{ __('common.warehouse') }}</th>
                                <th>{{ __('common.products') }}</th>
                                <th>{{ __('common.total') }}</th>
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
@endsection
@section('js')
    @php
        $__i18nPurchaseReturns = [
            'something_went_wrong' => __('common.something_went_wrong'),
        ];
    @endphp
    <script>
        window.i18n_purchase_returns = @json($__i18nPurchaseReturns);
    </script>
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'purchase_return_no',name:'purchase_return_no'},
                        {data:'purchase_return_date',name:'purchase_return_date'},
                        {data:'return_type',name:'return_type',sortable:false},
                        {data:'source_no',name:'source_no',sortable:false},
                        {data:'supplier',name:'supplier',sortable:false},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'total_products',name:'total_products',sortable:false},
                        {data:'total',name:'total'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'purchase-return/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'purchase_return_table',
        'variable' => 'purchase_return_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),return_type:$('#return_type').val(),supplier_id:$('#supplier_id').val(),warehouse_id:$('#warehouse_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#return_type').select2();
            $('#supplier_id').select2();
            $('#warehouse_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTablepurchase_return_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let purchase_return_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/purchase-return/change-status", // route
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    purchase_return_id: purchase_return_id,
                    status: status
                },
                success: function(response) {

                    successMessage(response.Message);
                    initDataTablepurchase_return_table();
                },
                error: function() {

                    errorMessage(error.Message || window.i18n_purchase_returns?.something_went_wrong || 'Something went wrong.');
                    initDataTablepurchase_return_table();
                    // Previous value restore
                    select.val(select.data('old'));
                }
            });

        });
        //delete
        deleteRecord({
            buttonClass: "#deletePurchaseReturn",
            url: url_local + "/admin/purchase-return",

            tableCallback: function() {
                initDataTablepurchase_return_table();
            }
        });
    </script>
@endsection
