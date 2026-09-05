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
            <button type="button" class="btn btn-sm btn-outline-secondary" id="cancelCorrectionBtn">{{ __('pos.cancel_correction') }}</button>
        </div>

        {{-- Shown until a register session is confirmed open --}}
        <div id="posNoSessionArea" class="card">
            <div class="card-body pos-disabled-overlay">
                <div class="text-center text-muted" id="posNoSessionChecking">
                    <div class="spinner-border mb-2" role="status"></div>
                    <p class="mb-0">{{ __('pos.checking_session') }}</p>
                </div>
                <div class="text-center d-none" id="posNoSessionBrowseOnly">
                    <i class="fa fa-cash-register fs-1 text-muted mb-2"></i>
                    <p class="mb-1 fw-semibold">{{ __('pos.no_session_open') }}</p>
                    <p class="text-muted mb-3">{{ __('pos.browse_only_hint') }}</p>
                    <div class="d-flex justify-content-center gap-2 flex-wrap">
                        <button type="button" class="btn btn-primary" id="openRegisterFromBrowseBtn">
                            <i class="fa fa-lock-open"></i> {{ __('pos.open_register_btn') }}
                        </button>
                        @if (!$is_fixed_context)
                            <button type="button" class="btn btn-outline-secondary js-change-branch-btn">
                                <i class="fa fa-code-branch"></i> {{ __('pos.change_branch') }}
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
                            <span class="pos-field-label">{{ __('common.search_product') }}</span>
                            <div class="pos-search-input-wrap">
                                <i class="fa fa-magnifying-glass pos-search-icon"></i>
                                <input type="text" class="form-control" id="productSearchInput"
                                    placeholder="{{ __('pos.search_product_placeholder') }}" autocomplete="off">
                                <button type="button" class="btn pos-scan-btn" id="scanFocusBtn" title="{{ __('pos.scan_barcode') }}">
                                    <i class="fa fa-barcode"></i>
                                </button>
                            </div>
                            <div id="productSearchResults" class="list-group pos-search-results" style="display:none;"></div>
                        </div>

                        <div class="pos-field pos-field-ordertype pos-pill-group" data-select-target="order_type_id">
                            <div class="d-flex align-items-center justify-content-between">
                                <span class="pos-field-label">{{ __('common.order_type') }}</span>
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
                            <span class="pos-field-label">{{ __('common.branch') }}</span>
                            @if (!$is_fixed_context)
                                <div class="pos-branch-switch">
                                    <span class="pos-branch-current"><i class="fa fa-code-branch"></i> {{ $branch_name ?? 'Branch' }}</span>
                                    <button type="button" class="btn pos-header-btn js-change-branch-btn" id="changeBranchBtn" title="{{ __('pos.change_branch') }}">
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
                                <span class="category-rail-label">{{ __('common.all_products_label') }}</span>
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
                                        <p class="text-muted mb-0">{{ __('common.no_products_found') }}</p>
                                    </div>
                                </div>
                                <button type="button" class="pos-checkout-clip" id="posCheckoutToggle"
                                    aria-expanded="false" title="{{ __('pos.payment_options') }}">
                                    <i class="fa fa-bookmark"></i>
                                    <span id="posCheckoutSummary">{{ __('common.cash') }}</span>
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
                                                <label class="pos-field-label" for="delivery_address">{{ __('common.delivery_address') }} <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control form-control-sm" id="delivery_address" placeholder="{{ __('common.enter_address') }}">
                                            </div>

                                            <div class="pos-field pos-field-payment" id="paymentMethodField">
                                                <span class="pos-field-label">{{ __('common.payment_method') }}</span>
                                                <select class="d-none" id="paymentMethodSelect">
                                                    <option value="">{{ __('common.payment_method') }}</option>
                                                </select>
                                                <div class="pos-pill-group" data-select-target="paymentMethodSelect">
                                                    <div class="pos-pill-buttons pos-payment-pills" id="paymentMethodPills"></div>
                                                </div>

                                                <div class="pos-payment-extra">
                                                    <div class="pos-payment-extra-summary">
                                                        <span>{{ __('common.entered') }}</span><span id="paymentEntered">0.00</span>
                                                    </div>

                                                    <div id="singlePaymentBlock">
                                                        <div class="d-none" id="creditCustomerSummary">
                                                            <div class="d-flex justify-content-between align-items-center pos-credit-summary">
                                                                <span id="creditCustomerText"></span>
                                                                <a href="javascript:void(0);" id="creditCustomerChangeLink">{{ __('common.change') }}</a>
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
                                                            <span class="fw-semibold">{{ __('common.split_payment') }}</span>
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
                                                    <span class="pos-field-label">{{ __('common.discount') }}</span>
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
                                                <span class="pos-field-label">{{ __('common.voucher_coupon') }}</span>
                                                <div class="input-group input-group-sm">
                                                    <input type="text" class="form-control" id="voucher_code" placeholder="{{ __('pos.search_or_enter_code') }}" autocomplete="off">
                                                    <button class="btn btn-outline-secondary" type="button" id="browseVouchersBtn" title="{{ __('pos.show_vouchers_title') }}">
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
                                            @if ($customer_setting->loyalty_program ?? false)
                                                <div class="pos-field pos-field-loyalty" id="loyaltyWrap">
                                                    <span class="pos-field-label">{{ __('common.loyalty_points') }}</span>
                                                    <div class="form-check">
                                                        <input type="checkbox" class="form-check-input" id="use_loyalty_points">
                                                        <label class="form-check-label" for="use_loyalty_points">{{ __('pos.redeem_points') }}</label>
                                                    </div>
                                                    <div id="loyaltyPointsHint" class="small text-muted mt-1" style="display:none;"></div>
                                                </div>
                                            @endif
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
                            <h6 class="mb-0">{{ __('common.cart') }} <span class="pos-cart-count" id="cartItemCount">{{ __('pos.cart_items_count', ['count' => 0]) }}</span></h6>
                            <div class="pos-cart-header-select pos-cart-header-select-customer">
                                <select id="customer_id" class="form-select form-select-sm" title="{{ __('common.customer') }}">
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
                                            data-loyalty-points="{{ $item->loyalty_points ?? 0 }}"
                                            data-phone="{{ $item->user->phone ?? '' }}" data-email="{{ $item->user->email ?? '' }}"
                                            {{ $item->is_walkin ? 'selected' : '' }}>
                                            {{ $customer_label }}
                                        </option>
                                    @endforeach
                                </select>
                                <div id="creditLimitHint" class="pos-cart-credit-hint d-none"></div>
                            </div>
                            <button type="button" class="btn btn-sm pos-cart-icon-btn" id="addCustomerBtn" title="{{ __('common.add_customer') }}">
                                <i class="fa fa-user-plus"></i>
                            </button>
                            <div class="pos-cart-header-select">
                                <select id="saleTypeSelect" class="form-select form-select-sm" title="{{ __('pos.sale_type') }}">
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
                            <span class="pos-cart-col-items">{{ __('common.items') }}</span>
                            <span class="pos-cart-col-price">{{ __('pos.price_with_currency', ['symbol' => session('accounting_setting.currency_symbol', 'Rs')]) }}</span>
                            @if ($pos_setting->enable_discount && in_array($pos_setting->discount_level, ['line', 'both']))
                                <span class="pos-cart-col-discount">{{ __('common.discount') }}</span>
                            @endif
                            <span class="pos-cart-col-qty">{{ __('common.qty') }}</span>
                            <span class="pos-cart-col-total">{{ __('pos.total_with_currency', ['symbol' => session('accounting_setting.currency_symbol', 'Rs')]) }}</span>
                        </div>
                        <div class="pos-cart-scroll">
                            <div id="cartRows" class="pos-cart-lines">
                                <div class="pos-cart-empty" id="cartEmptyRow">
                                    <i class="fa fa-cart-shopping fs-1 text-muted mb-2"></i>
                                    <p class="text-muted mb-0">{{ __('common.cart_empty') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- ---- Rows 2-5: Subtotal / Discount / Tax / Total ---- --}}
                    <div class="pos-totals-card">
                        <div class="pos-totals-row"><span>{{ __('common.subtotal') }}</span><span id="sumSubtotal">0.00</span></div>
                        <div class="pos-totals-row"><span>{{ __('pos.item_discounts') }}</span><span id="sumItemDiscount">0.00</span></div>
                        <div class="pos-totals-row"><span>{{ __('pos.order_discount') }}</span><span id="sumOrderDiscount">0.00</span></div>
                        <div class="pos-totals-row d-none" id="sumLoyaltyDiscountRow"><span>{{ __('pos.loyalty_discount') }}</span><span id="sumLoyaltyDiscount">0.00</span></div>
                        <div class="pos-totals-row"><span>{{ __('common.tax') }}</span><span id="sumTax">0.00</span></div>
                        <div class="pos-totals-row pos-grand-total"><span>{{ __('pos.total_with_currency', ['symbol' => session('accounting_setting.currency_symbol', 'Rs')]) }}</span><span id="sumTotal">0.00</span></div>
                    </div>
                </div>
            </div>

            {{-- ================= Sticky footer: Paid Amount / Due-Change / Hold & Pay ================= --}}
            <div class="pos-sticky-footer">
                <div class="pos-footer-field pos-footer-paid">
                    <label for="paidAmountInput">{{ __('common.paid_amount') }}</label>
                    <input type="number" step="0.01" min="0" class="form-control" id="paidAmountInput" placeholder="0.00">
                </div>

                <div class="pos-footer-field pos-footer-due">
                    <label id="paymentRemainingLabel">{{ __('common.due_change') }}</label>
                    <span class="pos-footer-due-value" id="paymentRemaining">0.00</span>
                </div>

                <div class="pos-footer-actions">
                    @if ($pos_setting->enable_hold_order)
                        <button type="button" class="btn pos-hold-btn" id="holdOrderBtn">
                            <i class="fa fa-pause"></i> {{ __('common.hold') }} <span class="pos-key-hint">(F6)</span>
                        </button>
                    @endif
                    <button type="button" class="btn pos-pay-btn" id="completeSaleBtn">
                        <i class="fa fa-check"></i> {{ __('common.pay') }} <span class="pos-key-hint">(F9)</span>
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
                    <h5 class="modal-title" id="productPickerTitle">{{ __('pos.select_variation') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="productPickerGrid" class="product-grid product-picker-grid"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="serialPickerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('pos.select_serials') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted mb-2" id="serialPickerHint"></p>
                    <div id="serialPickerList" style="max-height:320px; overflow-y:auto;"></div>
                    <div class="mt-2">
                        <input type="text" class="form-control d-none" id="posSerialScanHelperInput">
                        @include('admin.partials.barcode_scanner', ['targetInputId' => '#posSerialScanHelperInput'])
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="serialPickerSaveBtn">{{ __('common.save') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= {{ __('pos.change_branch') }} Modal (switches business/branch/warehouse
         context without leaving the POS screen - submits to the same
         pos-screen.context route the original full-page picker used) ================= --}}
    @if (!$is_fixed_context)
        <div class="modal fade" id="changeBranchModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">{{ __('pos.change_branch') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <form action="{{ route('pos-screen.context') }}" method="POST">
                        @csrf
                        <div class="modal-body">
                            @if ($is_superadmin)
                                <div class="mb-3">
                                    <label class="form-label">{{ __('common.business') }} <span class="text-danger">*</span></label>
                                    <select class="form-select select2" name="business_id" id="changeBranchBusinessId" required>
                                        <option value="">--Select Business--</option>
                                        @foreach ($context_businesses as $item)
                                            <option value="{{ $item->business_id }}" {{ $item->business_id == $business_id ? 'selected' : '' }}>{{ $item->name }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="mb-3">
                                <label class="form-label">{{ __('common.branch') }} <span class="text-danger">*</span></label>
                                <select class="form-select select2" name="branch_id" id="changeBranchBranchId" required>
                                    <option value="">--Select Branch--</option>
                                    @foreach ($context_branches as $item)
                                        <option value="{{ $item->branch_id }}" {{ $item->branch_id == $branch_id ? 'selected' : '' }}>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="mb-1">
                                <label class="form-label">{{ __('common.warehouse') }}</label>
                                <select class="form-select select2" name="warehouse_id" id="changeBranchWarehouseId">
                                    <option value="">--Select Warehouse--</option>
                                    @foreach ($context_warehouses as $item)
                                        <option value="{{ $item->warehouse_id }}" {{ $item->warehouse_id == $warehouse_id ? 'selected' : '' }}>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                <small class="text-muted">{{ __('pos.warehouse_context_hint') }}</small>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="submit" class="btn btn-primary">{{ __('pos.switch_branch') }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    @endif

    {{-- ================= {{ __('common.add_customer') }} Modal (quick create, stays on POS screen) ================= --}}
    <div class="modal fade" id="addCustomerModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('common.add_customer') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.name') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="new_customer_name">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.email') }} <span class="text-danger">*</span></label>
                        <input type="email" class="form-control" id="new_customer_email">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">{{ __('common.phone') }}</label>
                        <input type="text" class="form-control" id="new_customer_phone">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="addCustomerSubmitBtn">{{ __('common.save_customer') }}</button>
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
                    <h5 class="modal-title">{{ __('pos.credit_sale_title') }}</h5>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.customer') }}</label>
                        <div class="form-control-plaintext fw-bold" id="creditCustomerName"></div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.due_date') }}</label>
                        <input type="date" class="form-control" id="creditDueDate">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">{{ __('common.note') }}</label>
                        <textarea class="form-control" id="creditNote" rows="2" placeholder="{{ __('pos.optional_reference_note') }}"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" id="creditPaymentSkipBtn">{{ __('pos.skip') }}</button>
                    <button type="button" class="btn btn-primary" id="creditPaymentSaveBtn">{{ __('common.save') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Same-day manager correction requires a reason before reverse/repost. --}}
    <div class="modal fade" id="correctionReasonModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('pos.correction_reason_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted small mb-2">
                        This will reverse stock and accounting for the posted sale, apply your cart changes, and repost on the same order number.
                    </p>
                    <label class="form-label">{{ __('common.reason') }} <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="correction_reason" rows="3" maxlength="1000" placeholder="{{ __('pos.correction_reason_placeholder') }}"></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="button" class="btn btn-primary" id="correctionReasonSubmitBtn">{{ __('pos.apply_correction') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Open Register Session Modal ================= --}}
    <div class="modal fade" id="openSessionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('pos.open_session_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    @if ($pos_setting->register_mode == 'manual')
                        <div class="mb-3">
                            <label class="form-label">{{ __('common.register') }} <span class="text-danger">*</span></label>
                            <select class="form-select select2" id="open_pos_register_id">
                                <option value="">{{ __('pos.select_register') }}</option>
                                @foreach ($registers as $item)
                                    <option value="{{ $item->pos_register_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <div class="mb-3">
                        <label class="form-label">{{ __('pos.opening_cash') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="opening_cash" value="0">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('pos.opening_notes') }}</label>
                        <textarea class="form-control" id="opening_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="openSessionSubmitBtn">{{ __('pos.open_session') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Close Register Session Modal ================= --}}
    <div class="modal fade" id="closeSessionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('pos.close_session_title') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <table class="table table-sm" id="closeSummaryTable">
                        <tbody>
                            <tr><th>{{ __('pos.opening_cash') }}</th><td class="text-end" id="sumOpeningCash">0.00</td></tr>
                            <tr><th>{{ __('pos.cash_sales') }}</th><td class="text-end" id="sumCashSales">0.00</td></tr>
                            <tr><th>{{ __('pos.cash_refunds') }}</th><td class="text-end" id="sumCashRefunds">0.00</td></tr>
                            <tr><th>{{ __('pos.cash_in') }}</th><td class="text-end" id="sumCashIn">0.00</td></tr>
                            <tr><th>{{ __('pos.cash_out') }}</th><td class="text-end" id="sumCashOut">0.00</td></tr>
                            <tr><th>{{ __('pos.expenses_minus') }}</th><td class="text-end" id="sumExpenses">0.00</td></tr>
                            <tr class="fw-bold"><th>{{ __('pos.expected_cash') }}</th><td class="text-end" id="sumExpectedCash">0.00</td></tr>
                        </tbody>
                    </table>
                    <div class="mb-3">
                        <label class="form-label">{{ __('pos.actual_cash') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control" id="actual_cash">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('pos.closing_notes') }}</label>
                        <textarea class="form-control" id="closing_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-danger" id="closeSessionSubmitBtn">{{ __('pos.close_session') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Cash Movement Modal ================= --}}
    <div class="modal fade" id="cashMovementModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cashMovementModalTitle">{{ __('pos.cash_movement') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cash_movement_type" value="in">
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="cash_movement_amount">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.reason') }} <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="cash_movement_reason" maxlength="255">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="cashMovementSubmitBtn">{{ __('common.save') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Add Expense Modal (quick log against active session) ================= --}}
    <div class="modal fade" id="addExpenseModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">{{ __('pos.add_expense') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.category') }} <span class="text-danger">*</span></label>
                        <select class="form-select select2" id="expense_category_id">
                            <option value="">{{ __('common.select_category') }}</option>
                            @foreach ($expense_categories as $item)
                                <option value="{{ $item->expense_category_id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">{{ __('common.amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0.01" class="form-control" id="expense_amount">
                    </div>
                    <div class="mb-1">
                        <label class="form-label">{{ __('common.description') }}</label>
                        <input type="text" class="form-control" id="expense_description">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="addExpenseSubmitBtn">{{ __('pos.save_expense') }}</button>
                </div>
            </div>
        </div>
    </div>

    {{-- ================= Held Orders Offcanvas ================= --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="heldOrdersOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">{{ __('pos.hold_orders') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div id="heldOrdersList" class="list-group">
                <div class="text-muted text-center py-3">{{ __('pos.no_hold_orders') }}</div>
            </div>
        </div>
    </div>

    {{-- ================= Reports Offcanvas (my register sessions - non-transactional) ================= --}}
    <div class="offcanvas offcanvas-end" tabindex="-1" id="posReportsOffcanvas">
        <div class="offcanvas-header">
            <h5 class="offcanvas-title">{{ __('pos.my_register_sessions') }}</h5>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas"></button>
        </div>
        <div class="offcanvas-body">
            <div id="posReportsList" class="list-group mb-3">
                <div class="text-muted text-center py-3">{{ __('pos.no_sessions_found') }}</div>
            </div>
            <div id="posReportsSummary" class="d-none">
                <hr>
                <h6>{{ __('pos.session_summary') }}</h6>
                <div class="table-responsive">
                    <table class="table table-sm" id="posReportsSummaryTable">
                        <thead>
                            <tr><th>{{ __('pos.detail') }}</th><th class="text-end">{{ __('pos.orders_col') }}</th><th class="text-end">{{ __('common.amount') }}</th></tr>
                        </thead>
                        <tbody>
                            <tr class="fw-bold"><td>{{ __('common.total') }}</td><td class="text-end" id="repTotalOrders">0</td><td class="text-end" id="repTotalSales">0.00</td></tr>
                        </tbody>
                        <tbody id="repPaymentRows"></tbody>
                        <tbody id="repSourceRows"></tbody>
                        <tbody>
                            <tr><td>{{ __('common.discount') }}</td><td class="text-end" id="repDiscountOrderCount">0</td><td class="text-end" id="repTotalDiscount">0.00</td></tr>
                            <tr><td>{{ __('common.tax') }}</td><td class="text-end" id="repTaxOrderCount">0</td><td class="text-end" id="repTotalTax">0.00</td></tr>
                            <tr><td>{{ __('pos.opening_amount') }}</td><td class="text-end">-</td><td class="text-end" id="repOpeningCash">0.00</td></tr>
                            <tr><td>{{ __('pos.cash_refunds') }}</td><td class="text-end">-</td><td class="text-end" id="repCashRefunds">0.00</td></tr>
                            <tr><td>{{ __('pos.cash_in') }}</td><td class="text-end">-</td><td class="text-end" id="repCashIn">0.00</td></tr>
                            <tr><td>{{ __('pos.cash_out') }}</td><td class="text-end">-</td><td class="text-end" id="repCashOut">0.00</td></tr>
                            <tr><td>{{ __('pos.expenses_minus') }}</td><td class="text-end">-</td><td class="text-end" id="repExpenses">0.00</td></tr>
                            <tr class="fw-bold"><td>{{ __('pos.cash_amount') }}</td><td class="text-end">-</td><td class="text-end" id="repExpectedCash">0.00</td></tr>
                            <tr><td>{{ __('pos.actual') }}</td><td class="text-end">-</td><td class="text-end" id="repActualCash">0.00</td></tr>
                        </tbody>
                    </table>
                </div>
                <button type="button" class="btn btn-sm btn-outline-secondary" id="printSessionSummaryBtn">
                    <i class="fa fa-print"></i> {{ __('pos.thermal_print') }}
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
            // Only the piece the "Use Loyalty Points" control needs client-
            // side (the redemption rate, to show "~Rs X" next to the points
            // balance) - the redemption cap/eligibility itself is always
            // resolved server-side (LoyaltyPointService::calculateRedemption()).
            'customer_setting' => [
                'loyalty_program' => (bool) ($customer_setting->loyalty_program ?? false),
                'loyalty_redemption_value' => $customer_setting->loyalty_redemption_value ?? 0,
            ],
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
                'available_serials' => url('admin/order/available-serials'),
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
        window.i18n_pos = @json(array_merge(trans('pos'), [
            'cash' => __('common.cash'),
            'hold' => __('common.hold'),
            'pay' => __('common.pay'),
            'save' => __('common.save'),
            'cancel' => __('common.cancel'),
            'amount' => __('common.amount'),
            'unit' => __('common.unit'),
            'loading' => __('common.loading'),
            'cart_empty' => __('common.cart_empty'),
            'select_branch' => __('common.select_branch'),
            'select_warehouse' => __('common.select_warehouse'),
            'register' => __('common.register'),
            'manual' => __('common.manual'),
        ]));
    </script>
    <script src="{{ asset('public/assets/js/admin/pos-screen.js') }}"></script>
@endsection
