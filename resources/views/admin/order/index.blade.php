@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Orders
        </h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @include('admin.partials.import-export-buttons', [
                        'importExportModule' => 'order',
                        'importExportLabel' => 'Orders',
                        'importExportRefreshFn' => 'initDataTableorder_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Order ID</label>
                            <input type="text" id="order_id" class="form-control" placeholder="Order ID">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Daily Order ID</label>
                            <input type="text" id="daily_order_id" class="form-control" placeholder="Daily Order ID">
                        </div>
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
                            <label class="form-label">Branch</label>
                            <select id="branch_id" class="form-select">
                                <option value="">--All Branches--</option>
                                @foreach ($branches as $item)
                                    <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Warehouse</label>
                            <select id="warehouse_id" class="form-select">
                                <option value="">--All Warehouses--</option>
                                @foreach ($warehouses as $item)
                                    <option value="{{ $item->warehouse_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Register</label>
                            <select id="register_id" class="form-select">
                                <option value="">--All Registers--</option>
                                @foreach ($registers as $item)
                                    <option value="{{ $item->pos_register_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Cashier</label>
                            <select id="cashier_id" class="form-select">
                                <option value="">--All Cashiers--</option>
                                @foreach ($cashiers as $item)
                                    <option value="{{ $item->id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Customer</label>
                            <select id="customer_id" class="form-select">
                                <option value="">--All Customers--</option>
                                @foreach ($customers as $item)
                                    <option value="{{ $item->user_id }}">{{ $item->user->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Order Type</label>
                            <select id="order_type_id" class="form-select">
                                <option value="">--All Order Types--</option>
                                @foreach ($order_types as $item)
                                    <option value="{{ $item->order_type_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Order Source</label>
                            <select id="order_source_id" class="form-select">
                                <option value="">--All Order Sources--</option>
                                @foreach ($order_sources as $item)
                                    <option value="{{ $item->order_source_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Method</label>
                            <select id="payment_method_id" class="form-select">
                                <option value="">--All Payment Methods--</option>
                                @foreach ($payment_methods as $item)
                                    <option value="{{ $item->payment_method_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
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
                            <label class="form-label">Order Date</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sale Date From</label>
                            <input type="date" id="sale_date_start" class="form-control">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Sale Date To</label>
                            <input type="date" id="sale_date_end" class="form-control">
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
                    <table id="order_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Daily Order ID</th>
                                <th>Order Date</th>
                                <th>Sale Date</th>
                                <th>Business</th>
                                <th>Branch</th>
                                <th>Warehouse</th>
                                <th>Register</th>
                                <th>Cashier</th>
                                <th>Customer</th>
                                <th>Order Type</th>
                                <th>Order Source</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Payment Method</th>
                                <th>Sale Type</th>
                                <th>Status</th>
                                <th>Action</th>
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
                        <h5 class="modal-title">Cancel Order</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" id="cancel_order_id">
                        <p class="mb-2">This order will be marked as Cancelled. This action is recorded in the order's status history.</p>
                        <div class="mb-3">
                            <label class="form-label">Cancellation Reason <span class="text-danger">*</span></label>
                            <textarea class="form-control" id="cancel_order_reason" rows="3" required></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                        <button type="button" class="btn btn-danger" id="confirmCancelOrder">Cancel Order</button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script src="{{ asset('public/assets/js/admin/order.js') }}"></script>
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'daily_order_id',name:'daily_order_id'},
                        {data:'order_date',name:'order_date'},
                        {data:'sale_date',name:'sale_date',sortable:false},
                        {data:'business',name:'business',sortable:false},
                        {data:'branch',name:'branch',sortable:false},
                        {data:'warehouse',name:'warehouse',sortable:false},
                        {data:'register',name:'register',sortable:false},
                        {data:'cashier',name:'cashier',sortable:false},
                        {data:'customer',name:'customer',sortable:false},
                        {data:'order_type',name:'order_type',sortable:false},
                        {data:'order_source',name:'order_source',sortable:false},
                        {data:'total',name:'total'},
                        {data:'paid_amount',name:'paid_amount',sortable:false},
                        {data:'due_amount',name:'due_amount',sortable:false},
                        {data:'payment_method',name:'payment_method',sortable:false},
                        {data:'sale_type',name:'sale_type',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'order/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'order_table',
        'variable' => 'order_table',
        'datefilter' => true,
        'params' =>
            "order_id:$('#order_id').val(),daily_order_id:$('#daily_order_id').val(),business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),warehouse_id:$('#warehouse_id').val(),register_id:$('#register_id').val(),cashier_id:$('#cashier_id').val(),customer_id:$('#customer_id').val(),order_type_id:$('#order_type_id').val(),order_source_id:$('#order_source_id').val(),payment_method_id:$('#payment_method_id').val(),status:$('#status').val(),sale_date_start:$('#sale_date_start').val(),sale_date_end:$('#sale_date_end').val()",
    ])
@endsection
