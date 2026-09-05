@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('purchases.title') }}
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>

                </div>
                <a href="{{ url('admin/purchase/create') }}" class="btn btn-primary rounded-pill">
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
                        {{-- <div class="col-md-3">
                            <label class="form-label">{{ __('common.branch') }}</label>
                            <select id="branch_id" class="form-select">
                                <option value="">{{ __('common.all_branches') }}</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($branch as $item)
                                        <option value="{{ $item->branch_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div> --}}
                        <div class="col-md-3">
                            <label class="form-label">{{ __('purchases.purchase_request') }}</label>
                            <select id="purchase_request_id" class="form-select">
                                <option value="">{{ __('purchases.all_purchase_requests') }}</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($purchase_requests as $item)
                                        <option value="{{ $item->purchase_request_id }}">{{ $item->purchase_request_no ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.supplier') }}</label>
                            <select id="supplier_id" class="form-select">
                                <option value="">{{ __('common.all_suppliers') }}</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($suppliers as $item)
                                        <option value="{{ $item->supplier_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.warehouse') }}</label>
                            <select id="warehouse_id" class="form-select">
                                <option value="">{{ __('common.all_warehouses') }}</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($warehouses as $item)
                                        <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
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
                    <table id="purchase_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('purchases.purchase_no') }}</th>
                                <th>{{ __('purchases.purchase_date') }}</th>
                                <th>{{ __('purchases.request_no') }}</th>
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
        $__i18nPurchases = [
            'all_branches' => __('common.all_branches'),
            'all_suppliers' => __('common.all_suppliers'),
            'all_warehouses' => __('common.all_warehouses'),
            'all_purchase_requests' => __('purchases.all_purchase_requests'),
            'something_went_wrong' => __('common.something_went_wrong'),
        ];
    @endphp
    <script>
        window.i18n_purchases = @json($__i18nPurchases);
    </script>
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'purchase_no',name:'purchase_no'},
                        {data:'purchase_date',name:'purchase_date'},
                        {data:'purchase_request_no',name:'purchase_request_no',sortable:false},
                        {data:'supplier',name:'supplier',sortable:false},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'total_products',name:'total_products',sortable:false},
                        {data:'total',name:'total'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'purchase/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'purchase_table',
        'variable' => 'purchase_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),purchase_request_id:$('#purchase_request_id').val(),supplier_id:$('#supplier_id').val(),warehouse_id:$('#warehouse_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#purchase_request_id').select2();
            $('#supplier_id').select2();
            $('#warehouse_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTablepurchase_table();
        });
        //status
        $(document).on('change', '.change-status', function() {

            let purchase_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            $.ajax({
                url: url_local + "/admin/purchase/change-status", // route
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    purchase_id: purchase_id,
                    status: status
                },
                success: function(response) {

                    successMessage(response.Message);
                    initDataTablepurchase_table();
                },
                error: function() {

                    errorMessage(error.Message || window.i18n_purchases?.something_went_wrong || 'Something went wrong.');
                    initDataTablepurchase_table();
                    // Previous value restore
                    select.val(select.data('old'));
                }
            });

        });
        //delete
        deleteRecord({
            buttonClass: "#deletePurchase",
            url: url_local + "/admin/purchase",

            tableCallback: function() {
                initDataTablepurchase_table();
            }
        });

        $('#business_id').on('change', function() {

            let business_id = $(this).val();

            // Reset dropdowns
            $('#branch_id').html('<option value="">' + (window.i18n_purchases?.all_branches || '--All Branches--') + '</option>');
            $('#supplier_id').html('<option value="">' + (window.i18n_purchases?.all_suppliers || '--All Suppliers--') + '</option>');
            $('#warehouse_id').html('<option value="">' + (window.i18n_purchases?.all_warehouses || '--All Warehouses--') + '</option>');
            $('#purchase_request_id').html('<option value="">' + (window.i18n_purchases?.all_purchase_requests || '--All Purchase Requests--') + '</option>');

            if (!business_id) {
                return;
            }

            Promise.all([
                    ajaxRequest({
                        url: url_local + '/admin/branch/by-business/' + business_id,
                        data: {}
                    }),
                    ajaxRequest({
                        url: url_local + '/admin/supplier/by-business/' + business_id,
                        data: {}
                    }),
                    ajaxRequest({
                        url: url_local + '/admin/warehouse/by-business/' + business_id,
                        data: {}
                    }),
                    ajaxRequest({
                        url: url_local + '/admin/purchase-request/by-business/' + business_id,
                        data: {}
                    })
                ])
                .then(([branchRes, supplierRes, warehouseRes, productRes, purchaseRequestRes]) => {

                    // Branches
                    let branchOptions = '<option value="">' + (window.i18n_purchases?.all_branches || '--All Branches--') + '</option>';
                    $.each(branchRes.Data, function(_, item) {
                        branchOptions += `<option value="${item.branch_id}">
                                ${item.code} ${item.name}
                              </option>`;
                    });
                    $('#branch_id').html(branchOptions);

                    // Suppliers
                    let supplierOptions = '<option value="">' + (window.i18n_purchases?.all_suppliers || '--All Suppliers--') + '</option>';
                    $.each(supplierRes.Data, function(_, item) {
                        supplierOptions += `<option value="${item.supplier_id}">
                                    ${item.code} ${item.name}
                                </option>`;
                    });
                    $('#supplier_id').html(supplierOptions);

                    // Warehouses
                    let warehouseOptions = '<option value="">' + (window.i18n_purchases?.all_warehouses || '--All Warehouses--') + '</option>';
                    $.each(warehouseRes.Data, function(_, item) {
                        warehouseOptions += `<option value="${item.warehouse_id}">
                                    ${item.name}
                                </option>`;
                    });
                    $('#warehouse_id').html(warehouseOptions);

                    // Purchase Requests
                    let purchaseRequestOptions = '<option value="">' + (window.i18n_purchases?.all_purchase_requests || '--All Purchase Requests--') + '</option>';
                    $.each(purchaseRequestRes.Data, function(_, item) {
                        purchaseRequestOptions += `<option value="${item.purchase_request_id}">
                                    ${item.purchase_request_no}
                                </option>`;
                    });
                    $('#purchase_request_id').html(purchaseRequestOptions);

                })
                .catch((err) => {
                    errorMessage(err.Message ?? (window.i18n_purchases?.something_went_wrong || 'Something went wrong.'));
                });

        });
    </script>
@endsection
