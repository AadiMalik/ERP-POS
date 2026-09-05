@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            {{ __('orders.title') }}
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
                        'importExportModule' => 'order',
                        'importExportLabel' => __('orders.title'),
                        'importExportRefreshFn' => 'initDataTableorder_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">{{ __('orders.order_id') }}</label>
                            <input type="text" id="order_id" class="form-control" placeholder="{{ __('orders.order_id') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('orders.daily_order_id') }}</label>
                            <input type="text" id="daily_order_id" class="form-control" placeholder="{{ __('orders.daily_order_id') }}">
                        </div>
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
                            <label class="form-label">{{ __('common.branch') }}</label>
                            <select id="branch_id" class="form-select">
                                <option value="">{{ __('common.all_branches') }}</option>
                                @foreach ($branches as $item)
                                    <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.warehouse') }}</label>
                            <select id="warehouse_id" class="form-select">
                                <option value="">{{ __('common.all_warehouses') }}</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('orders.register') }}</label>
                            <select id="register_id" class="form-select">
                                <option value="">{{ __('orders.all_registers') }}</option>
                                @foreach ($registers as $item)
                                    <option value="{{ $item->pos_register_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('orders.cashier') }}</label>
                            <select id="cashier_id" class="form-select">
                                <option value="">{{ __('orders.all_cashiers') }}</option>
                                @foreach ($cashiers as $item)
                                    <option value="{{ $item->id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.customer') }}</label>
                            <select id="customer_id" class="form-select">
                                <option value="">{{ __('common.all_customers') }}</option>
                                @foreach ($customers as $item)
                                    <option value="{{ $item->user_id }}">{{ $item->user->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('orders.order_type') }}</label>
                            <select id="order_type_id" class="form-select">
                                <option value="">{{ __('orders.all_order_types') }}</option>
                                @foreach ($order_types as $item)
                                    <option value="{{ $item->order_type_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('orders.order_source') }}</label>
                            <select id="order_source_id" class="form-select">
                                <option value="">{{ __('orders.all_order_sources') }}</option>
                                @foreach ($order_sources as $item)
                                    <option value="{{ $item->order_source_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('orders.payment_method') }}</label>
                            <select id="payment_method_id" class="form-select">
                                <option value="">{{ __('orders.all_payment_methods') }}</option>
                                @foreach ($payment_methods as $item)
                                    <option value="{{ $item->payment_method_id }}">{{ $item->name ?? '' }}</option>
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
                            <label class="form-label">{{ __('orders.order_date') }}</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('orders.sale_date_from') }}</label>
                            <input type="date" id="sale_date_start" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('orders.sale_date_to') }}</label>
                            <input type="date" id="sale_date_end" class="form-control">
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
                    <table id="order_table" class="table datatables">
                        <thead>
                            <tr>
                                <th></th>
                                <th>{{ __('orders.daily_order_id') }}</th>
                                <th>{{ __('orders.order_date') }}</th>
                                <th>{{ __('common.customer') }}</th>
                                <th>{{ __('common.total') }}</th>
                                <th>{{ __('common.due') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('common.action') }}</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        @include('admin.partials.import-export-modal')

        <!-- Cancel Order modal -->
        <div class="modal fade" id="cancelOrderModal" tabindex="-1">
            <div class="modal-dialog">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('orders.cancel_order') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="cancel_order_id">
                        <p class="mb-2">{{ __('orders.cancel_order_hint') }}</p>
                        <div class="mb-3">
                            <label class="form-label">{{ __('orders.cancellation_reason') }} <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="cancel_order_reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
                        <button type="button" class="btn btn-danger" id="confirmCancelOrder">{{ __('orders.cancel_order') }}</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @php
        $__i18nOrders = [
            'all_branches' => __('common.all_branches'),
            'all_warehouses' => __('common.all_warehouses'),
            'all_registers' => __('orders.all_registers'),
            'all_cashiers' => __('orders.all_cashiers'),
            'all_customers' => __('common.all_customers'),
            'all_order_types' => __('orders.all_order_types'),
            'all_order_sources' => __('orders.all_order_sources'),
            'all_payment_methods' => __('orders.all_payment_methods'),
            'something_went_wrong' => __('common.something_went_wrong'),
            'cancellation_reason_required' => __('orders.cancellation_reason_required'),
            'unable_cancel' => __('orders.unable_cancel'),
        ];
    @endphp
    <script>
        window.i18n_orders = @json($__i18nOrders);
    </script>
    <script src="{{ asset('public/assets/js/admin/order.js') }}"></script>
    @include('admin.partials.datatable', [
        'columns' => "
                        {data: null , defaultContent: ''},
                        {data:'daily_order_id',name:'daily_order_id'},
                        {data:'order_date',name:'order_date'},
                        {data:'customer',name:'customer',sortable:false},
                        {data:'total',name:'total'},
                        {data:'due_amount',name:'due_amount',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'order/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'order_table',
        'variable' => 'order_table',
        'datefilter' => true,
        'detail' => true,
        'order' => "[[1, 'asc']]",
        'params' =>
            "order_id:$('#order_id').val(),daily_order_id:$('#daily_order_id').val(),business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),warehouse_id:$('#warehouse_id').val(),register_id:$('#register_id').val(),cashier_id:$('#cashier_id').val(),customer_id:$('#customer_id').val(),order_type_id:$('#order_type_id').val(),order_source_id:$('#order_source_id').val(),payment_method_id:$('#payment_method_id').val(),status:$('#status').val(),sale_date_start:$('#sale_date_start').val(),sale_date_end:$('#sale_date_end').val()",
    ])
@endsection
