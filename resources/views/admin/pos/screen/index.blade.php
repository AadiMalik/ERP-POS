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

        <div id="posCorrectionBanner" class="alert alert-warning mb-2 py-2 px-3 d-none justify-content-between align-items-center flex-wrap gap-2">
            <div>
                <i class="fa fa-pencil"></i>
                Correcting order <strong id="posCorrectionOrderLabel"></strong> — same-day manager correction.
                Stock, payments, and accounting will be reversed and reposted.
            </div>
            <button type="button" class="btn btn-sm btn-outline-secondary" id="cancelCorrectionBtn">Cancel Correction</button>
        </div>

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
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="pos-field-label">Order Type</span>
                                @include('admin.partials.quick-add-btn', ['permission' => 'order-type.create', 'modal' => 'quickAddOrderTypeModal', 'label' => 'Order Type'])
                            </div>
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

                        <div class="pos-main-col" id="posMainCol">
                            <div class="pos-product-panel" id="posProductPanel">
                                <div class="pos-product-results" id="posProductResults">
                                    <div id="posProductGrid" class="product-grid"></div>
                                    <div id="posProductGridEmpty" class="pos-empty-state d-none">
                                        <i class="fa fa-box-open fs-1 text-muted mb-2"></i>
                                        <p class="text-muted mb-0">No products found</p>
                                    </div>
                                </div>
                                <button type="button" class="pos-checkout-clip" id="posCheckoutToggle"
                                    aria-expanded="false" title="Payment &amp; Options">
                                    <i class="fa fa-bookmark"></i>
                                    <span id="posCheckoutSummary">Cash</span>
                                </button>
                            </div>

                            {{-- Order-level Sale Type control lives in .pos-cart-header, next to
                                 Clear Cart, for prominence - #sale_type_id here is the single
                                 source of truth the cart-header dropdown (#saleTypeSelect) drives. --}}
                            <select class="d-none" id="sale_type_id">
                                @foreach ($sale_types as $item)
                                    <option value="{{ $item->sale_type_id }}" {{ $item->is_default ? 'selected' : '' }}>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>

                            {{-- ---- Delivery / Discount / Voucher / Payment Method (collapsible) ---- --}}
                            <div class="pos-checkout-wrap is-collapsed" id="posCheckoutWrap">
                                <div class="pos-row pos-row-meta" id="posCheckoutPanel">
                                    <div class="pos-checkout-body" id="posCheckoutBody">
                                        <div class="pos-meta-row pos-delivery-payment-row">
                                            <div class="pos-field pos-field-delivery d-none" id="deliveryAddressWrap">
                                                <label class="pos-field-label" for="delivery_address">Delivery Address <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm" id="delivery_address" placeholder="Enter address">
                                            </div>

                                            <div class="pos-field pos-field-payment" id="paymentMethodField">
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
                                                        <div class="d-none" id="storeCreditSummary">
                                                            <div class="d-flex justify-content-between align-items-center pos-credit-summary">
                                                                <span id="storeCreditText"></span>
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

                                        <div class="pos-meta-row">
                                            @if ($pos_setting->enable_discount && in_array($pos_setting->discount_level, ['order', 'both']))
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
                                            <div class="pos-field pos-field-voucher" id="voucherWrap" style="position:relative;">
                                                <span class="pos-field-label">Voucher / Coupon</span>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" id="voucher_code" placeholder="Search or enter code" autocomplete="off">
                                                    <button class="btn btn-outline-secondary" type="button" id="browseVouchersBtn" title="Show vouchers available for this cart">
                                                        <i class="fa fa-list"></i>
                                                    </button>
                                                    <button class="btn btn-outline-primary" type="button" id="applyVoucherBtn">
                                                        Apply
                                                    </button>
                                                </div>
                                                <div id="voucherApplyFeedback" class="small mt-1" style="display:none;"></div>
                                                <input type="hidden" id="voucher_id" value="">
                                                <div id="voucherSearchResults" class="list-group pos-search-results" style="display:none;"></div>
                                            </div>
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
                            <div class="pos-cart-header-select pos-cart-header-select-customer">
                                <select id="customer_id" class="form-select form-select-sm" title="Customer">
                                    @foreach ($customers as $item)
                                        @php
                                            $customer_label = ($item->code ? $item->code . ' - ' : '') . ($item->user->name ?? '');
                                            if ($item->is_walkin) {
                                                $customer_label .= ' (Walk-in)';
                                            }
                                        @endphp
                                        <option value="{{ $item->user_id }}" data-code="{{ $item->code ?? '' }}"
                                            data-credit-limit="{{ $item->credit_limit ?? 0 }}"
                                            data-walkin="{{ $item->is_walkin ? 1 : 0 }}" data-credit-days="{{ $item->credit_days ?? 0 }}"
                                            data-store-credit-balance="{{ $item->store_credit_balance ?? 0 }}"
                                            data-phone="{{ $item->user->phone ?? '' }}" data-email="{{ $item->user->email ?? '' }}"
                                            {{ $item->is_walkin ? 'selected' : '' }}>
                                            {{ $customer_label }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="creditLimitHint" class="pos-cart-credit-hint d-none"></div>
                            </div>
                            <button type="button" class="btn btn-sm pos-cart-icon-btn" id="addCustomerBtn" title="Add Customer">
                                <i class="fa fa-user-plus"></i>
                            </button>
                            <div class="pos-cart-header-select">
                                <select id="saleTypeSelect" class="form-select form-select-sm" title="Sale Type">
                                    @foreach ($sale_types as $item)
                                        <option value="{{ $item->sale_type_id }}" {{ $item->is_default ? 'selected' : '' }}>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <span class="pos-cart-order-no d-none" id="cartOrderNoBadge"></span>
                            <button type="button" class="btn btn-sm pos-clear-cart-btn d-none" id="clearCartBtn">
                                <i class="fa fa-trash"></i> Clear
                            </button>
                        </div>
                        <div class="pos-cart-columns">
                            <span class="pos-cart-col-items">Items</span>
                            <span class="pos-cart-col-price">Price ({{ session('accounting_setting.currency_symbol', 'Rs') }})</span>
                            @if ($pos_setting->enable_discount && in_array($pos_setting->discount_level, ['line', 'both']))
                                <span class="pos-cart-col-discount">Discount</span>
                            @endif
                            <span class="pos-cart-col-qty">Qty</span>
                            <span class="pos-cart-col-total">Total ({{ session('accounting_setting.currency_symbol', 'Rs') }})</span>
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
                        <div class="pos-totals-row"><span>Item Discounts</span><span id="sumItemDiscount">0.00</span></div>
                        <div class="pos-totals-row"><span>Order Discount</span><span id="sumOrderDiscount">0.00</span></div>
                        <div class="pos-totals-row"><span>Tax</span><span id="sumTax">0.00</span></div>
                        <div class="pos-totals-row pos-grand-total"><span>Total ({{ session('accounting_setting.currency_symbol', 'Rs') }})</span><span id="sumTotal">0.00</span></div>
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

    {{-- $business here is POS's own single current-context Business model, not
         the collection admin.order-type.model.quick-create needs for its
         Super-Admin business picker - pass $context_businesses explicitly so
         the two don't collide (see PosScreenController::index()). --}}
    @include('admin.order-type.model.quick-create', ['business' => $context_businesses])

    {{-- ================= Credit Payment Modal ================= --}}
    {{-- Shown after a Credit-type sale completes (see completeSale() in
         pos-screen.js) - due date/note are optional and can be skipped, the
         customer itself is already guaranteed non-walk-in before checkout
         even starts. JV generation for the credit sale already happened
         automatically in OrderService::post() - this only records optional
         follow-up info on the order (due_date/notes). --}}
    <div class="modal fade" id="creditPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Credit Sale - Customer Payment Details</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Customer</label>
                        <div class="form-control-plaintext fw-bold" id="creditCustomerName"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Due Date</label>
                        <input type="date" class="form-control" id="creditDueDate">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Note</label>
                        <textarea class="form-control" id="creditNote" rows="2" placeholder="Optional reference note"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="creditPaymentSkipBtn">Skip</button>
                    <button type="button" class="btn btn-primary" id="creditPaymentSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Same-day manager correction requires a reason before reverse/repost. --}}
    <div class="modal fade" id="correctionReasonModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Correction Reason</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">
                        This will reverse stock and accounting for the posted sale, apply your cart changes, and repost on the same order number.
                    </p>
                    <label class="form-label">Reason <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="correction_reason" rows="3" maxlength="1000" placeholder="Why is this order being corrected?"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="correctionReasonSubmitBtn">Apply Correction</button>
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
                            <tr><th>Cash Sales (+)</th><td class="text-end" id="sumCashSales">0.00</td></tr>
                            <tr><th>Cash Refunds (&minus;)</th><td class="text-end" id="sumCashRefunds">0.00</td></tr>
                            <tr><th>Cash In (+)</th><td class="text-end" id="sumCashIn">0.00</td></tr>
                            <tr><th>Cash Out (&minus;)</th><td class="text-end" id="sumCashOut">0.00</td></tr>
                            <tr><th>Expenses (&minus;)</th><td class="text-end" id="sumExpenses">0.00</td></tr>
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
                        <label class="form-label">Reason <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cash_movement_reason" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="cashMovementSubmitBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Add Expense Modal (quick log against active session) ================= --}}
    <div class="modal fade" id="addExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Add Expense</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Category <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="expense_category_id">
                            <option value="">--Select Category--</option>
                            @foreach ($expense_categories as $item)
                                <option value="{{ $item->expense_category_id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="expense_amount">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">Description</label>
                        <input type="text" class="form-control" id="expense_description">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="addExpenseSubmitBtn">Save Expense</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Held Orders Offcanvas ================= --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="heldOrdersOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">Hold Orders</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div id="heldOrdersList" class="list-group">
                <div class="text-muted text-center py-3">No hold orders</div>
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
                <div class="table-responsive">
                    <table class="table table-sm" id="posReportsSummaryTable">
                        <thead>
                            <tr><th>Detail</th><th class="text-end">Orders</th><th class="text-end">Amount</th></tr>
                        </thead>
                        <tbody>
                            <tr class="fw-bold"><td>Total</td><td class="text-end" id="repTotalOrders">0</td><td class="text-end" id="repTotalSales">0.00</td></tr>
                        </tbody>
                        <tbody id="repPaymentRows"></tbody>
                        <tbody id="repSourceRows"></tbody>
                        <tbody>
                            <tr><td>Discount</td><td class="text-end" id="repDiscountOrderCount">0</td><td class="text-end" id="repTotalDiscount">0.00</td></tr>
                            <tr><td>Tax</td><td class="text-end" id="repTaxOrderCount">0</td><td class="text-end" id="repTotalTax">0.00</td></tr>
                            <tr><td>Opening Amount</td><td class="text-end">-</td><td class="text-end" id="repOpeningCash">0.00</td></tr>
                            <tr><td>Cash Refunds (&minus;)</td><td class="text-end">-</td><td class="text-end" id="repCashRefunds">0.00</td></tr>
                            <tr><td>Cash In (+)</td><td class="text-end">-</td><td class="text-end" id="repCashIn">0.00</td></tr>
                            <tr><td>Cash Out (&minus;)</td><td class="text-end">-</td><td class="text-end" id="repCashOut">0.00</td></tr>
                            <tr><td>Expenses (&minus;)</td><td class="text-end">-</td><td class="text-end" id="repExpenses">0.00</td></tr>
                            <tr class="fw-bold"><td>Cash Amount</td><td class="text-end">-</td><td class="text-end" id="repExpectedCash">0.00</td></tr>
                            <tr><td>Actual</td><td class="text-end">-</td><td class="text-end" id="repActualCash">0.00</td></tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="printSessionSummaryBtn">
                    <i class="fa fa-print"></i> Thermal Print
                </button>
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
            'allow_negative_stock' => (bool) $inventory_setting->negative_stock,
            'payment_methods' => $payment_methods,
            'sale_types' => $sale_types,
            'tax_rates_setting' => [
                'overall_tax_rate' => $business_setting->overall_tax_rate,
                'card_tax_rate' => $business_setting->card_tax_rate,
            ],
            'permissions' => $permissions,
            'reorder_from' => $reorder_from,
            'correct_order_id' => $correct_order_id ?? null,
            'urls' => [
                'session_current' => url('admin/pos-register-session/current'),
                'session_open' => url('admin/pos-register-session/open'),
                'session_close' => url('admin/pos-register-session/close'),
                'session_summary' => url('admin/pos-register-session/summary'),
                'session_summary_print' => url('admin/pos-register-session/summary'),
                'session_cash_movement' => url('admin/pos-register-session/cash-movement'),
                'session_my_history' => url('admin/pos-register-session/my-history'),
                'search_products' => url('admin/order/search-products'),
                'search_vouchers' => url('admin/order/search-vouchers'),
                'eligible_vouchers' => url('admin/order/eligible-vouchers'),
                'preview_voucher' => url('admin/order/preview-voucher'),
                'products_by_category' => url('admin/order/products-by-category'),
                'resolve_prices' => url('admin/order/resolve-prices'),
                'order_store' => url('admin/order'),
                'order_hold' => url('admin/order/hold'),
                'order_resume' => url('admin/order/resume'),
                'order_complete' => url('admin/order/complete'),
                'order_correct' => url('admin/order/correct'),
                'order_credit_info' => url('admin/order/credit-info'),
                'order_details' => url('admin/order/details'),
                'order_data' => url('admin/order/data'),
                'order_print' => url('admin/order'),
                'quick_customer' => route('pos-screen.quick-customer'),
                'quick_expense' => route('pos-screen.quick-expense'),
            ],
        ];
    @endphp
    <script>
        window.POS_CONFIG = @json($posConfig);
    </script>
    <script src="{{ asset('public/assets/js/admin/pos-screen.js') }}"></script>
@endsection
