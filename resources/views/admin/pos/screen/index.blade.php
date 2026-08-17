@extends('layouts.pos')
@section('css')
    <link rel="stylesheet" href="{{ asset('public/assets/css/admin/pos-screen.css') }}">
@endsection
@section('content')
    <div class="pos-screen-wrapper" id="posScreen">
        <select class="d-none" id="order_type_id">
            @foreach ($order_types as $item)
                <option value="{{ $item->order_type_id }}" data-code="{{ $item->code }}" {{ $item->is_default ? 'selected' : '' }}>
                    {{ $item->name }}
                </option>
            @endforeach
        </select>

        {{-- Order Source is resolved server-side (PosScreenController::resolvePosOrderSourceId())
             and is never user-facing; this hidden field only exists so buildStorePayload()
             keeps reading #order_source_id unchanged. --}}
        <input type="hidden" id="order_source_id" value="{{ $pos_order_source_id }}">

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
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-primary" id="openRegisterFromBrowseBtn">
                            <i class="fa fa-lock-open"></i> Open Register
                        </button>
                        @if (!$is_fixed_context)
                            <button type="button" class="btn btn-outline-secondary js-change-branch-btn">
                                <i class="fa fa-code-branch"></i> Change Branch
                            </button>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div id="posScreenBody" class="pos-screen-body" style="display:none;">
            <div class="pos-layout">
                {{-- ================= Left column (8/12) ================= --}}
                <div class="pos-left-col">
                    {{-- ---- Row 1: Search Product / Order Type / Branch ---- --}}
                    <div class="pos-row pos-row-top">
                        <div class="pos-field pos-field-search">
                            <span class="pos-field-label">Search Product</span>
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

                        <div class="pos-field pos-field-ordertype pos-pill-group" data-select-target="order_type_id">
                            <span class="pos-field-label">Order Type</span>
                            <div class="pos-pill-buttons">
                                @foreach ($order_types as $item)
                                    <button type="button" class="pos-pill {{ $item->is_default ? 'active' : '' }}"
                                        data-value="{{ $item->order_type_id }}" data-code="{{ $item->code }}">{{ $item->name }}</button>
                                @endforeach
                            </div>
                        </div>

                        <div class="pos-field pos-field-branch">
                            <span class="pos-field-label">Branch</span>
                            @if (!$is_fixed_context)
                                <div class="pos-branch-switch">
                                    <span class="pos-branch-current"><i class="fa fa-code-branch"></i> {{ $branch_name ?? 'Branch' }}</span>
                                    <button type="button" class="btn pos-header-btn js-change-branch-btn" id="changeBranchBtn" title="Change Branch">
                                        <i class="fa fa-exchange-alt"></i>
                                    </button>
                                </div>
                            @else
                                <div class="pos-branch-static"><i class="fa fa-code-branch"></i> {{ $branch_name ?? 'Branch' }}</div>
                            @endif
                        </div>
                    </div>

                    {{-- ---- Row 2: Categories (full height, spans down to the footer) + Products/Checkout column ---- --}}
                    <div class="pos-row pos-row-products">
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

                        <div class="pos-main-col">
                            <div class="pos-product-panel" id="posProductPanel">
                                <div class="pos-product-results" id="posProductResults">
                                    <div id="posProductGrid" class="product-grid"></div>
                                    <div id="posProductGridEmpty" class="pos-empty-state d-none">
                                        <i class="fa fa-box-open fs-1 text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No products found</p>
                                    </div>
                                </div>
                            </div>

                            {{-- ---- Customer / Delivery / Discount / Voucher / Payment Method ---- --}}
                            <div class="pos-row pos-row-meta">
                                <div class="pos-meta-row">
                                    <div class="pos-field pos-field-customer">
                                        <span class="pos-field-label">Customer</span>
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
                                            <button type="button" class="btn pos-add-customer-btn" id="addCustomerBtn" title="Add Customer">
                                                <i class="fa fa-user-plus"></i>
                                            </button>
                                        </div>
                                        <div id="creditLimitHint" class="pos-field-floating-hint d-none"></div>
                                    </div>

                                    <div class="pos-field pos-field-delivery d-none" id="deliveryAddressWrap">
                                        <label class="pos-field-label" for="delivery_address">Delivery Address <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control form-control-sm" id="delivery_address" placeholder="Enter address">
                                    </div>
                                </div>

                                @if ($pos_setting->enable_discount)
                                    <div class="pos-meta-row">
                                        @if (in_array($pos_setting->discount_level, ['order', 'both']))
                                            <div class="pos-field pos-field-discount" id="orderDiscountWrap">
                                                <span class="pos-field-label">Discount</span>
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
                                        <div class="pos-field pos-field-voucher" id="voucherWrap">
                                            <span class="pos-field-label">Voucher / Coupon</span>
                                            <div class="input-group input-group-sm">
                                                <input type="text" class="form-control" id="voucher_code" placeholder="Enter code">
                                                <button class="btn btn-outline-primary" type="button" id="applyVoucherBtn">
                                                    Apply
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                @endif

                                <div class="pos-field pos-field-payment">
                                    <span class="pos-field-label">Payment Method</span>
                                    <select class="d-none" id="paymentMethodSelect">
                                        <option value="">Payment Method</option>
                                    </select>
                                    <div class="pos-pill-group" data-select-target="paymentMethodSelect">
                                        <div class="pos-pill-buttons pos-payment-pills" id="paymentMethodPills"></div>
                                    </div>

                                    <div class="pos-payment-extra">
                                        <div class="pos-payment-extra-summary">
                                            <span>Entered</span><span id="paymentEntered">0.00</span>
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
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- ================= Right column (4/12) ================= --}}
                <div class="pos-right-col">
                    {{-- ---- Row 1: Cart ---- --}}
                    <div class="pos-cart-card" id="posCartPanel">
                        <div class="pos-cart-header">
                            <h6 class="mb-0">Cart <span class="pos-cart-count" id="cartItemCount">(0 Items)</span></h6>
                            <div class="d-flex align-items-center gap-2">
                                <span class="pos-cart-order-no d-none" id="cartOrderNoBadge"></span>
                                <button type="button" class="btn btn-sm pos-clear-cart-btn d-none" id="clearCartBtn">
                                    <i class="fa fa-trash"></i> Clear
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

                    {{-- ---- Rows 2-5: Subtotal / Discount / Tax / Total ---- --}}
                    <div class="pos-totals-card">
                        <div class="pos-totals-row"><span>Subtotal</span><span id="sumSubtotal">0.00</span></div>
                        <div class="pos-totals-row"><span>Discount</span><span id="sumDiscount">0.00</span></div>
                        <div class="pos-totals-row"><span>Tax</span><span id="sumTax">0.00</span></div>
                        <div class="pos-totals-row pos-grand-total"><span>Total</span><span id="sumTotal">0.00</span></div>
                    </div>
                </div>
            </div>

            {{-- ================= Sticky footer: Paid Amount / Due-Change / Hold & Pay ================= --}}
            <div class="pos-sticky-footer">
                <div class="pos-footer-field pos-footer-paid">
                    <label for="paidAmountInput">Paid Amount</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="paidAmountInput" placeholder="0.00">
                </div>

                <div class="pos-footer-field pos-footer-due">
                    <label id="paymentRemainingLabel">Due / Change</label>
                    <span class="pos-footer-due-value" id="paymentRemaining">0.00</span>
                </div>

                <div class="pos-footer-actions">
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

    {{-- ================= Product Picker Modal (variation grid - only opened when
         a product has >1 variation; unit is display-only, qty always 1, a card
         click adds immediately and closes) ================= --}}
    <div class="modal fade" id="productPickerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="productPickerTitle">Select a variation</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="productPickerGrid" class="product-grid product-picker-grid"></div>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Change Branch Modal (switches business/branch/warehouse
         context without leaving the POS screen - submits to the same
         pos-screen.context route the original full-page picker used) ================= --}}
    @if (!$is_fixed_context)
        <div class="modal fade" id="changeBranchModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">Change Branch</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('pos-screen.context') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            @if ($is_superadmin)
                                <div class="mb-3">
                                    <label class="form-label">Business <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="business_id" id="changeBranchBusinessId" required>
                                        <option value="">--Select Business--</option>
                                        @foreach ($context_businesses as $item)
                                            <option value="{{ $item->business_id }}" {{ $item->business_id == $business_id ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">Branch <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="branch_id" id="changeBranchBranchId" required>
                                    <option value="">--Select Branch--</option>
                                    @foreach ($context_branches as $item)
                                        <option value="{{ $item->branch_id }}" {{ $item->branch_id == $branch_id ? 'selected' : '' }}>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-1">
                                <label class="form-label">Warehouse</label>
                                <select class="form-select select2" name="warehouse_id" id="changeBranchWarehouseId">
                                    <option value="">--Select Warehouse--</option>
                                    @foreach ($context_warehouses as $item)
                                        <option value="{{ $item->warehouse_id }}" {{ $item->warehouse_id == $warehouse_id ? 'selected' : '' }}>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">Manual mode registers already fix a warehouse - this is only used where the register/session doesn't determine one.</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">Switch Branch</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

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
