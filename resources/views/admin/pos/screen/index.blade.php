@extends('layouts.pos')
@section('css')
    <style>
        .pos-screen-wrapper .product-search-wrap { position: relative; }
        #productSearchResults {
            position: absolute;
            left: 0;
            right: 0;
            top: 100%;
            z-index: 1050;
            max-height: 340px;
            overflow-y: auto;
            display: none;
        }
        #productSearchResults .list-group-item { cursor: pointer; }
        #cartRows input.line-qty, #cartRows input.line-price, #cartRows input.line-discount { width: 100%; }
        .pos-disabled-overlay {
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 300px;
        }
    </style>
@endsection
@section('content')
    <div class="container-fluid pos-screen-wrapper py-3" id="posScreen">
        @if (!$is_fixed_context)
            <div class="mb-3">
                <a href="{{ route('pos-screen.change-context') }}" class="btn btn-sm btn-outline-secondary">
                    <i class="fa fa-exchange-alt"></i> Change Branch
                </a>
            </div>
        @endif

        {{-- Shown until a register session is confirmed open --}}
        <div id="posNoSessionArea" class="card">
            <div class="card-body pos-disabled-overlay">
                <div class="text-center text-muted" id="posNoSessionChecking">
                    <div class="spinner-border mb-2" role="status"></div>
                    <p class="mb-0">Checking register session...</p>
                </div>
                <div class="text-center d-none" id="posNoSessionBrowseOnly">
                    <i class="fa fa-cash-register fs-1 text-muted mb-2"></i>
                    <p class="mb-1 fw-semibold">No register session is open</p>
                    <p class="text-muted mb-3">You can still view Order History and Reports from the header above,
                        but you must open a register before placing orders.</p>
                    <button type="button" class="btn btn-primary" id="openRegisterFromBrowseBtn">
                        <i class="fa fa-lock-open"></i> Open Register
                    </button>
                </div>
            </div>
        </div>

        <div id="posScreenBody" class="row g-3" style="display:none;">
            <div class="col-lg-8">
                <div class="card mb-3">
                    <div class="card-body">
                        <div class="product-search-wrap">
                            <input type="text" class="form-control form-control-lg" id="productSearchInput"
                                placeholder="Scan barcode or search by name / SKU" autocomplete="off">
                            <div id="productSearchResults" class="list-group shadow"></div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Cart</h6>
                        <button type="button" class="btn btn-sm btn-outline-secondary" id="heldOrdersBtn">
                            <i class="fa fa-pause"></i> Held Orders
                            <span class="badge bg-label-primary" id="heldOrdersCount">0</span>
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0" id="cartTable">
                            <thead>
                                <tr>
                                    <th style="min-width:200px;">Product</th>
                                    <th style="min-width:100px;">Unit</th>
                                    <th style="min-width:80px;">Qty</th>
                                    <th style="min-width:100px;">Price</th>
                                    <th style="min-width:80px;" class="line-discount-col">Disc %</th>
                                    <th style="min-width:100px;">Total</th>
                                    <th style="width:40px;"></th>
                                </tr>
                            </thead>
                            <tbody id="cartRows">
                                <tr id="cartEmptyRow">
                                    <td colspan="7" class="text-center text-muted py-4">Cart is empty</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card mb-3">
                    <div class="card-body">
                        <label class="form-label">Customer</label>
                        <select class="form-select select2" id="customer_id">
                            @foreach ($customers as $item)
                                <option value="{{ $item->user_id }}" data-credit-limit="{{ $item->credit_limit ?? 0 }}"
                                    data-walkin="{{ $item->is_walkin ? 1 : 0 }}"
                                    {{ $item->is_walkin ? 'selected' : '' }}>
                                    {{ $item->user->name ?? '' }}{{ $item->is_walkin ? ' (Walk-in)' : '' }}
                                </option>
                            @endforeach
                        </select>
                        <div id="creditLimitHint" class="form-text d-none"></div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-body">
                        <label class="form-label">Order Type</label>
                        <select class="form-select select2 mb-2" id="order_type_id">
                            @foreach ($order_types as $item)
                                <option value="{{ $item->order_type_id }}" {{ $item->is_default ? 'selected' : '' }}>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                        <label class="form-label">Order Source</label>
                        <select class="form-select select2" id="order_source_id">
                            @foreach ($order_sources as $item)
                                <option value="{{ $item->order_source_id }}" {{ $item->is_default ? 'selected' : '' }}>
                                    {{ $item->name }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if ($pos_setting->enable_discount)
                    <div class="card mb-3" id="discountVoucherCard">
                        <div class="card-body">
                            @if (in_array($pos_setting->discount_level, ['order', 'both']))
                                <div class="mb-3" id="orderDiscountWrap">
                                    <label class="form-label">Order Discount</label>
                                    <select class="form-select select2" id="discount_id">
                                        <option value="">--No Discount--</option>
                                        @foreach ($discounts as $item)
                                            <option value="{{ $item->discount_id }}">
                                                {{ $item->name }}
                                                ({{ $item->type == 'percent' ? $item->value . '%' : $item->value }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div id="voucherWrap">
                                <label class="form-label">Voucher / Coupon Code</label>
                                <div class="input-group">
                                    <input type="text" class="form-control" id="voucher_code" placeholder="Enter code">
                                    <button class="btn btn-outline-primary" type="button" id="applyVoucherBtn">
                                        Apply
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                @endif

                <div class="card mb-3">
                    <div class="card-body">
                        <div class="d-flex justify-content-between mb-1">
                            <span>Subtotal</span><span id="sumSubtotal">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Discount</span><span id="sumDiscount">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-1">
                            <span>Tax</span><span id="sumTax">0.00</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between fw-bold fs-5">
                            <span>Total</span><span id="sumTotal">0.00</span>
                        </div>
                    </div>
                </div>

                <div class="card mb-3">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0">Payments</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="addPaymentRowBtn">
                            <i class="fa fa-plus"></i> Add
                        </button>
                    </div>
                    <div class="card-body">
                        <div id="paymentRows"></div>
                        <div class="d-flex justify-content-between mt-2">
                            <span>Entered</span><span id="paymentEntered">0.00</span>
                        </div>
                        <div class="d-flex justify-content-between">
                            <span id="paymentRemainingLabel">Remaining</span><span id="paymentRemaining">0.00</span>
                        </div>
                    </div>
                </div>

                <div class="d-grid gap-2">
                    @if ($pos_setting->enable_hold_order)
                        <button type="button" class="btn btn-outline-secondary" id="holdOrderBtn">
                            Hold Order
                        </button>
                    @endif
                    <button type="button" class="btn btn-success btn-lg" id="completeSaleBtn">
                        Complete Sale
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Open Register Session Modal ================= --}}
    <div class="modal fade" id="openSessionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Open Register Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($pos_setting->register_mode == 'manual')
                        <div class="mb-3">
                            <label class="form-label">Register <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="open_pos_register_id">
                                <option value="">--Select Register--</option>
                                @foreach ($registers as $item)
                                    <option value="{{ $item->pos_register_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">Opening Cash <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="opening_cash" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Opening Notes</label>
                        <textarea class="form-control" id="opening_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="openSessionSubmitBtn">Open Session</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Close Register Session Modal ================= --}}
    <div class="modal fade" id="closeSessionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Close Register Session</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm" id="closeSummaryTable">
                        <tbody>
                            <tr><th>Opening Cash</th><td class="text-end" id="sumOpeningCash">0.00</td></tr>
                            <tr><th>Cash Sales</th><td class="text-end" id="sumCashSales">0.00</td></tr>
                            <tr><th>Cash Refunds</th><td class="text-end" id="sumCashRefunds">0.00</td></tr>
                            <tr><th>Cash In</th><td class="text-end" id="sumCashIn">0.00</td></tr>
                            <tr><th>Cash Out</th><td class="text-end" id="sumCashOut">0.00</td></tr>
                            <tr class="fw-bold"><th>Expected Cash</th><td class="text-end" id="sumExpectedCash">0.00</td></tr>
                        </tbody>
                    </table>
                    <div class="mb-3">
                        <label class="form-label">Actual Cash <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="actual_cash">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Closing Notes</label>
                        <textarea class="form-control" id="closing_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="closeSessionSubmitBtn">Close Session</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Cash Movement Modal ================= --}}
    <div class="modal fade" id="cashMovementModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cashMovementModalTitle">Cash Movement</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cash_movement_type" value="in">
                    <div class="mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="cash_movement_amount">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Reason</label>
                        <input type="text" class="form-control" id="cash_movement_reason">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="cashMovementSubmitBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Held Orders Offcanvas ================= --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="heldOrdersOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Held Orders</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div id="heldOrdersList" class="list-group">
                <div class="text-muted text-center py-3">No held orders</div>
            </div>
        </div>
    </div>

    {{-- ================= Order History Offcanvas (non-transactional - viewable without an open register) ================= --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="orderHistoryOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Order History</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <select class="form-select form-select-sm mb-3" id="orderHistoryStatusFilter">
                <option value="">All Statuses</option>
                <option value="posted">Posted</option>
                <option value="hold">Hold</option>
                <option value="cancelled">Cancelled</option>
                <option value="void">Void</option>
                <option value="returned">Returned</option>
            </select>
            <div id="orderHistoryList" class="list-group">
                <div class="text-muted text-center py-3">No orders found</div>
            </div>
        </div>
    </div>

    {{-- ================= Reports Offcanvas (my register sessions - non-transactional) ================= --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="posReportsOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">My Register Sessions</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div id="posReportsList" class="list-group mb-3">
                <div class="text-muted text-center py-3">No sessions found</div>
            </div>
            <div id="posReportsSummary" class="d-none">
                <hr>
                <h6>Session Summary</h6>
                <table class="table table-sm" id="posReportsSummaryTable">
                    <tbody>
                        <tr><th>Opening Cash</th><td class="text-end" id="repOpeningCash">0.00</td></tr>
                        <tr><th>Cash Sales</th><td class="text-end" id="repCashSales">0.00</td></tr>
                        <tr><th>Cash In</th><td class="text-end" id="repCashIn">0.00</td></tr>
                        <tr><th>Cash Out</th><td class="text-end" id="repCashOut">0.00</td></tr>
                        <tr><th>Total Orders</th><td class="text-end" id="repTotalOrders">0</td></tr>
                        <tr><th>Total Sales</th><td class="text-end" id="repTotalSales">0.00</td></tr>
                        <tr class="fw-bold"><th>Expected Cash</th><td class="text-end" id="repExpectedCash">0.00</td></tr>
                        <tr><th>Actual Cash</th><td class="text-end" id="repActualCash">0.00</td></tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
@section('js')
    @php
        $posConfig = [
            'business_id' => $business_id,
            'branch_id' => $branch_id,
            'pos_setting' => $pos_setting,
            'payment_methods' => $payment_methods,
            'tax_rates_setting' => [
                'overall_tax_rate' => $business_setting->overall_tax_rate,
                'card_tax_rate' => $business_setting->card_tax_rate,
            ],
            'permissions' => $permissions,
            'urls' => [
                'session_current' => url('admin/pos-register-session/current'),
                'session_open' => url('admin/pos-register-session/open'),
                'session_close' => url('admin/pos-register-session/close'),
                'session_summary' => url('admin/pos-register-session/summary'),
                'session_cash_movement' => url('admin/pos-register-session/cash-movement'),
                'session_my_history' => url('admin/pos-register-session/my-history'),
                'search_products' => url('admin/order/search-products'),
                'order_store' => url('admin/order'),
                'order_hold' => url('admin/order/hold'),
                'order_resume' => url('admin/order/resume'),
                'order_complete' => url('admin/order/complete'),
                'order_details' => url('admin/order/details'),
                'order_data' => url('admin/order/data'),
                'order_print' => url('admin/order'),
            ],
        ];
    @endphp
    <script>
        window.POS_CONFIG = @json($posConfig);
    </script>
    <script src="{{ asset('public/assets/js/admin/pos-screen.js') }}"></script>
@endsection
