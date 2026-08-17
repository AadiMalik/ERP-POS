@extends('layouts.pos')
@section('css')
    <link rel="stylesheet" href="{{ asset('public/assets/css/admin/pos-screen.css') }}">
@endsection
@section('content')
    <div class="pos-screen-wrapper" id="posScreen">
        @if (!$is_fixed_context)
            <div class="pos-context-switch">
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

        <div id="posScreenBody" class="pos-screen-body" style="display:none;">
            {{-- ================= Toolbar: product search / scan only ================= --}}
            <div class="pos-toolbar">
                <div class="pos-toolbar-zone pos-toolbar-search">
                    <div class="pos-search-input-wrap">
                        <i class="fa fa-magnifying-glass pos-search-icon"></i>
                        <input type="text" class="form-control" id="productSearchInput"
                            placeholder="Search by name, SKU or scan barcode..." autocomplete="off">
                        <button type="button" class="btn pos-scan-btn" id="scanFocusBtn" title="Scan barcode">
                            <i class="fa fa-barcode"></i>
                        </button>
                    </div>
                    <div id="productSearchResults" class="list-group pos-search-results" style="display:none;"></div>
                </div>
            </div>

            <div class="pos-main-row">
                {{-- ================= Category Rail ================= --}}
                <div class="pos-category-rail" id="posCategoryRail">
                    <button type="button" class="category-rail-item active" data-category-id="">
                        <span class="category-rail-icon"><i class="fa fa-th-large"></i></span>
                        <span class="category-rail-label">All Products</span>
                    </button>
                    @foreach ($categories as $item)
                        <button type="button" class="category-rail-item" data-category-id="{{ $item->category_id }}">
                            <span class="category-rail-icon"><img src="{{ $item->logo_url }}" alt=""></span>
                            <span class="category-rail-label">{{ $item->name }}</span>
                        </button>
                    @endforeach
                </div>

                {{-- ================= Product Browse Panel ================= --}}
                <div class="pos-product-panel" id="posProductPanel">
                    <div class="pos-product-results" id="posProductResults">
                        <div id="posProductGrid" class="product-grid"></div>
                        <div id="posProductGridEmpty" class="pos-empty-state d-none">
                            <i class="fa fa-box-open fs-1 text-muted mb-2"></i>
                            <p class="text-muted mb-0">No products found</p>
                        </div>
                    </div>
                </div>

                {{-- ================= Cart / Checkout Panel ================= --}}
                <div class="pos-cart-panel" id="posCartPanel">
                    <div class="pos-cart-table-wrap">
                        <div class="pos-cart-header">
                            <h6 class="mb-0">Cart <span class="pos-cart-count" id="cartItemCount">(0 Items)</span></h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="pos-cart-order-no d-none" id="cartOrderNoBadge"></span>
                                <button type="button" class="btn btn-sm pos-clear-cart-btn d-none" id="clearCartBtn">
                                    <i class="fa fa-trash"></i> Clear Cart
                                </button>
                            </div>
                        </div>
                        <div class="pos-cart-scroll">
                            <div id="cartRows" class="pos-cart-lines">
                                <div class="pos-cart-empty" id="cartEmptyRow">
                                    <i class="fa fa-cart-shopping fs-1 text-muted mb-2"></i>
                                    <p class="text-muted mb-0">Cart is empty</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="pos-cart-footer">
                        <div class="pos-footer-row" id="customerOrderDetailsCard">
                            <div class="pos-customer-row">
                                <select class="form-select form-select-sm select2" id="customer_id">
                                    @foreach ($customers as $item)
                                        <option value="{{ $item->user_id }}" data-credit-limit="{{ $item->credit_limit ?? 0 }}"
                                            data-walkin="{{ $item->is_walkin ? 1 : 0 }}"
                                            data-phone="{{ $item->user->phone ?? '' }}" data-email="{{ $item->user->email ?? '' }}"
                                            {{ $item->is_walkin ? 'selected' : '' }}>
                                            {{ $item->user->name ?? '' }}{{ $item->is_walkin ? ' (Walk-in)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                                <button type="button" class="btn btn-sm pos-add-customer-btn" id="addCustomerBtn" title="Add Customer">
                                    <i class="fa fa-user-plus"></i>
                                </button>
                            </div>
                            <div id="creditLimitHint" class="form-text d-none"></div>

                            <div class="pos-order-meta-row">
                                <div class="pos-pill-group" data-select-target="order_type_id">
                                    <span class="pos-pill-group-label">Order Type</span>
                                    <div class="pos-pill-buttons">
                                        @foreach ($order_types as $item)
                                            <button type="button" class="pos-pill {{ $item->is_default ? 'active' : '' }}"
                                                data-value="{{ $item->order_type_id }}" data-code="{{ $item->code }}">{{ $item->name }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <div class="pos-pill-group" data-select-target="order_source_id">
                                    <span class="pos-pill-group-label">Order Source</span>
                                    <div class="pos-pill-buttons">
                                        @foreach ($order_sources as $item)
                                            <button type="button" class="pos-pill {{ $item->is_default ? 'active' : '' }}"
                                                data-value="{{ $item->order_source_id }}">{{ $item->name }}</button>
                                        @endforeach
                                    </div>
                                </div>
                                <select class="d-none" id="order_type_id">
                                    @foreach ($order_types as $item)
                                        <option value="{{ $item->order_type_id }}" data-code="{{ $item->code }}" {{ $item->is_default ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <select class="d-none" id="order_source_id">
                                    @foreach ($order_sources as $item)
                                        <option value="{{ $item->order_source_id }}" {{ $item->is_default ? 'selected' : '' }}>
                                            {{ $item->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="d-none pos-delivery-row" id="deliveryAddressWrap">
                                <label class="form-label mb-0" for="delivery_address">Delivery Address <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" id="delivery_address" placeholder="Enter delivery address">
                            </div>
                        </div>

                        @if ($pos_setting->enable_discount)
                            <div class="pos-footer-row pos-discount-voucher-row" id="discountVoucherCard">
                                @if (in_array($pos_setting->discount_level, ['order', 'both']))
                                    <div id="orderDiscountWrap">
                                        <label class="form-label mb-1">Discount</label>
                                        <select class="form-select form-select-sm select2" id="discount_id">
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
                                    <label class="form-label mb-1">Voucher / Coupon</label>
                                    <div class="input-group input-group-sm">
                                        <input type="text" class="form-control" id="voucher_code" placeholder="Enter code">
                                        <button class="btn btn-outline-primary" type="button" id="applyVoucherBtn">
                                            Apply
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <div class="pos-footer-row pos-totals">
                            <div class="d-flex justify-content-between mb-1">
                                <span>Subtotal</span><span id="sumSubtotal">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Discount</span><span id="sumDiscount">0.00</span>
                            </div>
                            <div class="d-flex justify-content-between mb-1">
                                <span>Tax</span><span id="sumTax">0.00</span>
                            </div>
                            <hr class="my-1">
                            <div class="d-flex justify-content-between pos-grand-total">
                                <span>Total</span><span id="sumTotal">0.00</span>
                            </div>
                        </div>

                        <div class="pos-footer-row" id="paymentSection">
                            <div class="pos-payment-compact-row">
                                <select class="form-select form-select-sm" id="paymentMethodSelect">
                                    <option value="">Payment Method</option>
                                </select>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" id="paidAmountInput" placeholder="Paid Amount">
                            </div>

                            <div id="singlePaymentBlock">
                                <div class="d-none" id="creditCustomerSummary">
                                    <div class="d-flex justify-content-between align-items-center pos-credit-summary">
                                        <span id="creditCustomerText"></span>
                                        <a href="javascript:void(0);" id="creditCustomerChangeLink">Change</a>
                                    </div>
                                </div>
                            </div>

                            <div class="d-none" id="multiPaymentBlock">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <span class="fw-semibold">Split Payment</span>
                                    <button type="button" class="btn btn-sm btn-outline-primary" id="addPaymentRowBtn">
                                        <i class="fa fa-plus"></i> Add
                                    </button>
                                </div>
                                <div id="paymentRows"></div>
                            </div>

                            <div class="pos-payment-summary">
                                <div class="d-flex justify-content-between mb-1">
                                    <span>Paid</span><span id="paymentEntered">0.00</span>
                                </div>
                                <div class="d-flex justify-content-between pos-change-row">
                                    <span id="paymentRemainingLabel">Remaining</span><span id="paymentRemaining">0.00</span>
                                </div>
                            </div>
                        </div>

                        <div class="pos-footer-row pos-action-buttons">
                            @if ($pos_setting->enable_hold_order)
                                <button type="button" class="btn pos-hold-btn" id="holdOrderBtn">
                                    <i class="fa fa-pause"></i> Hold <span class="pos-key-hint">(F6)</span>
                                </button>
                            @endif
                            <button type="button" class="btn pos-pay-btn" id="completeSaleBtn">
                                <i class="fa fa-check"></i> Pay <span class="pos-key-hint">(F9)</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Product Picker Modal (variation / unit / qty) ================= --}}
    <div class="modal fade" id="productPickerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productPickerTitle">Select Options</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="product-picker-summary">
                        <img id="productPickerImage" src="" alt="" class="product-picker-img">
                        <div>
                            <div class="fw-semibold" id="productPickerName"></div>
                            <div class="text-muted small" id="productPickerSku"></div>
                            <div class="fw-bold text-primary" id="productPickerPrice"></div>
                        </div>
                    </div>

                    <div class="mb-3 d-none" id="productPickerVariationWrap">
                        <label class="form-label">Variation</label>
                        <select class="form-select" id="productPickerVariation"></select>
                    </div>

                    <div class="mb-3" id="productPickerUnitWrap">
                        <label class="form-label">Unit</label>
                        <select class="form-select" id="productPickerUnit"></select>
                    </div>

                    <div class="mb-1">
                        <label class="form-label">Quantity</label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="productPickerQty" value="1">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-success" id="productPickerAddBtn">
                        <i class="fa fa-cart-plus"></i> Add to Cart
                    </button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Add Customer Modal (quick create, stays on POS screen) ================= --}}
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Customer</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_customer_name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="new_customer_email">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Phone</label>
                        <input type="text" class="form-control" id="new_customer_phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="addCustomerSubmitBtn">Save Customer</button>
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
                'products_by_category' => url('admin/order/products-by-category'),
                'order_store' => url('admin/order'),
                'order_hold' => url('admin/order/hold'),
                'order_resume' => url('admin/order/resume'),
                'order_complete' => url('admin/order/complete'),
                'order_details' => url('admin/order/details'),
                'order_data' => url('admin/order/data'),
                'order_print' => url('admin/order'),
                'quick_customer' => route('pos-screen.quick-customer'),
            ],
        ];
    @endphp
    <script>
        window.POS_CONFIG = @json($posConfig);
    </script>
    <script src="{{ asset('public/assets/js/admin/pos-screen.js') }}"></script>
@endsection
