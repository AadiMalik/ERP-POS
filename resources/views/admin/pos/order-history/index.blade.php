@php
    $today = now()->format('Y-m-d');
@endphp
@extends('layouts.pos')
@section('css')
    <link rel="stylesheet" href="{{ asset('public/assets/css/admin/pos-screen.css') }}">
    <style>
        /* Order History is a scrolling page inside the POS's fixed-height
           content wrapper (see layouts/pos.blade.php) - this wrapper owns
           its own scrollbar instead of the whole POS chrome scrolling. */
        .pos-oh-wrapper {
            height: 100%;
            overflow-y: auto;
            padding: clamp(0.85rem, 2vw, 1.25rem) clamp(0.85rem, 2vw, 1.25rem) 2rem;
        }

        .pos-oh-summary-tile {
            border: 1px solid var(--pos-border, #eceef1);
            border-left: 3px solid var(--pos-primary, #3833C8);
            border-radius: 0.5rem;
            background: #fff;
        }

        .pos-oh-summary-tile .pos-oh-summary-label {
            font-size: 0.72rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: var(--pos-muted, #8592a3);
            font-weight: 700;
        }

        #odItemsBody td,
        #odPaymentsBody td {
            vertical-align: middle;
        }

        .pos-oh-modal-section-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: .03em;
            color: var(--pos-muted, #8592a3);
            font-weight: 700;
            margin-bottom: 0.5rem;
        }

        @media (max-width: 575.98px) {
            .pos-oh-wrapper .card-header {
                flex-wrap: wrap;
                gap: 0.5rem;
            }
        }
    </style>
@endsection
@section('content')
    <div class="pos-oh-wrapper">
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 py-2 mb-3">
            <h4 class="fw-bold mb-0">
                Order History
            </h4>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" id="toggleSummary" class="btn btn-outline-success">
                    <i class="fa fa-receipt"></i>
                    Sales Summary
                </button>
                <a href="{{ route('pos-screen') }}" class="btn btn-outline-primary">
                    <i class="fa fa-arrow-left"></i>
                    Back to POS
                </a>
            </div>
        </div>

        <div id="summarySection" class="card mb-3" style="display:none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Sales Summary <small class="text-muted">(current filters)</small></h6>
                <button type="button" id="printSummaryBtn" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-print"></i>
                    Print Thermal Summary
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="pos-oh-summary-tile p-3 text-center">
                            <div class="pos-oh-summary-label">Total Orders</div>
                            <div class="fs-4 fw-bold" id="sumTotalOrders">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="pos-oh-summary-tile p-3 text-center">
                            <div class="pos-oh-summary-label">Total Sales</div>
                            <div class="fs-4 fw-bold" id="sumTotalSales">0.00</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="pos-oh-summary-tile p-3 text-center">
                            <div class="pos-oh-summary-label">Total Paid</div>
                            <div class="fs-4 fw-bold" id="sumTotalPaid">0.00</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="pos-oh-summary-tile p-3 text-center">
                            <div class="pos-oh-summary-label">Total Due</div>
                            <div class="fs-4 fw-bold" id="sumTotalDue">0.00</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <h6 class="small text-muted">By Order Status</h6>
                        <div id="sumByStatus" class="d-flex flex-wrap gap-2"></div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="small text-muted">By Payment Method</h6>
                        <div id="sumByPaymentMethod" class="d-flex flex-wrap gap-2"></div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    @if ($is_order_taker)
                        <div class="alert alert-info py-2 mb-3">
                            <i class="fa fa-circle-info"></i>
                            As an Order Taker, you can only view today's ({{ localDate($today) }}) orders.
                        </div>
                    @endif
                    <div class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Order No</label>
                            <input type="text" id="daily_order_id" class="form-control" placeholder="Order No">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Start Date</label>
                            <input type="date" id="sale_date_start" class="form-control"
                                value="{{ $is_order_taker ? $today : '' }}" @if ($is_order_taker) readonly @endif>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">End Date</label>
                            <input type="date" id="sale_date_end" class="form-control"
                                value="{{ $is_order_taker ? $today : '' }}" @if ($is_order_taker) readonly @endif>
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
                            <label class="form-label">Order Status</label>
                            <select id="status" class="form-select">
                                <option value="">--All Statuses--</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Payment Status</label>
                            <select id="payment_status" class="form-select">
                                <option value="">--All Payment Statuses--</option>
                                @foreach ($payment_statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label ?? '' }}</option>
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
                            <label class="form-label">Order Taker</label>
                            <select id="cashier_id" class="form-select">
                                <option value="">--All Order Takers--</option>
                                @foreach ($cashiers as $item)
                                    <option value="{{ $item->id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if (!$is_fixed_context)
                            <div class="col-md-3">
                                <label class="form-label">Branch</label>
                                <select id="branch_id" class="form-select">
                                    <option value="">--All Branches--</option>
                                    @foreach ($branches as $item)
                                        <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
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
                    <table id="order_history_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Order No</th>
                                <th>Order Date/Time</th>
                                <th>Order Type</th>
                                <th>Order Source</th>
                                <th>Order Taker</th>
                                <th>Customer</th>
                                <th>Order Status</th>
                                <th>Payment Status</th>
                                <th>Payment Method</th>
                                <th>Sale Type</th>
                                <th>Total</th>
                                <th>Paid</th>
                                <th>Due</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    {{-- Order Detail modal - View opens here instead of navigating to the
         Admin Panel's order.show page, so the POS never leaves its own
         interface. --}}
    <div class="modal fade" id="orderDetailModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Order <span id="odOrderNo"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4"><strong>Date/Time:</strong> <span id="odOrderDate"></span></div>
                        <div class="col-md-4"><strong>Order Type:</strong> <span id="odOrderType"></span></div>
                        <div class="col-md-4"><strong>Order Source:</strong> <span id="odOrderSource"></span></div>
                        <div class="col-md-4"><strong>Order Taker:</strong> <span id="odCashier"></span></div>
                        <div class="col-md-4"><strong>Customer:</strong> <span id="odCustomer"></span></div>
                        <div class="col-md-4"><strong>Status:</strong> <span id="odStatus"></span></div>
                        <div class="col-md-4"><strong>Payment Status:</strong> <span id="odPaymentStatus"></span></div>
                        <div class="col-md-4"><strong>Payment Method:</strong> <span id="odPaymentMethod"></span></div>
                    </div>

                    <h6 class="pos-oh-modal-section-title">Items</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Product</th>
                                    <th>Variation</th>
                                    <th class="text-end">Qty</th>
                                    <th>Unit</th>
                                    <th class="text-end">Price</th>
                                    <th class="text-end">Total</th>
                                </tr>
                            </thead>
                            <tbody id="odItemsBody"></tbody>
                        </table>
                    </div>

                    <h6 class="pos-oh-modal-section-title">Payments</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Method</th>
                                    <th>Reference No</th>
                                    <th class="text-end">Amount</th>
                                </tr>
                            </thead>
                            <tbody id="odPaymentsBody"></tbody>
                        </table>
                    </div>

                    <h6 class="pos-oh-modal-section-title">Payment History</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                    <th class="text-end">Amount</th>
                                    <th class="text-end">Remaining Due</th>
                                </tr>
                            </thead>
                            <tbody id="odCustomerPaymentsBody"></tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td>Subtotal</td><td class="text-end" id="odSubtotal"></td></tr>
                                <tr><td>Discount</td><td class="text-end" id="odDiscount"></td></tr>
                                <tr><td>Tax</td><td class="text-end" id="odTax"></td></tr>
                                <tr class="fw-bold"><td>Total</td><td class="text-end" id="odTotal"></td></tr>
                                <tr><td>Paid</td><td class="text-end" id="odPaid"></td></tr>
                                <tr><td>Due</td><td class="text-end" id="odDue"></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="odReceivePaymentBtn" class="btn btn-primary d-none">
                        <i class="fa fa-money-bill"></i> Receive Payment
                    </button>
                    <a href="#" id="odThermalPrintBtn" target="_blank" class="btn btn-outline-info">
                        <i class="fa fa-receipt"></i> Thermal Print
                    </a>
                    <a href="#" id="odReorderBtn" target="_blank" class="btn btn-outline-success">
                        <i class="fa fa-rotate-right"></i> Reorder
                    </a>
                    <a href="#" id="odCorrectBtn" class="btn btn-outline-primary d-none">
                        <i class="fa fa-pencil"></i> Correct
                    </a>
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Receive Payment - quick capture against a credit order's remaining
         due, without leaving the POS or navigating to the full Admin
         Customer Payment page. Backed by admin/customer-payment/receive,
         which auto-posts immediately (create + post in one call) so the
         due amount/payment method update right away. --}}
    <div class="modal fade" id="receivePaymentModal" tabindex="-1" aria-hidden="true" data-bs-backdrop="static">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Receive Payment - <span id="rpOrderNo"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        Remaining Due: <strong id="rpDueAmount">0.00</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="rpAmount">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Payment Method <span class="text-danger">*</span></label>
                        <select class="form-select" id="rpPaymentMethod">
                            <option value="cash">Cash</option>
                            <option value="card">Card</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="cheque">Cheque</option>
                            <option value="online">Online Payment</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reference No.</label>
                        <input type="text" class="form-control" id="rpReferenceNo">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" id="rpSubmitBtn" class="btn btn-primary">Receive Payment</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        var IS_ORDER_TAKER = @json($is_order_taker);
        var CAN_RECEIVE_PAYMENT = @json(auth()->user()->can('customer-payment.create'));
        var CAN_CORRECT_ORDER = @json(auth()->user()->can('order.correct'));
        var ORDER_HISTORY_URLS = {
            details: "{{ url('admin/order/details') }}",
            print: "{{ url('admin/order') }}",
            pos_screen: "{{ route('pos-screen') }}",
            summary: "{{ url('admin/order/history-summary') }}",
            summary_print: "{{ route('order.history-summary.print') }}",
            receive_payment: "{{ url('admin/customer-payment/receive') }}"
        };
    </script>
    <script src="{{ asset('public/assets/js/admin/order-history.js') }}"></script>
    @include('admin.partials.datatable', [
        'columns' => "
                        {data:'daily_order_id',name:'daily_order_id'},
                        {data:'order_date',name:'order_date'},
                        {data:'order_type',name:'order_type',sortable:false},
                        {data:'order_source',name:'order_source',sortable:false},
                        {data:'cashier',name:'cashier',sortable:false},
                        {data:'customer',name:'customer',sortable:false},
                        {data:'status',name:'status',sortable:false},
                        {data:'payment_status',name:'payment_status',sortable:false},
                        {data:'payment_method',name:'payment_method',sortable:false},
                        {data:'sale_type',name:'sale_type',sortable:false},
                        {data:'total',name:'total'},
                        {data:'paid_amount',name:'paid_amount',sortable:false},
                        {data:'due_amount',name:'due_amount',sortable:false},
                        {data:'action',name:'action',sortable:false}",
        'route' => 'order/data',
        'buttons' => false,
        'pageLength' => 25,
        'class' => 'order_history_table',
        'variable' => 'order_history_table',
        'params' =>
            "daily_order_id:$('#daily_order_id').val(),sale_date_start:$('#sale_date_start').val(),sale_date_end:$('#sale_date_end').val(),customer_id:$('#customer_id').val(),order_type_id:$('#order_type_id').val(),status:$('#status').val(),payment_status:$('#payment_status').val(),payment_method_id:$('#payment_method_id').val(),cashier_id:$('#cashier_id').val(),branch_id:$('#branch_id').val(),context:'pos'",
    ])
@endsection
