@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('stock_taking.title') }}
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>

                </div>
                <a href="{{ url('admin/stock-taking/create') }}" class="btn btn-primary rounded-pill">
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
                    <table id="stock_taking_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('stock_taking.stock_taking_no') }}</th>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('common.warehouse') }}</th>
                                <th>{{ __('common.products') }}</th>
                                <th>{{ __('stock_taking.difference_value') }}</th>
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
        $__i18nStockTaking = [
            'something_went_wrong' => __('common.something_went_wrong'),
            'stock_changed_title' => __('stock_taking.stock_changed_title'),
            'stock_changed_text' => __('stock_taking.stock_changed_text'),
            'approve_anyway' => __('stock_taking.approve_anyway'),
        ];
    @endphp
    <script>
        window.i18n_stock_taking = @json($__i18nStockTaking);
    </script>
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'stock_taking_no',name:'stock_taking_no'},
                        {data:'stock_taking_date',name:'stock_taking_date'},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'total_products',name:'total_products',sortable:false},
                        {data:'total_difference_value',name:'total_difference_value'},
                        {data:'status',name:'status',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'stock-taking/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'stock_taking_table',
        'variable' => 'stock_taking_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),warehouse_id:$('#warehouse_id').val(),status:$('#status').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#warehouse_id').select2();
            $('#status').select2();
        });
        $('#search_btn').click(function() {
            initDataTablestock_taking_table();
        });
        //status
        function submitStockTakingStatus(stock_taking_id, status, select, confirmDrift) {
            $.ajax({
                url: url_local + "/admin/stock-taking/change-status", // route
                type: 'POST',
                data: {
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    stock_taking_id: stock_taking_id,
                    status: status,
                    confirm_drift: confirmDrift ? 1 : 0
                },
                success: function(response) {

                    // Approving can come back as a warning rather than a
                    // completed change if live stock has moved since the
                    // count was taken - ask the approver to confirm before
                    // actually posting.
                    if (response.Data && response.Data.requires_confirmation) {
                        let lines = (response.Data.drift || []).map(function(d) {
                            return d.product_name + ': counted against ' + d.counted_system_quantity +
                                ', now ' + d.current_system_quantity;
                        }).join('\n');

                        Swal.fire({
                            title: window.i18n_stock_taking?.stock_changed_title || 'Stock has changed since this count was taken',
                            text: (window.i18n_stock_taking?.stock_changed_text || 'The final adjustment will use the CURRENT stock quantity, not what was originally counted:') + '\n' + lines,
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: window.i18n_stock_taking?.approve_anyway || 'Approve anyway'
                        }).then((result) => {
                            if (result.isConfirmed) {
                                submitStockTakingStatus(stock_taking_id, status, select, true);
                            } else {
                                select.val(select.data('old'));
                            }
                        });
                        return;
                    }

                    successMessage(response.Message);
                    initDataTablestock_taking_table();
                },
                error: function() {

                    errorMessage(error.Message || window.i18n_stock_taking?.something_went_wrong || 'Something went wrong.');
                    initDataTablestock_taking_table();
                    // Previous value restore
                    select.val(select.data('old'));
                }
            });
        }

        $(document).on('change', '.change-status', function() {

            let stock_taking_id = $(this).data('id');
            let status = $(this).val();
            let select = $(this);

            submitStockTakingStatus(stock_taking_id, status, select, false);
        });
        //delete
        deleteRecord({
            buttonClass: "#deleteStockTaking",
            url: url_local + "/admin/stock-taking",

            tableCallback: function() {
                initDataTablestock_taking_table();
            }
        });
    </script>
@endsection
