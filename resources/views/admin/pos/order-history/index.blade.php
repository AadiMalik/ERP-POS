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
                {{ __('pos.order_history_title') }}
            </h4>
            <div class="d-flex gap-2 flex-wrap">
                <button type="button" id="toggleSummary" class="btn btn-outline-success">
                    <i class="fa fa-receipt"></i>
                    {{ __('pos.sales_summary') }}
                </button>
                <a href="{{ route('pos-screen') }}" class="btn btn-outline-primary">
                    <i class="fa fa-arrow-left"></i>
                    {{ __('pos.back_to_pos') }}
                </a>
            </div>
        </div>

        <div id="summarySection" class="card mb-3" style="display:none;">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">{{ __('pos.sales_summary_filters') }}</h6>
                <button type="button" id="printSummaryBtn" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-print"></i>
                    {{ __('pos.print_thermal_summary') }}
                </button>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6 col-md-3">
                        <div class="pos-oh-summary-tile p-3 text-center">
                            <div class="pos-oh-summary-label">{{ __('pos.total_orders') }}</div>
                            <div class="fs-4 fw-bold" id="sumTotalOrders">0</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="pos-oh-summary-tile p-3 text-center">
                            <div class="pos-oh-summary-label">{{ __('pos.total_sales') }}</div>
                            <div class="fs-4 fw-bold" id="sumTotalSales">0.00</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="pos-oh-summary-tile p-3 text-center">
                            <div class="pos-oh-summary-label">{{ __('pos.total_paid') }}</div>
                            <div class="fs-4 fw-bold" id="sumTotalPaid">0.00</div>
                        </div>
                    </div>
                    <div class="col-6 col-md-3">
                        <div class="pos-oh-summary-tile p-3 text-center">
                            <div class="pos-oh-summary-label">{{ __('pos.total_due') }}</div>
                            <div class="fs-4 fw-bold" id="sumTotalDue">0.00</div>
                        </div>
                    </div>
                </div>
                <div class="row g-3 mt-1">
                    <div class="col-md-6">
                        <h6 class="small text-muted">{{ __('pos.by_order_status') }}</h6>
                        <div id="sumByStatus" class="d-flex flex-wrap gap-2"></div>
                    </div>
                    <div class="col-md-6">
                        <h6 class="small text-muted">{{ __('pos.by_payment_method') }}</h6>
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
                        {{ __('common.filters') }}
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
                            <label class="form-label">{{ __('common.order_no') }}</label>
                            <input type="text" id="daily_order_id" class="form-control" placeholder="{{ __('common.order_no') }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.start_date') }}</label>
                            <input type="date" id="sale_date_start" class="form-control"
                                value="{{ $is_order_taker ? $today : '' }}" @if ($is_order_taker) readonly @endif>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.end_date') }}</label>
                            <input type="date" id="sale_date_end" class="form-control"
                                value="{{ $is_order_taker ? $today : '' }}" @if ($is_order_taker) readonly @endif>
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
                            <label class="form-label">{{ __('common.order_type') }}</label>
                            <select id="order_type_id" class="form-select">
                                <option value="">{{ __('pos.all_order_types') }}</option>
                                @foreach ($order_types as $item)
                                    <option value="{{ $item->order_type_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.order_status') }}</label>
                            <select id="status" class="form-select">
                                <option value="">{{ __('common.all_statuses') }}</option>
                                @foreach ($statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.payment_status') }}</label>
                            <select id="payment_status" class="form-select">
                                <option value="">{{ __('pos.all_payment_statuses') }}</option>
                                @foreach ($payment_statuses as $value => $label)
                                    <option value="{{ $value }}">{{ $label ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.payment_method') }}</label>
                            <select id="payment_method_id" class="form-select">
                                <option value="">{{ __('pos.all_payment_methods') }}</option>
                                @foreach ($payment_methods as $item)
                                    <option value="{{ $item->payment_method_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('pos.order_taker') }}</label>
                            <select id="cashier_id" class="form-select">
                                <option value="">{{ __('pos.all_order_takers') }}</option>
                                @foreach ($cashiers as $item)
                                    <option value="{{ $item->id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                            </select>
                        </div>
                        @if (!$is_fixed_context)
                            <div class="col-md-3">
                                <label class="form-label">{{ __('common.branch') }}</label>
                                <select id="branch_id" class="form-select">
                                    <option value="">{{ __('common.all_branches') }}</option>
                                    @foreach ($branches as $item)
                                        <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
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
                    <table id="order_history_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('common.order_no') }}</th>
                                <th>{{ __('pos.order_datetime') }}</th>
                                <th>{{ __('common.order_type') }}</th>
                                <th>{{ __('pos.order_source') }}</th>
                                <th>{{ __('pos.order_taker') }}</th>
                                <th>{{ __('common.customer') }}</th>
                                <th>{{ __('common.order_status') }}</th>
                                <th>{{ __('common.payment_status') }}</th>
                                <th>{{ __('common.payment_method') }}</th>
                                <th>{{ __('pos.sale_type') }}</th>
                                <th>{{ __('common.total') }}</th>
                                <th>{{ __('common.paid') }}</th>
                                <th>{{ __('common.due') }}</th>
                                <th>{{ __('common.action') }}</th>
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
                    <h5 class="modal-title">{{ __('common.order') }} <span id="odOrderNo"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-2 mb-3">
                        <div class="col-md-4"><strong>{{ __('pos.order_datetime') }}:</strong> <span id="odOrderDate"></span></div>
                        <div class="col-md-4"><strong>{{ __('common.order_type') }}:</strong> <span id="odOrderType"></span></div>
                        <div class="col-md-4"><strong>{{ __('pos.order_source') }}:</strong> <span id="odOrderSource"></span></div>
                        <div class="col-md-4"><strong>{{ __('pos.order_taker') }}:</strong> <span id="odCashier"></span></div>
                        <div class="col-md-4"><strong>{{ __('common.customer') }}:</strong> <span id="odCustomer"></span></div>
                        <div class="col-md-4"><strong>{{ __('common.status') }}:</strong> <span id="odStatus"></span></div>
                        <div class="col-md-4"><strong>{{ __('common.payment_status') }}:</strong> <span id="odPaymentStatus"></span></div>
                        <div class="col-md-4"><strong>{{ __('common.payment_method') }}:</strong> <span id="odPaymentMethod"></span></div>
                    </div>

                    <h6 class="pos-oh-modal-section-title">{{ __('common.items') }}</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('common.product') }}</th>
                                    <th>{{ __('common.variation') }}</th>
                                    <th class="text-end">{{ __('common.qty') }}</th>
                                    <th>{{ __('common.unit') }}</th>
                                    <th class="text-end">{{ __('common.price') }}</th>
                                    <th class="text-end">{{ __('common.total') }}</th>
                                </tr>
                            </thead>
                            <tbody id="odItemsBody"></tbody>
                        </table>
                    </div>

                    <h6 class="pos-oh-modal-section-title">{{ __('pos.payments') }}</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('common.method') }}</th>
                                    <th>{{ __('common.reference_no') }}</th>
                                    <th class="text-end">{{ __('common.amount') }}</th>
                                </tr>
                            </thead>
                            <tbody id="odPaymentsBody"></tbody>
                        </table>
                    </div>

                    <h6 class="pos-oh-modal-section-title">{{ __('pos.payment_history') }}</h6>
                    <div class="table-responsive mb-3">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('common.method') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                    <th class="text-end">{{ __('common.amount') }}</th>
                                    <th class="text-end">{{ __('pos.remaining_due') }}</th>
                                </tr>
                            </thead>
                            <tbody id="odCustomerPaymentsBody"></tbody>
                        </table>
                    </div>

                    <div class="row justify-content-end">
                        <div class="col-md-5">
                            <table class="table table-sm table-borderless mb-0">
                                <tr><td>{{ __('common.subtotal') }}</td><td class="text-end" id="odSubtotal"></td></tr>
                                <tr><td>{{ __('common.discount') }}</td><td class="text-end" id="odDiscount"></td></tr>
                                <tr><td>{{ __('common.tax') }}</td><td class="text-end" id="odTax"></td></tr>
                                <tr class="fw-bold"><td>{{ __('common.total') }}</td><td class="text-end" id="odTotal"></td></tr>
                                <tr><td>{{ __('common.paid') }}</td><td class="text-end" id="odPaid"></td></tr>
                                <tr><td>{{ __('common.due') }}</td><td class="text-end" id="odDue"></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" id="odReceivePaymentBtn" class="btn btn-primary d-none">
                        <i class="fa fa-money-bill"></i> {{ __('pos.receive_payment') }}
                    </button>
                    <a href="#" id="odThermalPrintBtn" target="_blank" class="btn btn-outline-info">
                        <i class="fa fa-receipt"></i> {{ __('pos.thermal_print') }}
                    </a>
                    <a href="#" id="odReorderBtn" target="_blank" class="btn btn-outline-success">
                        <i class="fa fa-rotate-right"></i> {{ __('pos.reorder') }}
                    </a>
                    <a href="#" id="odCorrectBtn" class="btn btn-outline-primary d-none">
                        <i class="fa fa-pencil"></i> {{ __('pos.correct') }}
                    </a>
                    <button type="button" class="btn btn-outline-primary" data-bs-dismiss="modal">{{ __('common.close') }}</button>
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
                    <h5 class="modal-title">{{ __('pos.receive_payment_title') }} - <span id="rpOrderNo"></span></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('common.close') }}"></button>
                </div>
                <div class="modal-body">
                    <div class="alert alert-info py-2 mb-3">
                        {{ __('pos.remaining_due') }}: <strong id="rpDueAmount">0.00</strong>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.amount') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="rpAmount">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.payment_method') }} <span class="text-danger">*</span></label>
                        <select class="form-select" id="rpPaymentMethod">
                            <option value="cash">{{ __('common.cash') }}</option>
                            <option value="card">{{ __('common.card') }}</option>
                            <option value="bank_transfer">{{ __('common.bank_transfer') }}</option>
                            <option value="cheque">{{ __('common.cheque') }}</option>
                            <option value="online">{{ __('common.online_payment') }}</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.reference_no') }}</label>
                        <input type="text" class="form-control" id="rpReferenceNo">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" id="rpSubmitBtn" class="btn btn-primary">{{ __('pos.receive_payment') }}</button>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        window.i18n_pos = @json(trans('pos'));
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
