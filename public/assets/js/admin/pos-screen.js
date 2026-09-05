/**
 * POS Screen - cashier-facing product search / cart / checkout UI.
 *
 * Relies on the app-wide ajaxRequest()/successMessage()/errorMessage()
 * helpers (public/assets/js/universal.js) and window.POS_CONFIG injected by
 * resources/views/admin/pos/screen/index.blade.php.
 */
(function () {
    'use strict';

    var CFG = window.POS_CONFIG || {};
    var PERM = CFG.permissions || {};
    var SETTING = CFG.pos_setting || {};
    var CUSTOMER_SETTING = CFG.customer_setting || {};
    var URLS = CFG.urls || {};

    var state = {
        session: null,
        cart: [], // {line_key, product_variation_id, product_name, variation_name, unit_id, unit_name, quantity, unit_price, discount, notes, image}
        payments: [],
        order_id: null,
        order_daily_id: null,
        line_seq: 0,
        cash_movement_open_modal: null,
        close_session_modal: null,
        open_session_modal: null,
        held_orders_offcanvas: null,
        reorder_applied: false,
        active_category_id: '',
        product_picker_modal: null,
        picker: {
            product: null,
            variations: [],
        },
        payment_mode: null, // null | 'single' | 'multi'
        selected_payment_method_id: null,
        reports_viewed_session_id: null,
        default_sale_type_id: null,
        credit_payment_modal: null,
        credit_payment_order_id: null,
        cash_movement_request_id: null,
        cash_movement_submitting: false,
        correction_mode: false,
        correction_reason_modal: null,
    };


    function t(key, fallback) {
        return (window.i18n_pos && window.i18n_pos[key]) || fallback;
    }

    function tr(key, fallback, replacements) {
        var s = t(key, fallback);
        if (replacements) {
            Object.keys(replacements).forEach(function (k) {
                s = s.split(':' + k).join(String(replacements[k]));
            });
        }
        return s;
    }

    function can(perm) {
        return !!PERM[perm];
    }

    // Price editing needs both the business-level master switch (POS
    // Settings -> Allow Price Change in Cart) and the per-user permission -
    // the setting can veto everyone regardless of permission, matching
    // OrderService::saveLinesAndComputeTotals()'s server-side enforcement.
    function canChangePrice() {
        return !!SETTING.allow_price_change_in_cart && can('order.price.change');
    }

    // Selling below a variation's Minimum Selling Price requires price
    // editing to be enabled in the first place, the separate "Allow Price
    // Below Minimum Selling Price" business setting to be on, AND the
    // order.price.override-minimum permission - matches
    // OrderService::saveLinesAndComputeTotals()'s server-side enforcement.
    // Replaces the old per-sale #overrideMinPriceCheck checkbox.
    function canOverrideMinPrice() {
        return canChangePrice() && !!SETTING.allow_price_below_minimum && can('order.price.override-minimum');
    }

    // Business-level "allow selling below/at zero stock" toggle
    // (Settings -> Inventory -> Negative Stock), surfaced read-only via
    // POS_CONFIG.allow_negative_stock (see PosScreenController::index()).
    // When true, every client-side stock guard below is a no-op - the
    // server (OrderService::save()/post()) allows overselling too, matching
    // this business's explicit choice.
    function allowsOutOfStockSale() {
        return !!CFG.allow_negative_stock;
    }

    // Small "N in stock" / "Out of stock" hint - null/undefined
    // available_stock means the product isn't stock-tracked (unlimited),
    // so no hint is shown at all.
    function stockHint(available_stock) {
        if (available_stock === null || available_stock === undefined) {
            return '';
        }
        return available_stock > 0
            ? '<span class="pos-stock-hint">' + tr('in_stock', ':qty in stock', {qty: available_stock}) + '</span>'
            : '<span class="pos-stock-hint pos-stock-hint-out">' + t('out_of_stock', 'Out of stock') + '</span>';
    }

    // Registers the qty (existing cart quantity + any quantity about to be
    // added) a stock-tracked pv/line can't exceed when out-of-stock selling
    // is off. Returns an error message string to block the action, or null
    // to allow it. Mirrors updateLineFromRow()'s existing client-side
    // minimum-price check - a UX-only mirror of the server's authoritative
    // enforcement (OrderService::saveLinesAndComputeTotals()/post()), which
    // always re-validates regardless of what the client does.
    function stockBlockMessage(name, is_track_stock, available_stock, requestedQty) {
        if (allowsOutOfStockSale() || !is_track_stock) {
            return null;
        }

        available_stock = parseFloat(available_stock) || 0;

        if (available_stock <= 0) {
            return tr('product_out_of_stock', '":name" is out of stock.', {name: name});
        }

        if (requestedQty > available_stock) {
            return tr('only_n_available', 'Only :qty of ":name" available in stock.', {qty: available_stock, name: name});
        }

        return null;
    }

    function money(v) {
        v = parseFloat(v || 0);
        if (isNaN(v)) v = 0;
        return v.toFixed(2);
    }

    // Client-generated idempotency key for a single logical submission (e.g.
    // one cash-in/cash-out) - reused across retries of the same submit so a
    // double-click or network retry resolves to the original record server-side
    // instead of creating a duplicate. Native crypto.randomUUID() when
    // available (every modern/localhost browser); falls back to a
    // timestamp+random string otherwise.
    function generateRequestId() {
        if (window.crypto && typeof window.crypto.randomUUID === 'function') {
            return window.crypto.randomUUID();
        }
        return 'req-' + Date.now() + '-' + Math.random().toString(16).slice(2);
    }

    // ==============================
    // INIT
    // ==============================
    $(document).ready(function () {
        $('.select2').not('#open_pos_register_id, #customer_id, #changeBranchBusinessId, #changeBranchBranchId, #changeBranchWarehouseId, #expense_category_id').select2();

        // Same reasoning as #open_pos_register_id below - scope this dropdown
        // to the Add Expense modal so it opens correctly.
        if ($('#expense_category_id').length) {
            $('#expense_category_id').select2({
                dropdownParent: $('#addExpenseModal'),
            });
        }

        // Same reasoning as #open_pos_register_id above - scope this dropdown
        // to the Change Branch modal so it opens correctly.
        $('#changeBranchBusinessId, #changeBranchBranchId, #changeBranchWarehouseId').select2({
            dropdownParent: $('#changeBranchModal'),
        });

        // Select2 appends its dropdown to <body> by default, which renders it
        // behind/outside the Bootstrap modal (and outside its focus trap) -
        // clicks land on the modal backdrop instead of the option list. Scope
        // this dropdown to the modal itself so it opens correctly.
        $('#open_pos_register_id').select2({
            dropdownParent: $('#openSessionModal'),
        });

        initCustomerSelect();

        state.open_session_modal = new bootstrap.Modal(document.getElementById('openSessionModal'));
        state.close_session_modal = new bootstrap.Modal(document.getElementById('closeSessionModal'));
        state.cash_movement_modal = new bootstrap.Modal(document.getElementById('cashMovementModal'));
        state.held_orders_offcanvas = new bootstrap.Offcanvas(document.getElementById('heldOrdersOffcanvas'));
        state.pos_reports_offcanvas = new bootstrap.Offcanvas(document.getElementById('posReportsOffcanvas'));
        state.product_picker_modal = new bootstrap.Modal(document.getElementById('productPickerModal'));
        state.add_customer_modal = new bootstrap.Modal(document.getElementById('addCustomerModal'));
        state.credit_payment_modal = new bootstrap.Modal(document.getElementById('creditPaymentModal'), { backdrop: 'static', keyboard: false });
        if ($('#correctionReasonModal').length) {
            state.correction_reason_modal = new bootstrap.Modal(document.getElementById('correctionReasonModal'), { backdrop: 'static' });
        }
        if ($('#addExpenseModal').length) {
            state.add_expense_modal = new bootstrap.Modal(document.getElementById('addExpenseModal'));
        }
        if ($('#changeBranchModal').length) {
            state.change_branch_modal = new bootstrap.Modal(document.getElementById('changeBranchModal'));
        }

        state.default_sale_type_id = $('#sale_type_id').val() || null;

        renderPaymentMethodTiles();
        selectDefaultPaymentMethod();
        updateCheckoutSummary();
        bootstrapSession();
        wireEvents();
    });

    // ==============================
    // REGISTER SESSION BOOTSTRAP
    // ==============================
    function bootstrapSession() {
        $('#posNoSessionChecking').removeClass('d-none');
        $('#posNoSessionBrowseOnly').addClass('d-none');

        ajaxRequest({ url: URLS.session_current })
            .then(function (response) {
                if (response.Data) {
                    state.session = response.Data;
                    onSessionReady();
                } else {
                    state.session = null;
                    showBrowseOnly();
                }
            })
            .catch(function (err) {
                state.session = null;
                errorMessage(err.Message || t('unable_check_session', 'Unable to check register session.'));
                showBrowseOnly();
            });
    }

    // No open session (manual mode not opened yet, or automatic mode outside
    // its business-hours window): cart/checkout stay hidden, but the header's
    // Order History / Reports buttons remain usable, and the Open Register
    // modal is reachable (and dismissible) from here.
    function showBrowseOnly() {
        $('#posScreenBody').hide();
        $('#posNoSessionArea').show();
        $('#posNoSessionChecking').addClass('d-none');
        $('#posNoSessionBrowseOnly').removeClass('d-none');
        $('#registerBadge').addClass('d-none');
        $('#cashInBtn, #cashOutBtn, #closeRegisterBtn, #addExpenseBtn').addClass('d-none');
        state.open_session_modal.show();
    }

    function onSessionReady() {
        $('#posNoSessionArea').hide();
        $('#posScreenBody').show();

        var registerName = (state.session.register && state.session.register.name) || t('register', 'Register');
        $('#registerBadge')
            .removeClass('d-none')
            .html(escapeHtml(registerName) + ' <span class="pos-register-status-pill">' + t('session_open_status', 'OPEN') + '</span>');

        $('#cashInBtn, #cashOutBtn, #closeRegisterBtn').removeClass('d-none');
        if (can('expense.access')) {
            $('#addExpenseBtn').removeClass('d-none');
        }

        loadHeldOrdersCount();
        loadProductsByCategory('');

        if (CFG.correct_order_id) {
            if (!can('order.correct')) {
                errorMessage(t('no_correct_permission', 'You do not have permission to correct orders.'));
            } else {
                loadCorrectionOrder(CFG.correct_order_id);
            }
        } else if (CFG.reorder_from) {
            reorderFromOrder(CFG.reorder_from);
        }
    }

    function wireEvents() {
        $('#openSessionSubmitBtn').on('click', submitOpenSession);
        $('#openRegisterFromBrowseBtn').on('click', function () { state.open_session_modal.show(); });
        $('#posReportsBtn').on('click', function () {
            loadPosReports();
            state.pos_reports_offcanvas.show();
        });
        $('#printSessionSummaryBtn').on('click', function () {
            if (!state.reports_viewed_session_id) {
                errorMessage(t('select_session_first', 'Select a session first.'));
                return;
            }
            window.open(URLS.session_summary_print + '/' + state.reports_viewed_session_id + '/print', '_blank');
        });
        $('#cashInBtn').on('click', function () { openCashMovementModal('in'); });
        $('#cashOutBtn').on('click', function () { openCashMovementModal('out'); });
        $('#cashMovementSubmitBtn').on('click', submitCashMovement);
        $('#addExpenseBtn').on('click', openAddExpenseModal);
        $('#addExpenseSubmitBtn').on('click', submitAddExpense);
        $('#closeRegisterBtn').on('click', openCloseSessionModal);
        $('#closeSessionSubmitBtn').on('click', submitCloseSession);

        var searchTimer = null;
        $('#productSearchInput').on('input', function () {
            var term = $(this).val().trim();
            clearTimeout(searchTimer);

            if (!term) {
                $('#productSearchResults').hide().empty();
                $('#posProductGrid, #posProductGridEmpty').show();
                return;
            }

            $('#posProductGrid, #posProductGridEmpty').hide();

            searchTimer = setTimeout(function () {
                searchProducts(term, false);
            }, 300);
        });

        $('#productSearchInput').on('keydown', function (e) {
            if (e.key === 'Enter' || e.keyCode === 13) {
                e.preventDefault();
                var term = $(this).val().trim();
                if (!term) return;
                clearTimeout(searchTimer);
                $('#posProductGrid, #posProductGridEmpty').hide();
                searchProducts(term, true);
            }
        });

        var voucherSearchTimer = null;
        $('#voucher_code').on('input', function () {
            var term = $(this).val().trim();
            // Free-typing invalidates whatever was picked from the dropdown -
            // the exact-code Apply fallback still works via voucher_code alone.
            $('#voucher_id').val('');
            clearTimeout(voucherSearchTimer);

            clearVoucherFeedback();

            if (!term) {
                $('#voucherSearchResults').hide().empty();
                return;
            }

            voucherSearchTimer = setTimeout(function () {
                searchVouchers(term);
            }, 300);
        });

        $('#posCategoryRail').on('click', '.category-rail-item', function () {
            var category_id = $(this).data('category-id') || '';
            $('#posCategoryRail .category-rail-item').removeClass('active');
            $(this).addClass('active');
            $('#productSearchInput').val('');
            $('#productSearchResults').hide().empty();
            loadProductsByCategory(category_id);
        });

        $('#posProductGrid').on('click', '.product-card', function () {
            var product = $(this).data('product');
            if (!product) return;

            if ($(this).hasClass('product-card-out-of-stock')) {
                errorMessage(tr('product_out_of_stock', '":name" is out of stock.', {name: product.name || t('this_product', 'This product')}));
                return;
            }

            handleGridProductClick(product);
        });

        $('#productPickerGrid').on('click', '.product-card', function () {
            var idx = $(this).data('idx');
            var pv = state.picker.variations[idx];
            if (!pv) return;

            if (addProductToCart(pv, { image: firstImageOf(state.picker.product) })) {
                state.product_picker_modal.hide();
            }
        });

        $('#cartRows').on('input change', '.line-qty, .line-price, .line-discount', function () {
            var key = $(this).closest('.cart-line').data('key');
            updateLineFromRow(key, $(this).closest('.cart-line'));
        });

        $('#cartRows').on('change', '.line-sale-type', function () {
            var key = $(this).closest('.cart-line').data('key');
            var line = state.cart.find(function (l) { return l.line_key === key; });
            if (!line) return;

            line.sale_type_id = $(this).val() || null;

            if (!line.manual_override) {
                repriceLineForSaleType(line);
            } else {
                // Price stays as manually entered, but the row still needs to
                // re-render so the select reflects the new value and the
                // "different from order" indicator (see renderCart()) updates.
                renderCart();
            }
        });

        $('#cartRows').on('click', '.line-remove', function () {
            var key = $(this).closest('.cart-line').data('key');
            state.cart = state.cart.filter(function (l) { return l.line_key !== key; });
            renderCart();
        });

        $('#cartRows').on('click', '.manage-serials', function () {
            var key = $(this).closest('.cart-line').data('key');
            openSerialPickerForEdit(key);
        });

        $('#cartRows').on('click', '.qty-inc, .qty-dec', function () {
            var $row = $(this).closest('.cart-line');
            var $qty = $row.find('.line-qty');
            var qty = parseFloat($qty.val()) || 0;
            qty = $(this).hasClass('qty-inc') ? qty + 1 : Math.max(0.01, qty - 1);
            $qty.val(qty).trigger('change');
        });

        $('#clearCartBtn').on('click', function () {
            if (!state.cart.length) return;
            state.cart = [];
            // Explicit "start a new sale" gesture - must also drop order_id,
            // otherwise the next Hold/Pay would silently update (overwrite)
            // whatever order was held/being-edited before this cart was
            // cleared, instead of creating a genuinely new one.
            state.order_id = null;
            state.order_daily_id = null;
            renderCart();
        });

        $('#customer_id').on('change', function () {
            updateCreditHint();
            updateCreditCustomerSummary();
            updateLoyaltyPointsHint();
            // Store Credit's tile/row visibility depends on the selected
            // customer's balance, unlike every other payment type - re-render
            // whenever the customer changes so switching to/from a customer
            // with a balance shows/hides it immediately.
            renderPaymentMethodTiles();
            if (state.payment_mode === 'multi') {
                renderPayments();
            }
        });
        updateCreditHint();
        updateLoyaltyPointsHint();

        $('#creditCustomerChangeLink').on('click', function () {
            openCustomerSelect();
        });

        $('#addPaymentRowBtn').on('click', function () {
            state.payments.push({ payment_method_id: '', amount: 0, reference_no: '' });
            renderPayments();
        });

        $('#applyVoucherBtn').on('click', function () {
            $('#voucherSearchResults').hide().empty();
            recalcLocal();
            previewVoucherApply();
        });

        $('#browseVouchersBtn').on('click', function () {
            browseEligibleVouchers();
        });

        $('#discount_id').on('change', function () {
            recalcLocal();
            previewVoucherApply();
        });

        $('#use_loyalty_points').on('change', function () {
            recalcLocal();
            previewVoucherApply();
        });

        $('#holdOrderBtn').on('click', holdOrder);
        $('#completeSaleBtn').on('click', completeSale);
        $('#correctionReasonSubmitBtn').on('click', submitCorrectionWithReason);
        $('#cancelCorrectionBtn').on('click', cancelCorrectionMode);

        $('#heldOrdersBtn').on('click', function () {
            loadHeldOrders();
            state.held_orders_offcanvas.show();
        });

        // ---- Order type / source pills (mirror the hidden <select>s) ----
        $('.pos-pill-group').on('click', '.pos-pill', function () {
            var $group = $(this).closest('.pos-pill-group');
            var $select = $('#' + $group.data('select-target'));
            $select.val($(this).data('value')).trigger('change');
        });

        $('#order_type_id, #order_source_id, #sale_type_id, #paymentMethodSelect').on('change', syncPillsFromSelect);
        syncPillsFromSelect();

        // The visible #saleTypeSelect drives the hidden #sale_type_id (the
        // field the rest of the screen reads from) - changing it reprices
        // and force-syncs every cart line (see repriceCartForSaleType()).
        $('#saleTypeSelect').on('change', function () {
            $('#sale_type_id').val($(this).val()).trigger('change');
        });

        $('#sale_type_id').on('change', repriceCartForSaleType);

        $('#posCheckoutToggle').on('click', function () {
            toggleCheckoutPanel();
        });

        $('#order_type_id').on('change', updateDeliveryAddressVisibility);
        updateDeliveryAddressVisibility();

        // ---- Change Branch modal (absent entirely for fixed-context roles).
        // Shared by the Row 1 branch field and the no-session browse screen's
        // fallback button, so branch switching stays reachable even before a
        // register session is open. ----
        if (state.change_branch_modal) {
            $('.js-change-branch-btn').on('click', function () {
                state.change_branch_modal.show();
            });

            $('#changeBranchBusinessId').on('change', function () {
                var business_id = $(this).val();
                $('#changeBranchBranchId').html('<option value="">' + t('select_branch', '--Select Branch--') + '</option>');
                $('#changeBranchWarehouseId').html('<option value="">' + t('select_warehouse', '--Select Warehouse--') + '</option>');
                if (!business_id) return;

                ajaxRequest({ url: url_local + '/admin/pos-screen/context-options/' + business_id })
                    .then(function (response) {
                        var data = response.Data;
                        var branchOptions = '<option value="">--Select Branch--</option>';
                        (data.branches || []).forEach(function (item) {
                            branchOptions += '<option value="' + item.branch_id + '">' + escapeHtml(item.name) + '</option>';
                        });
                        $('#changeBranchBranchId').html(branchOptions);

                        var warehouseOptions = '<option value="">--Select Warehouse--</option>';
                        (data.warehouses || []).forEach(function (item) {
                            warehouseOptions += '<option value="' + item.warehouse_id + '">' + escapeHtml(item.name) + '</option>';
                        });
                        $('#changeBranchWarehouseId').html(warehouseOptions);
                    })
                    .catch(function (err) {
                        errorMessage(err.Message || t('unable_load_branches', 'Unable to load branches.'));
                    });
            });
        }

        // ---- Add Customer modal ----
        $('#addCustomerBtn').on('click', function () {
            $('#new_customer_name, #new_customer_email, #new_customer_phone').val('');
            state.add_customer_modal.show();
        });
        $('#addCustomerSubmitBtn').on('click', submitAddCustomer);

        // ---- Add Order Type modal (quick-add, mirrors #addCustomerModal) ----
        // The visible UI is the pill-button row, not the hidden #order_type_id
        // select directly, so on success a new pill is appended alongside the
        // option (mirrors how a new payment method pill is added in
        // renderPaymentMethodOptions()) and then selected via the same
        // #order_type_id change -> syncPillsFromSelect() path every other
        // order-type change already goes through.
        initQuickAdd({
            modalId: '#quickAddOrderTypeModal',
            formId: '#quickAddOrderTypeForm',
            url: url_local + '/admin/order-type',
            valueField: 'order_type_id',
            labelField: 'name',
            targetSelectIds: ['order_type_id'],
            onSuccess: function (data) {
                $('.pos-field-ordertype .pos-pill-buttons').append(
                    '<button type="button" class="pos-pill" data-value="' + data.order_type_id + '" data-code="' +
                        (data.code || '') + '">' + escapeHtml(data.name || '') + '</button>'
                );
                // The pill didn't exist yet when the select's own change
                // event ran syncPillsFromSelect() a moment ago, so re-run it
                // now that the pill is in the DOM to mark it active.
                syncPillsFromSelect();
            },
        });

        // ---- Credit Payment modal (shown after a Credit-type sale completes) ----
        $('#creditPaymentSaveBtn').on('click', function () { submitCreditInfo(true); });
        $('#creditPaymentSkipBtn').on('click', function () { submitCreditInfo(false); });

        // ---- Product search: scan button just re-focuses the input (the
        // scanner itself is a keyboard-wedge device that types into it) ----
        $('#scanFocusBtn').on('click', function () {
            $('#productSearchInput').trigger('focus');
        });

        // ---- Payment method dropdown ----
        $('#paymentMethodSelect').on('change', function () {
            var value = $(this).val();

            if (!value) {
                resetPaymentSelection();
                state.payments = [];
                recalcPayments();
                return;
            }

            selectPaymentTile(value === MULTI_PAY_VALUE ? null : value, value === MULTI_PAY_VALUE);
        });

        $('#paidAmountInput').on('input', function () {
            var amount = parseFloat($(this).val()) || 0;

            // A resumed Hold/Draft order with no saved payments leaves
            // state.payments empty and no method pre-selected (see
            // loadCartFromDetails()) - typing an amount here before picking
            // a tile must still land in state.payments instead of being
            // silently dropped, otherwise completeSale()'s total check sees
            // 0 entered even though this field shows the full amount.
            if (!state.payments.length) {
                state.payments = [{ payment_method_id: state.selected_payment_method_id || '', amount: amount, reference_no: '' }];
            } else {
                state.payments[0].amount = amount;
            }

            recalcPayments();
        });

        // ---- Keyboard shortcuts matching the on-screen (F3/F6/F9) hints ----
        $(document).on('keydown', function (e) {
            if (e.key === 'F3') {
                e.preventDefault();
                openCustomerSelect();
            } else if (e.key === 'F6') {
                e.preventDefault();
                $('#holdOrderBtn').trigger('click');
            } else if (e.key === 'F9') {
                e.preventDefault();
                $('#completeSaleBtn').trigger('click');
            }
        });
    }

    function syncPillsFromSelect() {
        $('.pos-pill-group').each(function () {
            var $group = $(this);
            var value = $('#' + $group.data('select-target')).val();
            $group.find('.pos-pill').each(function () {
                $(this).toggleClass('active', String($(this).data('value')) === String(value));
            });
        });

        // #saleTypeSelect isn't a pill group, but it needs the same
        // sync-on-every-#sale_type_id-change treatment (see all call sites
        // of this function), so it's kept in step here too.
        $('#saleTypeSelect').val($('#sale_type_id').val());
    }

    // "Delivery" order types are identified by the seeded order_types.code
    // 'DELIVERY' (see OrderTypeService::$default_types / OrderService::save())
    // - mirrors the server-side check so the field only shows/blocks
    // submission when it will actually be required.
    function formatCustomerLabel(code, name, isWalkin) {
        var label = (code ? code + ' - ' : '') + (name || '');
        return isWalkin ? label + ' (Walk-in)' : label;
    }

    function initCustomerSelect() {
        $('#customer_id').select2({
            placeholder: t('walk_in_customer', 'Walk-in Customer'),
            dropdownParent: $('body'),
            width: '100%',
            minimumResultsForSearch: 0,
            matcher: function (params, data) {
                var term = ($.trim(params.term) || '').toLowerCase();
                if (!term || !data.id) {
                    return data;
                }

                var $opt = data.element ? $(data.element) : null;
                var haystack = [
                    data.text || '',
                    $opt ? ($opt.data('code') || '') : '',
                    $opt ? ($opt.data('phone') || '') : '',
                    $opt ? ($opt.data('email') || '') : '',
                ].join(' ').toLowerCase();

                return haystack.indexOf(term) > -1 ? data : null;
            },
        });
    }

    function openCustomerSelect() {
        $('#customer_id').select2('open');
    }

    function isDeliveryOrderType() {
        return $('#order_type_id').find(':selected').data('code') === 'DELIVERY';
    }

    function updateDeliveryAddressVisibility() {
        var isDelivery = isDeliveryOrderType();
        $('#deliveryAddressWrap').toggleClass('d-none', !isDelivery);
        $('#posCheckoutPanel').toggleClass('is-delivery-order', isDelivery);

        if (isDelivery) {
            toggleCheckoutPanel(true);
        }
    }

    function toggleCheckoutPanel(forceOpen) {
        var $wrap = $('#posCheckoutWrap');
        var opening = typeof forceOpen === 'boolean'
            ? forceOpen
            : $wrap.hasClass('is-collapsed');

        $wrap.toggleClass('is-collapsed', !opening);
        $('#posMainCol').toggleClass('checkout-open', opening);
        $('#posCheckoutToggle').attr('aria-expanded', opening ? 'true' : 'false');
    }

    function updateCheckoutSummary() {
        var label = t('cash', 'Cash');
        var methods = CFG.payment_methods || [];

        if (state.payment_mode === 'multi') {
            label = t('multi_pay', 'Multi Pay');
        } else if (state.selected_payment_method_id) {
            var selected = methods.find(function (m) {
                return m.payment_method_id === state.selected_payment_method_id;
            });
            if (selected) {
                label = selected.name;
            }
        }

        $('#posCheckoutSummary').text(label);
    }

    // ==============================
    // QUICK ADD CUSTOMER
    // ==============================
    function submitAddCustomer() {
        var name = $('#new_customer_name').val().trim();
        var email = $('#new_customer_email').val().trim();
        var phone = $('#new_customer_phone').val().trim();

        if (!name || !email) {
            errorMessage(t('name_email_required', 'Name and Email are required.'));
            return;
        }

        ajaxRequest({
            url: URLS.quick_customer,
            method: 'POST',
            data: { name: name, email: email, phone: phone },
        })
            .then(function (response) {
                var customer = response.Data;

                var $option = $('<option></option>')
                    .attr('value', customer.user_id)
                    .attr('data-code', customer.code || '')
                    .attr('data-credit-limit', customer.credit_limit || 0)
                    .attr('data-walkin', customer.is_walkin ? 1 : 0)
                    .attr('data-credit-days', customer.credit_days || 0)
                    .attr('data-store-credit-balance', customer.store_credit_balance || 0)
                    .attr('data-loyalty-points', customer.loyalty_points || 0)
                    .attr('data-phone', customer.phone || '')
                    .attr('data-email', customer.email || '')
                    .text(formatCustomerLabel(customer.code, customer.name, customer.is_walkin));

                $('#customer_id').append($option).val(customer.user_id).trigger('change');

                state.add_customer_modal.hide();
                successMessage(t('customer_added', 'Customer added.'));
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_add_customer', 'Unable to add customer.'));
            });
    }

    // ==============================
    // OPEN / CLOSE SESSION
    // ==============================
    function submitOpenSession() {
        var opening_cash = $('#opening_cash').val();

        if (opening_cash === '' || isNaN(opening_cash)) {
            errorMessage(t('valid_opening_cash', 'Please enter a valid opening cash amount.'));
            return;
        }

        var data = {
            opening_cash: opening_cash,
            opening_notes: $('#opening_notes').val(),
            business_id: CFG.business_id,
            branch_id: CFG.branch_id,
        };

        if (SETTING.register_mode === 'manual') {
            var register_id = $('#open_pos_register_id').val();
            if (!register_id) {
                errorMessage(t('please_select_register', 'Please select a register.'));
                return;
            }
            data.pos_register_id = register_id;
        }

        ajaxRequest({ url: URLS.session_open, method: 'POST', data: data })
            .then(function (response) {
                state.session = response.Data;
                state.open_session_modal.hide();
                successMessage(t('session_opened', 'Register session opened.'));
                onSessionReady();
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_open_session', 'Unable to open register session.'));
            });
    }

    function openCashMovementModal(type) {
        $('#cash_movement_type').val(type);
        $('#cashMovementModalTitle').text(type === 'in' ? t('add_cash_in', 'Add Cash (In)') : t('remove_cash_out', 'Remove Cash (Out)'));
        $('#cash_movement_amount').val('');
        $('#cash_movement_reason').val('');
        // Fresh key per modal open - reused across retries of this one
        // submission so a double-click/network retry can't create two
        // movements (see PosRegisterSessionService::addCashMovement()).
        state.cash_movement_request_id = generateRequestId();
        state.cash_movement_modal.show();
    }

    function submitCashMovement() {
        var amount = $('#cash_movement_amount').val();
        var reason = $.trim($('#cash_movement_reason').val() || '');

        if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
            errorMessage(t('valid_amount', 'Please enter a valid amount.'));
            return;
        }
        if (!reason) {
            errorMessage(t('cash_movement_reason_required', 'Please enter a reason for this cash movement.'));
            return;
        }
        if (state.cash_movement_submitting) {
            return;
        }

        state.cash_movement_submitting = true;
        $('#cashMovementSubmitBtn').prop('disabled', true);

        ajaxRequest({
            url: URLS.session_cash_movement,
            method: 'POST',
            data: {
                pos_register_session_id: state.session.pos_register_session_id,
                type: $('#cash_movement_type').val(),
                amount: amount,
                reason: reason,
                offline_local_id: state.cash_movement_request_id,
            },
        })
            .then(function () {
                successMessage(t('cash_movement_recorded', 'Cash movement recorded.'));
                state.cash_movement_modal.hide();
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_record_cash_movement', 'Unable to record cash movement.'));
            })
            .finally(function () {
                state.cash_movement_submitting = false;
                $('#cashMovementSubmitBtn').prop('disabled', false);
            });
    }

    // ==============================
    // QUICK ADD EXPENSE
    // ==============================
    function openAddExpenseModal() {
        $('#expense_category_id').val('');
        if ($.fn.select2) {
            $('#expense_category_id').trigger('change');
        }
        $('#expense_amount').val('');
        $('#expense_description').val('');
        state.add_expense_modal.show();
    }

    function submitAddExpense() {
        var category_id = $('#expense_category_id').val();
        var amount = $('#expense_amount').val();

        if (!category_id) {
            errorMessage(t('please_select_expense_category', 'Please select an expense category.'));
            return;
        }
        if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
            errorMessage(t('valid_amount', 'Please enter a valid amount.'));
            return;
        }

        ajaxRequest({
            url: URLS.quick_expense,
            method: 'POST',
            data: {
                pos_register_session_id: state.session.pos_register_session_id,
                expense_category_id: category_id,
                amount: amount,
                description: $('#expense_description').val(),
            },
        })
            .then(function () {
                successMessage(t('expense_recorded', 'Expense recorded.'));
                state.add_expense_modal.hide();
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_record_expense', 'Unable to record expense.'));
            });
    }

    function openCloseSessionModal() {
        ajaxRequest({ url: URLS.session_summary + '/' + state.session.pos_register_session_id })
            .then(function (response) {
                var s = response.Data || {};
                $('#sumOpeningCash').text(money(s.opening_cash));
                $('#sumCashSales').text(money(s.cash_sales));
                $('#sumCashRefunds').text(money(s.cash_refunds));
                $('#sumCashIn').text(money(s.cash_movements_in));
                $('#sumCashOut').text(money(s.cash_movements_out));
                $('#sumExpenses').text(money(s.total_expenses));
                $('#sumExpectedCash').text(money(s.expected_cash));
                $('#actual_cash').val(s.expected_cash != null ? s.expected_cash : '');
                $('#closing_notes').val('');
                state.close_session_modal.show();
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_load_session_summary', 'Unable to load session summary.'));
            });
    }

    function submitCloseSession() {
        var actual_cash = $('#actual_cash').val();

        if (actual_cash === '' || isNaN(actual_cash)) {
            errorMessage(t('enter_actual_cash', 'Please enter the actual cash amount.'));
            return;
        }

        ajaxRequest({
            url: URLS.session_close,
            method: 'POST',
            data: {
                pos_register_session_id: state.session.pos_register_session_id,
                actual_cash: actual_cash,
                closing_notes: $('#closing_notes').val(),
            },
        })
            .then(function () {
                successMessage(t('session_closed', 'Register session closed.'));
                state.close_session_modal.hide();
                state.session = null;
                resetScreenState();
                bootstrapSession();
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_close_session', 'Unable to close register session.'));
            });
    }

    // ==============================
    // PRODUCT SEARCH
    // ==============================
    function searchProducts(term, isScan) {
        ajaxRequest({
            url: URLS.search_products,
            data: {
                business_id: CFG.business_id,
                branch_id: CFG.branch_id,
                sale_type_id: $('#sale_type_id').val(),
                term: term,
                // Lets the server resolve the register's warehouse so
                // available_stock is scoped to it - see
                // OrderService::resolveWarehouseContext().
                register_session_id: state.session ? state.session.pos_register_session_id : null,
            },
        })
            .then(function (response) {
                var results = response.Data || [];

                if (isScan) {
                    if (results.length === 1) {
                        openProductPicker('variation', results[0]);
                        $('#productSearchInput').val('');
                        $('#productSearchResults').hide().empty();
                        return;
                    }
                    // zero or multiple matches - fall back to showing the dropdown
                }

                renderSearchResults(results);
            })
            .catch(function (err) {
                errorMessage(err.Message || t('product_search_failed', 'Product search failed.'));
            });
    }

    function renderSearchResults(results) {
        var $box = $('#productSearchResults');
        $box.empty();

        if (!results.length) {
            $box.append('<div class="list-group-item text-muted">No products found</div>');
            $box.show();
            return;
        }

        results.forEach(function (item, idx) {
            var product_name = (item.product && item.product.name) || '';
            var variation_name = item.name || '';
            var unit_name = primaryUnitOf(item).name;
            var displayPrice = item.resolved_price !== undefined ? item.resolved_price : item.sale_price;
            var outOfStock = !!stockBlockMessage(product_name || variation_name, item.is_track_stock, item.available_stock, 1);

            var $row = $(
                '<a href="javascript:void(0);" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center' +
                    (outOfStock ? ' pos-search-result-out-of-stock' : '') + '">' +
                    '<span>' + escapeHtml(product_name) + (variation_name ? ' - ' + escapeHtml(variation_name) : '') +
                        '<small class="text-muted d-block">' + escapeHtml(item.sku || '') + ' ' + escapeHtml(item.barcode || '') + '</small>' +
                    '</span>' +
                    '<span class="text-end">' +
                        '<span class="fw-bold d-block">' + money(displayPrice) + ' / ' + escapeHtml(unit_name) + '</span>' +
                        stockHint(item.available_stock) +
                    '</span>' +
                '</a>'
            );

            $row.on('click', function () {
                openProductPicker('variation', item);
                $('#productSearchInput').val('');
                $box.hide().empty();
            });

            $box.append($row);
        });

        $box.show();
    }

    function escapeHtml(str) {
        return $('<div>').text(str == null ? '' : str).html();
    }

    // ==============================
    // VOUCHER SEARCH / APPLY
    // ==============================
    function searchVouchers(term) {
        ajaxRequest({
            url: URLS.search_vouchers,
            data: { business_id: CFG.business_id, term: term },
        })
            .then(function (response) {
                renderVoucherSearchResults(response.Data || []);
            })
            .catch(function (err) {
                errorMessage(err.Message || t('voucher_search_failed', 'Voucher search failed.'));
            });
    }

    // "Browse" button - lists vouchers already eligible for the current
    // business/branch/customer/order-type/order-source/sale-type/schedule, so
    // the cashier doesn't have to know a code. Final item/BOGO/payment-method
    // eligibility is still resolved by previewVoucherApply() once picked.
    function browseEligibleVouchers() {
        ajaxRequest({
            url: URLS.eligible_vouchers,
            data: {
                business_id: CFG.business_id,
                branch_id: CFG.branch_id,
                customer_id: $('#customer_id').val(),
                order_type_id: $('#order_type_id').val(),
                order_source_id: $('#order_source_id').val(),
                sale_type_id: $('#sale_type_id').val(),
            },
        })
            .then(function (response) {
                renderVoucherSearchResults(response.Data || []);
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_load_vouchers', 'Unable to load available vouchers.'));
            });
    }

    function renderVoucherSearchResults(results) {
        var $box = $('#voucherSearchResults');
        $box.empty();

        if (!results.length) {
            $box.append('<div class="list-group-item text-muted">No matching vouchers</div>');
            $box.show();
            return;
        }

        results.forEach(function (item) {
            var amount = item.rule || (item.type === 'percent' ? (parseFloat(item.value) || 0) + '% off' : money(item.value) + ' off');

            var $row = $(
                '<a href="javascript:void(0);" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">' +
                    '<span><strong>' + escapeHtml(item.code) + '</strong>' +
                        (item.name ? '<small class="text-muted d-block">' + escapeHtml(item.name) + '</small>' : '') +
                    '</span>' +
                    '<span class="fw-bold">' + escapeHtml(amount) + '</span>' +
                '</a>'
            );

            $row.on('click', function () {
                $('#voucher_id').val(item.voucher_id);
                $('#voucher_code').val(item.code);
                $box.hide().empty();
                recalcLocal();
                previewVoucherApply();
            });

            $box.append($row);
        });

        $box.show();
    }

    function clearVoucherFeedback() {
        $('#voucherApplyFeedback').hide().empty();
        $('.cart-line .voucher-badge').remove();
    }

    // Real-time, server-authoritative voucher/discount validation - reuses the
    // exact same eligibility/calculation OrderService runs when actually
    // saving the order (see OrderService::previewVoucher()), without creating
    // any draft order. Gives the cashier the amount and matched items on
    // success, or the precise reason on failure, before checkout.
    var voucherPreviewTimer = null;
    function previewVoucherApply() {
        clearTimeout(voucherPreviewTimer);

        if (!state.session || !state.cart.length
            || (!$('#voucher_code').val() && !$('#voucher_id').val() && !$('#discount_id').val() && !$('#use_loyalty_points').is(':checked'))) {
            clearVoucherFeedback();
            return;
        }

        voucherPreviewTimer = setTimeout(function () {
            var payload = buildStorePayload('draft');
            payload.business_id = CFG.business_id;

            ajaxRequest({ url: URLS.preview_voucher, method: 'POST', data: payload })
                .then(function (response) {
                    var data = response.Data || {};
                    clearVoucherFeedback();

                    var itemDiscount = parseFloat($('#sumItemDiscount').text().replace(/,/g, '')) || 0;
                    var orderDiscount = Math.max(0, (parseFloat(data.discount_amount) || 0) - itemDiscount);

                    $('#sumSubtotal').text(money(data.subtotal));
                    $('#sumOrderDiscount').text(money(orderDiscount));
                    $('#sumTotal').text(money(data.total));
                    recalcPayments(parseFloat(data.total) || 0);

                    if (parseFloat(data.voucher_discount_amount) > 0) {
                        var msg = tr('voucher_applied', 'Voucher applied: -:amount', {amount: money(data.voucher_discount_amount)});
                        if (data.voucher_rule) msg += ' (' + escapeHtml(data.voucher_rule) + ')';
                        $('#voucherApplyFeedback').removeClass('text-danger').addClass('text-success').text(msg).show();
                    }

                    (data.lines || []).forEach(function (line) {
                        var cartLine = state.cart.find(function (l) { return l.product_variation_id === line.product_variation_id; });
                        if (!cartLine) return;

                        var badge = parseFloat(line.free_quantity) > 0
                            ? (line.free_quantity + ' Free')
                            : ('-' + money(line.voucher_discount_amount));

                        $('#cartRows .cart-line[data-key="' + cartLine.line_key + '"] .line-total')
                            .after('<small class="voucher-badge text-success d-block">' + escapeHtml(badge) + '</small>');
                    });
                })
                .catch(function (err) {
                    clearVoucherFeedback();
                    $('#voucherApplyFeedback').removeClass('text-success').addClass('text-danger')
                        .text(err.Message || t('voucher_cannot_apply', 'This voucher cannot be applied.')).show();
                    // Don't silently carry an invalid voucher into checkout -
                    // the cashier must pick another one or clear the field.
                    $('#voucher_id').val('');
                    recalcLocal();
                });
        }, 350);
    }

    // A variation's default selling unit is its Sale Unit; when that isn't
    // configured (sale_unit_id null), fall back to its Base (stocking) unit -
    // pv.unit is the base-unit relation (ProductVariation::unit() maps to
    // base_unit_id, see OrderService::searchProducts()/getProductsByCategory()).
    function primaryUnitOf(pv) {
        if (pv.sale_unit_id) {
            return { unit_id: pv.sale_unit_id, name: (pv.sale_unit && pv.sale_unit.name) || t('sale_unit', 'Sale Unit') };
        }

        if (pv.base_unit_id) {
            return { unit_id: pv.base_unit_id, name: (pv.unit && pv.unit.name) || t('base_unit', 'Base Unit') };
        }

        return { unit_id: null, name: t('unit', 'Unit') };
    }

    // ==============================
    // CART
    // ==============================
    // overrides (optional): { quantity, image } - POS never offers a unit
    // choice (see primaryUnitOf()), so every line always uses the
    // variation's primary (Sale, else Base) unit. Every call site keeps
    // calling this with no quantity override, so the default (qty 1) is
    // what's actually used. Returns true if the item was actually added,
    // false if it was blocked by the stock guard - callers that only do
    // something on success (e.g. close the picker modal) check this.
    function addProductToCart(pv, overrides) {
        overrides = overrides || {};

        var primary = primaryUnitOf(pv);
        var unit_id = primary.unit_id;

        var quantity = parseFloat(overrides.quantity);
        if (isNaN(quantity) || quantity <= 0) {
            quantity = 1;
        }

        // Same variation already in cart -> just bump qty (unit is always
        // the same primary unit now, so no need to match on it too).
        var existing = state.cart.find(function (l) {
            return l.product_variation_id === pv.product_variation_id && l.unit_id === unit_id;
        });

        var name = (pv.product && pv.product.name) || pv.name || t('this_product', 'This product');
        var newQty = quantity + (existing ? (parseFloat(existing.quantity) || 0) : 0);

        var blockMessage = stockBlockMessage(name, pv.is_track_stock, pv.available_stock, newQty);
        if (blockMessage) {
            errorMessage(blockMessage);
            return false;
        }

        // Serial-tracked products can't be silently bumped/added - the
        // cashier must pick which specific unit(s) are being sold, so this
        // hands off to an async picker instead of mutating the cart
        // synchronously. See openSerialPickerForAdd().
        if (pv.track_serial_number) {
            openSerialPickerForAdd(pv, unit_id, primary, quantity, existing, overrides);
            return false;
        }

        if (existing) {
            existing.quantity = newQty;
            existing.is_track_stock = pv.is_track_stock;
            existing.available_stock = pv.available_stock;
            renderCart();
            return true;
        }

        state.line_seq += 1;

        state.cart.push({
            line_key: 'line_' + state.line_seq,
            product_variation_id: pv.product_variation_id,
            product_name: (pv.product && pv.product.name) || '',
            variation_name: pv.name || '',
            unit_id: unit_id,
            unit_name: primary.name,
            quantity: quantity,
            // resolved_price/resolved_discount_percentage come from
            // VariationPricingService via searchProducts()/getProductsByCategory()
            // (see OrderService::applyResolvedPricing()), resolved against the
            // currently selected #sale_type_id - sale_price/0 are the fallback
            // for any caller that hasn't gone through that resolution.
            unit_price: (pv.resolved_price !== undefined ? pv.resolved_price : pv.sale_price) || 0,
            discount: pv.resolved_discount_percentage || 0,
            // Set by OrderService::applyResolvedPricing() (search/browse) or
            // by repriceCartForSaleType()/repriceLineForSaleType() - used for
            // the client-side below-minimum-price validation (see
            // updateLineFromRow()/canOverrideMinPrice()).
            minimum_selling_price: pv.minimum_selling_price !== undefined ? pv.minimum_selling_price : null,
            // Set by attachAvailableStock() (search/browse/reprice) - used
            // for the client-side stock guard (see stockBlockMessage()) on
            // this line going forward. null available_stock = not tracked.
            is_track_stock: !!pv.is_track_stock,
            available_stock: pv.available_stock !== undefined ? pv.available_stock : null,
            // null = inherits the order-level Sale Type (re-priced whenever it
            // changes); set only when the cashier overrides this one line's
            // Sale Type (see .line-sale-type, allowed only when
            // pos_setting.allow_mixed_sale_types is on).
            sale_type_id: null,
            manual_override: false,
            notes: '',
            image: overrides.image || null,
            track_serial_number: !!pv.track_serial_number,
            serial_numbers: [],
        });

        renderCart();
        return true;
    }

    // ==============================
    // SERIAL NUMBER PICKER (add-to-cart + cart-line management)
    // ==============================
    var state_serial_picker = { pv: null, unit_id: null, primary: null, requestedQty: null, existing: null, overrides: null, lineKey: null };

    function openSerialPickerForAdd(pv, unit_id, primary, requestedQty, existing, overrides) {
        state_serial_picker = { pv: pv, unit_id: unit_id, primary: primary, requestedQty: requestedQty, existing: existing || null, overrides: overrides || {}, lineKey: existing ? existing.line_key : null };

        var alreadyChosen = existing ? (existing.serial_numbers || []) : [];
        var totalNeeded = requestedQty + alreadyChosen.length;

        $('#serialPickerHint').text(tr('select_exactly_serials', 'Select exactly :count serial number(s) for :name.', {count: totalNeeded, name: ((pv.product && pv.product.name) || pv.name || t('this_product', 'This product'))}));
        $('#serialPickerList').html('<div class="text-muted p-2">' + t('loading', 'Loading...') + '</div>');
        $('#serialPickerModal').data('total-needed', totalNeeded);
        state.serial_picker_modal = state.serial_picker_modal || new bootstrap.Modal(document.getElementById('serialPickerModal'));
        state.serial_picker_modal.show();

        ajaxRequest({
            url: URLS.available_serials,
            data: {
                product_variation_id: pv.product_variation_id,
                register_session_id: state.session ? state.session.pos_register_session_id : null,
            },
        }).then(function (response) {
            renderSerialPickerList(response.Data || [], alreadyChosen);
        }).catch(function () {
            $('#serialPickerList').html('<div class="text-danger p-2">' + t('unable_load_serials', 'Unable to load serial numbers.') + '</div>');
        });
    }

    // Opens the same picker to edit an already-cart-ed serial-tracked
    // line's chosen units (add/remove) - quantity is derived from however
    // many end up checked, rather than being entered separately.
    function openSerialPickerForEdit(lineKey) {
        var line = state.cart.find(function (l) { return l.line_key === lineKey; });
        if (!line) return;

        state_serial_picker = { pv: null, unit_id: line.unit_id, primary: null, requestedQty: null, existing: null, overrides: {}, lineKey: lineKey };

        $('#serialPickerHint').text(tr('check_uncheck_serials', 'Check/uncheck serial numbers for :name. At least one must remain selected.', {name: line.product_name}));
        $('#serialPickerList').html('<div class="text-muted p-2">' + t('loading', 'Loading...') + '</div>');
        $('#serialPickerModal').data('total-needed', null);
        state.serial_picker_modal = state.serial_picker_modal || new bootstrap.Modal(document.getElementById('serialPickerModal'));
        state.serial_picker_modal.show();

        ajaxRequest({
            url: URLS.available_serials,
            data: {
                product_variation_id: line.product_variation_id,
                register_session_id: state.session ? state.session.pos_register_session_id : null,
            },
        }).then(function (response) {
            renderSerialPickerList(response.Data || [], line.serial_numbers || []);
        }).catch(function () {
            $('#serialPickerList').html('<div class="text-danger p-2">' + t('unable_load_serials', 'Unable to load serial numbers.') + '</div>');
        });
    }

    function renderSerialPickerList(serials, alreadyChosen) {
        if (!serials.length && !alreadyChosen.length) {
            $('#serialPickerList').html('<div class="text-muted p-2">' + t('no_serials_found', 'No available serial numbers found for this product.') + '</div>');
            return;
        }
        // Already-chosen serials (from an existing cart line) may not come
        // back from the "available" list, since this session already holds
        // them client-side - union them in so they still show up checked.
        var known = {};
        var html = '';
        serials.forEach(function (s) {
            known[s.serial_no] = true;
            var checked = alreadyChosen.indexOf(s.serial_no) !== -1 ? 'checked' : '';
            html += '<div class="form-check">' +
                '<input class="form-check-input serial-picker-checkbox" type="checkbox" value="' + escapeHtml(s.serial_no) + '" id="sp_' + s.product_variation_serial_number_id + '" ' + checked + '>' +
                '<label class="form-check-label" for="sp_' + s.product_variation_serial_number_id + '">' + escapeHtml(s.serial_no) + '</label>' +
                '</div>';
        });
        alreadyChosen.forEach(function (sn, i) {
            if (!known[sn]) {
                html += '<div class="form-check">' +
                    '<input class="form-check-input serial-picker-checkbox" type="checkbox" value="' + escapeHtml(sn) + '" id="sp_existing_' + i + '" checked>' +
                    '<label class="form-check-label" for="sp_existing_' + i + '">' + escapeHtml(sn) + '</label>' +
                    '</div>';
            }
        });
        $('#serialPickerList').html(html);
    }

    $(document).on('change', '#posSerialScanHelperInput', function () {
        var code = $(this).val();
        $(this).val('');
        if (!code) return;

        var $checkbox = $('.serial-picker-checkbox[value="' + code.replace(/"/g, '\\"') + '"]');
        if (!$checkbox.length) {
            errorMessage(tr('serial_not_available', 'Serial number ":code" is not in the available list for this product.', {code: code}));
            return;
        }
        $checkbox.prop('checked', true);
    });

    $(document).on('click', '#serialPickerSaveBtn', function () {
        var selected = $('.serial-picker-checkbox:checked').map(function () { return $(this).val(); }).get();
        var totalNeeded = $('#serialPickerModal').data('total-needed');

        if (totalNeeded !== null && totalNeeded !== undefined && selected.length !== totalNeeded) {
            errorMessage(tr('select_exactly_serials_count', 'Select exactly :needed serial number(s) (currently :current).', {needed: totalNeeded, current: selected.length}));
            return;
        }
        if (!selected.length) {
            errorMessage(t('select_at_least_one_serial', 'Select at least one serial number.'));
            return;
        }

        if (state_serial_picker.lineKey) {
            var line = state.cart.find(function (l) { return l.line_key === state_serial_picker.lineKey; });
            if (line) {
                line.serial_numbers = selected;
                line.quantity = selected.length;
            }
        } else {
            var pv = state_serial_picker.pv;
            var overrides = state_serial_picker.overrides || {};

            state.line_seq += 1;
            state.cart.push({
                line_key: 'line_' + state.line_seq,
                product_variation_id: pv.product_variation_id,
                product_name: (pv.product && pv.product.name) || '',
                variation_name: pv.name || '',
                unit_id: state_serial_picker.unit_id,
                unit_name: state_serial_picker.primary.name,
                quantity: selected.length,
                unit_price: (pv.resolved_price !== undefined ? pv.resolved_price : pv.sale_price) || 0,
                discount: pv.resolved_discount_percentage || 0,
                minimum_selling_price: pv.minimum_selling_price !== undefined ? pv.minimum_selling_price : null,
                is_track_stock: !!pv.is_track_stock,
                available_stock: pv.available_stock !== undefined ? pv.available_stock : null,
                sale_type_id: null,
                manual_override: false,
                notes: '',
                image: overrides.image || null,
                track_serial_number: true,
                serial_numbers: selected,
            });
        }

        state.serial_picker_modal.hide();
        renderCart();

        // The product picker (variation-choice) modal, if it was the one
        // that led here, should also close now that the line is committed.
        if (state.product_picker_modal) {
            state.product_picker_modal.hide();
        }
    });

    // Changing the order-level Sale Type (via #saleTypeSelect) force-syncs
    // every line currently in the cart to it - including lines the cashier
    // had manually priced or given their own per-line Sale Type override -
    // so the whole cart always reflects one consistent, current Sale Type
    // until a cashier explicitly re-overrides an individual line again.
    function repriceCartForSaleType() {
        var saleTypeId = $('#sale_type_id').val();

        var variationIds = state.cart.map(function (l) { return l.product_variation_id; });

        if (!variationIds.length) {
            return;
        }

        ajaxRequest({
            url: URLS.resolve_prices,
            method: 'POST',
            data: {
                sale_type_id: saleTypeId,
                product_variation_ids: variationIds,
                register_session_id: state.session ? state.session.pos_register_session_id : null,
            },
        })
            .then(function (response) {
                var resolved = response.Data || {};

                state.cart.forEach(function (line) {
                    var r = resolved[line.product_variation_id];
                    if (!r) return;

                    line.sale_type_id = null;
                    line.manual_override = false;
                    line.unit_price = r.price;
                    line.discount = r.discount_percentage;
                    line.minimum_selling_price = r.minimum_selling_price;
                    line.is_track_stock = r.is_track_stock;
                    line.available_stock = r.available_stock;
                });

                renderCart();
            })
            .catch(function () {
                // Re-pricing failure shouldn't block the cashier - the cart
                // keeps its previous prices and the server re-resolves
                // authoritatively again at save time anyway.
            });
    }

    // Re-resolves a single line against its own Sale Type override - used
    // when the cashier picks a per-line Sale Type different from the
    // order-level default (see .line-sale-type in renderCart()).
    function repriceLineForSaleType(line) {
        ajaxRequest({
            url: URLS.resolve_prices,
            method: 'POST',
            data: {
                sale_type_id: line.sale_type_id || $('#sale_type_id').val(),
                product_variation_ids: [line.product_variation_id],
                register_session_id: state.session ? state.session.pos_register_session_id : null,
            },
        })
            .then(function (response) {
                var r = (response.Data || {})[line.product_variation_id];
                if (!r) return;

                line.is_track_stock = r.is_track_stock;
                line.available_stock = r.available_stock;

                if (line.manual_override) {
                    renderCart();
                    return;
                }

                line.unit_price = r.price;
                line.discount = r.discount_percentage;
                line.minimum_selling_price = r.minimum_selling_price;
                renderCart();
            })
            .catch(function () {
                // Same rationale as repriceCartForSaleType() above.
            });
    }

    // ==============================
    // CATEGORY BROWSING / PRODUCT GRID
    // ==============================
    function loadProductsByCategory(category_id) {
        state.active_category_id = category_id || '';

        ajaxRequest({
            url: URLS.products_by_category,
            data: {
                business_id: CFG.business_id,
                branch_id: CFG.branch_id,
                sale_type_id: $('#sale_type_id').val(),
                category_id: category_id || '',
                register_session_id: state.session ? state.session.pos_register_session_id : null,
            },
        })
            .then(function (response) {
                renderProductGrid(response.Data || []);
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_load_products', 'Unable to load products.'));
                renderProductGrid([]);
            });
    }

    function renderProductGrid(products) {
        var $grid = $('#posProductGrid');
        $grid.empty();

        if (!products.length) {
            $grid.hide();
            $('#posProductGridEmpty').removeClass('d-none').show();
            return;
        }

        $('#posProductGridEmpty').addClass('d-none').hide();

        products.forEach(function (product) {
            var variations = product.product_variations || [];
            var firstVariation = variations[0] || {};
            var image = (product.product_images && product.product_images[0] && product.product_images[0].image_url) || null;
            var unitName = primaryUnitOf(firstVariation).name;

            var $card = $('<div class="product-card"></div>').data('product', product);

            var imgHtml = image
                ? '<img class="product-card-img" src="' + image + '" alt="">'
                : '<div class="product-card-img d-flex align-items-center justify-content-center text-muted"><i class="fa fa-image"></i></div>';

            var badgeHtml = variations.length > 1
                ? '<span class="product-card-variations-badge">' + variations.length + ' options</span>'
                : '';

            var firstDisplayPrice = firstVariation.resolved_price !== undefined ? firstVariation.resolved_price : firstVariation.sale_price;

            // Only a single-variation product shows its own stock right on
            // the grid card - a multi-variation product's stock differs per
            // variation, so it's shown per-card in the picker modal instead
            // (see renderVariationPickerGrid()).
            var stockHtml = variations.length === 1 ? stockHint(firstVariation.available_stock) : '';
            var outOfStock = variations.length === 1
                && !!stockBlockMessage(product.name, firstVariation.is_track_stock, firstVariation.available_stock, 1);

            $card.toggleClass('product-card-out-of-stock', outOfStock);

            $card.html(
                '<div class="product-card-img-wrap">' + imgHtml + badgeHtml + '</div>' +
                '<div class="product-card-body">' +
                    '<span class="product-card-add-btn"><i class="fa fa-plus"></i></span>' +
                    '<div class="product-card-name">' + escapeHtml(product.name || '') + '</div>' +
                    '<div class="product-card-sku">' + escapeHtml(firstVariation.sku || '') + '</div>' +
                    '<div class="product-card-footer">' +
                        '<span class="product-card-price">' + money(firstDisplayPrice) + '</span>' +
                        '<span class="product-card-unit">' + escapeHtml(unitName) + '</span>' +
                    '</div>' +
                    stockHtml +
                '</div>'
            );

            $grid.append($card);
        });

        $grid.show();
    }

    function handleGridProductClick(product) {
        // Always routed through the 'product' source (rather than unwrapping
        // a lone variation here) so openProductPicker()'s own fast-path
        // check is the single place that decides bypass-vs-modal, and the
        // parent product (with its images) stays available to the modal
        // even for a single-variation product that still needs a unit pick.
        openProductPicker('product', product);
    }

    // Reshapes a grid-sourced { product, variation } pair into the same
    // flat shape searchProducts() already returns per ProductVariation, so
    // addProductToCart()/openProductPicker() only ever deal with one shape.
    function pvFromGridVariation(product, variation) {
        return {
            product_variation_id: variation.product_variation_id,
            name: variation.name,
            sku: variation.sku,
            barcode: variation.barcode,
            sale_price: variation.sale_price,
            resolved_price: variation.resolved_price,
            resolved_discount_percentage: variation.resolved_discount_percentage,
            base_unit_id: variation.base_unit_id,
            unit: variation.unit,
            sale_unit_id: variation.sale_unit_id,
            sale_unit: variation.sale_unit,
            product_variation_unit_conversion: variation.product_variation_unit_conversion,
            is_track_stock: variation.is_track_stock,
            available_stock: variation.available_stock,
            product: { name: product.name, product_images: product.product_images },
        };
    }

    // First product image URL, if any - grid-browsed products carry
    // product_images (see getProductsByCategory()); search/scan results don't
    // eager-load images, so this simply returns null for those.
    function firstImageOf(product) {
        return (product && product.product_images && product.product_images[0] && product.product_images[0].image_url) || null;
    }

    // ==============================
    // PRODUCT PICKER MODAL (variation grid - only shown for >1 variation)
    // ==============================
    // source: 'variation' - payload is a single flat pv (from search/scan or
    //         a single-variation grid product)
    //         'product'   - payload is a grid Product with >1 product_variations
    function openProductPicker(source, payload) {
        var product, variations;

        if (source === 'product') {
            product = payload;
            variations = (payload.product_variations || []).map(function (v) {
                return pvFromGridVariation(product, v);
            });
        } else {
            variations = [payload];
            product = payload.product || { name: payload.name };
        }

        if (!variations.length) {
            errorMessage(t('no_sellable_variation', 'This product has no sellable variation.'));
            return;
        }

        // Purely variation-count driven: exactly one variation always
        // direct-adds at qty 1 - unit conversions are no longer a factor
        // (POS never offers a unit choice, see primaryUnitOf()/
        // addProductToCart()). More than one variation opens the picker.
        if (variations.length === 1) {
            addProductToCart(variations[0], { image: firstImageOf(product) });
            return;
        }

        state.picker.product = product;
        state.picker.variations = variations;

        $('#productPickerTitle').text(tr('select_variation_for', 'Select a variation for :name', {name: product.name || ''}));
        renderVariationPickerGrid(product, variations);

        state.product_picker_modal.show();
    }

    // Renders each variation as a card in the same visual style as the main
    // product grid (see renderProductGrid()) - clicking a card adds that
    // variation immediately at qty 1 and closes the modal.
    function renderVariationPickerGrid(product, variations) {
        var $grid = $('#productPickerGrid');
        $grid.empty();

        var image = firstImageOf(product);
        var imgHtml = image
            ? '<img class="product-card-img" src="' + image + '" alt="">'
            : '<div class="product-card-img d-flex align-items-center justify-content-center text-muted"><i class="fa fa-image"></i></div>';

        variations.forEach(function (pv, idx) {
            var unitName = primaryUnitOf(pv).name;
            var outOfStock = !!stockBlockMessage(pv.name || product.name, pv.is_track_stock, pv.available_stock, 1);

            var $card = $('<div class="product-card"></div>').data('idx', idx).toggleClass('product-card-out-of-stock', outOfStock);
            $card.html(
                '<div class="product-card-img-wrap">' + imgHtml + '</div>' +
                '<div class="product-card-body">' +
                    '<div class="product-card-name">' + escapeHtml(pv.name || product.name || '') + '</div>' +
                    (pv.sku ? '<div class="product-card-sku">' + escapeHtml(pv.sku) + '</div>' : '') +
                    '<div class="product-card-footer">' +
                        '<span class="product-card-price">' + money(pv.resolved_price !== undefined ? pv.resolved_price : pv.sale_price) + '</span>' +
                        '<span class="product-card-unit">' + escapeHtml(unitName) + '</span>' +
                    '</div>' +
                    stockHint(pv.available_stock) +
                '</div>'
            );

            $grid.append($card);
        });
    }

    // Small inline "Manual" badge when the cashier has hand-edited a line's
    // price, so it's clear that line no longer follows Sale Type pricing.
    function manualOverrideBadge(line) {
        return line.manual_override ? ' <span class="badge bg-label-warning">' + t('manual', 'Manual') + '</span>' : '';
    }

    function saleTypeOptionsHtml(selectedId) {
        var html = '<option value="">Order default</option>';
        (CFG.sale_types || []).forEach(function (st) {
            html += '<option value="' + st.sale_type_id + '"' + (st.sale_type_id === selectedId ? ' selected' : '') + '>' + escapeHtml(st.name) + '</option>';
        });
        return html;
    }

    function renderCart() {
        var $rows = $('#cartRows');
        $rows.empty();

        $('#cartItemCount').text(state.cart.length === 1
            ? tr('cart_item_one', '(:count Item)', {count: state.cart.length})
            : tr('cart_items_count', '(:count Items)', {count: state.cart.length}));
        $('#clearCartBtn').toggleClass('d-none', !state.cart.length);
        updateCartOrderBadge();

        var showLineDiscount = SETTING.enable_discount && ['line', 'both'].includes(SETTING.discount_level);
        var showLineSaleType = !!SETTING.allow_mixed_sale_types && (CFG.sale_types || []).length > 1;

        if (!state.cart.length) {
            $rows.append(
                '<div class="pos-cart-empty" id="cartEmptyRow">' +
                    '<i class="fa fa-cart-shopping fs-1 text-muted mb-2"></i>' +
                    '<p class="text-muted mb-0">Cart is empty</p>' +
                '</div>'
            );
            recalcLocal();
            return;
        }

        state.cart.forEach(function (line) {
            // Preferred display format: Product Name (Variation) - Unit -
            // Sale Type. Name/Variation/Unit are combined into one static
            // line; Sale Type stays the interactive .line-sale-type <select>
            // right after it (saleTypeCell below) so mixed-sale-type
            // overrides keep working.
            var lineDesc = escapeHtml(line.product_name);
            if (line.variation_name) {
                lineDesc += ' (' + escapeHtml(line.variation_name) + ')';
            }
            if (line.unit_name) {
                lineDesc += ' - ' + escapeHtml(line.unit_name);
            }

            var priceCell = (canChangePrice()
                ? '<input type="number" step="0.01" min="0" class="line-price" value="' + line.unit_price + '">'
                : money(line.unit_price)) + manualOverrideBadge(line);

            var discountCell = showLineDiscount
                ? '<div class="cart-line-discount">' +
                    '<input type="number" step="0.01" min="0" max="100" class="line-discount" value="' + line.discount + '"><span>%</span></div>'
                : '<div class="cart-line-discount"></div>';

            // "Order default" (line.sale_type_id === null) always matches the
            // order - only a line with its own override that differs from
            // the order's current Sale Type is flagged as mixed.
            var orderSaleTypeId = $('#sale_type_id').val();
            var isOverride = !!line.sale_type_id && line.sale_type_id !== orderSaleTypeId;

            var saleTypeCell = showLineSaleType
                ? '<select class="line-sale-type' + (isOverride ? ' line-sale-type-override' : '') + '"' +
                    (isOverride ? ' title="' + t('priced_different_sale_type', 'Priced under a different Sale Type than the order') + '"' : '') + '>' +
                    saleTypeOptionsHtml(line.sale_type_id) + '</select>'
                : '';

            var imgHtml = line.image
                ? '<img class="cart-line-img" src="' + line.image + '" alt="">'
                : '<div class="cart-line-img-placeholder"><i class="fa fa-image"></i></div>';

            var qtyCell = line.track_serial_number
                ? '<div class="cart-line-qty-stepper">' +
                    '<button type="button" class="btn btn-sm btn-outline-secondary manage-serials" title="' + t('manage_serials', 'Manage serial numbers') + '">' +
                        '<i class="fa fa-barcode"></i> ' + line.quantity +
                    '</button>' +
                  '</div>'
                : '<div class="cart-line-qty-stepper">' +
                    '<button type="button" class="qty-dec">-</button>' +
                    '<input type="number" step="0.01" min="0.01" class="line-qty" value="' + line.quantity + '">' +
                    '<button type="button" class="qty-inc">+</button>' +
                  '</div>';

            var $row = $('<div class="cart-line"></div>').attr('data-key', line.line_key);
            $row.html(
                imgHtml +
                '<div class="cart-line-info">' +
                    '<div class="cart-line-name">' + lineDesc + '</div>' +
                    stockHint(line.available_stock) +
                    saleTypeCell +
                '</div>' +
                '<div class="cart-line-price">' + priceCell + '</div>' +
                discountCell +
                qtyCell +
                '<div class="line-total">0.00</div>' +
                '<button type="button" class="line-remove"><i class="fa fa-xmark"></i></button>'
            );

            $rows.append($row);
        });

        recalcLocal();
    }

    function updateCartOrderBadge() {
        var $badge = $('#cartOrderNoBadge');
        if (state.order_daily_id) {
            $badge.removeClass('d-none').text(tr('order_number', 'Order #:id', {id: state.order_daily_id}));
        } else {
            $badge.addClass('d-none').text('');
        }

        // Hold button reflects whether this cart is still a fresh order or an
        // already-held/draft one being continued - clicking it either way
        // just saves the current cart to the same order_id (see holdOrder()).
        var $holdBtn = $('#holdOrderBtn');
        if ($holdBtn.length) {
            var label = state.order_id ? t('update_hold', 'Update Hold') : t('hold', 'Hold');
            $holdBtn.html('<i class="fa fa-pause"></i> ' + label + ' <span class="pos-key-hint">(F6)</span>');
        }
    }

    function updateLineFromRow(key, $row) {
        var line = state.cart.find(function (l) { return l.line_key === key; });
        if (!line) return;

        var newQuantity = parseFloat($row.find('.line-qty').val()) || 0;

        // UX-only stock guard, mirroring the server's authoritative one in
        // OrderService::saveLinesAndComputeTotals()/post() - covers both the
        // qty stepper (+/- triggers 'change', see its click handler) and a
        // manually typed quantity. Reverts the input back to the last valid
        // quantity rather than silently clamping, so the cashier sees
        // exactly why nothing changed.
        var blockMessage = stockBlockMessage(line.product_name, line.is_track_stock, line.available_stock, newQuantity);
        if (blockMessage) {
            errorMessage(blockMessage);
            $row.find('.line-qty').val(line.quantity);
            recalcLocal();
            return;
        }

        line.quantity = newQuantity;

        var $discountInput = $row.find('.line-discount');
        var discountPercent = $discountInput.length ? (parseFloat($discountInput.val()) || 0) : (line.discount || 0);

        if (canChangePrice()) {
            var newPrice = parseFloat($row.find('.line-price').val()) || 0;

            // UX-only floor check, mirroring the server's authoritative one
            // in OrderService::saveLinesAndComputeTotals() (net_unit_price =
            // unit_price * (1 - discount/100), quantity cancels out). The
            // server always re-validates regardless of what the client does.
            if (!canOverrideMinPrice() && line.minimum_selling_price !== null && line.minimum_selling_price !== undefined) {
                var netPrice = newPrice * (1 - discountPercent / 100);
                if (netPrice < line.minimum_selling_price - 0.0005) {
                    errorMessage(tr('below_min_price', 'Price for ":name" cannot be below its minimum selling price of :price.', {name: line.product_name, price: money(line.minimum_selling_price)}));
                    $row.find('.line-price').val(line.unit_price);
                    recalcLocal();
                    return;
                }
            }

            if (newPrice !== line.unit_price) {
                line.manual_override = true;
            }
            line.unit_price = newPrice;
        }

        if ($discountInput.length) {
            line.discount = discountPercent;
        }

        recalcLocal();
    }

    // Local preview only - mirrors OrderService::resolveTaxPercent() on the
    // server (Card Tax Rate only when every payment tendered so far is a
    // card-type method, otherwise Overall Tax Rate). The server always
    // recomputes authoritatively once the final payments are known at
    // complete-sale time.
    function effectiveTaxPercent() {
        var rates = CFG.tax_rates_setting || {};
        var overall = parseFloat(rates.overall_tax_rate) || 0;
        var card = parseFloat(rates.card_tax_rate) || 0;

        if (!state.payments.length) {
            return overall;
        }

        var allCard = state.payments.every(function (p) {
            var method = (CFG.payment_methods || []).find(function (m) { return m.payment_method_id === p.payment_method_id; });
            return method && method.type === 'card';
        });

        return allCard ? card : overall;
    }

    function lineTotal(line) {
        var qty = parseFloat(line.quantity) || 0;
        var price = parseFloat(line.unit_price) || 0;
        var base = qty * price;
        var discAmt = base * (parseFloat(line.discount) || 0) / 100;
        var taxable = base - discAmt;
        var taxAmt = taxable * effectiveTaxPercent() / 100;

        return {
            base: base,
            discAmt: discAmt,
            taxAmt: taxAmt,
            total: taxable + taxAmt,
        };
    }

    // ==============================
    // LOCAL PREVIEW TOTALS (client-side only - server always recomputes)
    // ==============================
    function recalcLocal() {
        var subtotal = 0, lineDiscount = 0, tax = 0;

        $('#cartRows .cart-line[data-key]').each(function () {
            var key = $(this).data('key');
            var line = state.cart.find(function (l) { return l.line_key === key; });
            if (!line) return;

            var t = lineTotal(line);
            subtotal += t.base;
            lineDiscount += t.discAmt;
            tax += t.taxAmt;

            $(this).find('.line-total').text(money(t.total));
        });

        // Order-level discount/voucher amounts are only known authoritatively
        // from the server response after store() - see renderFromServerOrder().
        // This local preview shows 0.00 for it until then, same as before.
        var orderDiscount = 0;
        var totalDiscount = lineDiscount + orderDiscount;
        var total = subtotal - totalDiscount + tax;

        $('#sumSubtotal').text(money(subtotal));
        $('#sumItemDiscount').text(money(lineDiscount));
        $('#sumOrderDiscount').text(money(orderDiscount));
        // Loyalty discount, like order discount/voucher, is only known
        // authoritatively from the server response after store() - see
        // renderFromServerOrder().
        $('#sumLoyaltyDiscountRow').addClass('d-none');
        $('#sumLoyaltyDiscount').text(money(0));
        $('#sumTax').text(money(tax));
        $('#sumTotal').text(money(total));

        recalcPayments(total);

        // Cart changed - re-validate any already-applied voucher/discount
        // against the new cart (no-op if neither is set).
        previewVoucherApply();
    }

    function updateCreditHint() {
        var $opt = $('#customer_id').find(':selected');
        var limit = parseFloat($opt.data('credit-limit') || 0);

        if (limit > 0) {
            $('#creditLimitHint').removeClass('d-none').text(tr('credit_limit_label', 'Credit limit: :amount', {amount: money(limit)}));
        } else {
            $('#creditLimitHint').addClass('d-none');
        }
    }

    // Loyalty points balance for the selected customer - #loyaltyWrap (and
    // therefore #loyaltyPointsHint) only exists in the DOM when the Loyalty
    // Program is active for this business (see PosScreenController::index()/
    // $customer_setting->loyalty_program), so this is a no-op elsewhere.
    // The balance itself comes from a data attribute baked onto each
    // <option> at page load (data-loyalty-points), the same way
    // data-store-credit-balance already works for Store Credit - not a
    // dedicated AJAX lookup. The redemption cap is still resolved
    // server-side (LoyaltyPointService::calculateRedemption()) regardless of
    // what this hint shows, so a stale balance here (e.g. from points
    // reserved elsewhere earlier in this same session) never lets a
    // cashier over-redeem.
    function updateLoyaltyPointsHint() {
        var $hint = $('#loyaltyPointsHint');
        if (!$hint.length) return;

        var $opt = $('#customer_id').find(':selected');
        var isWalkin = $opt.data('walkin') == 1;
        var available = parseFloat($opt.data('loyalty-points') || 0);

        if (isWalkin || available <= 0) {
            $hint.hide().text('');
            return;
        }

        var rate = parseFloat(CUSTOMER_SETTING.loyalty_redemption_value || 0);
        if (rate > 0) {
            $hint.show().text(tr('pts_available_value', ':pts pts available (~:value)', {pts: available, value: money(available * rate)}));
        } else {
            $hint.show().text(tr('pts_available', ':pts pts available', {pts: available}));
        }
    }

    // ==============================
    // PAYMENTS
    // ==============================
    // Compact dropdown on top of the same state.payments array the existing
    // multi-row list (#paymentRows/renderPayments()) already drives - picking
    // a single method just means state.payments has exactly one entry;
    // "Multi Pay" reveals the pre-existing split-tender rows.
    var MULTI_PAY_VALUE = '__multi__';

    // Store Credit is only ever a real option for a non-walk-in customer who
    // currently has a positive balance - unlike Credit (gated by a
    // permission, since it extends new trust), anyone can use their own
    // already-owned store credit, so this is a data check, not a permission
    // check.
    function customerHasStoreCredit() {
        var balance = parseFloat($('#customer_id').find(':selected').data('store-credit-balance') || 0);
        var isWalkin = $('#customer_id').find(':selected').data('walkin') == 1;
        return !isWalkin && balance > 0;
    }

    function filterPaymentMethodsForDisplay(methods) {
        return (methods || []).filter(function (m) {
            if (m.type === 'credit') return can('order.payment.credit');
            if (m.type === 'store_credit') return customerHasStoreCredit();
            return true;
        });
    }

    function renderPaymentMethodTiles() {
        var $select = $('#paymentMethodSelect');
        $select.empty().append('<option value="">Payment Method</option>');

        var methods = filterPaymentMethodsForDisplay(CFG.payment_methods);

        // Visible badge-style selector mirrors the Order Type pills - the
        // hidden <select> above stays the source of truth the rest of the
        // payment logic (selectPaymentTile/activatePaymentUI/etc) reads from.
        var $pills = $('#paymentMethodPills').empty();

        methods.forEach(function (m) {
            $select.append('<option value="' + m.payment_method_id + '">' + escapeHtml(m.name) + '</option>');
            $pills.append('<button type="button" class="pos-pill" data-value="' + m.payment_method_id + '">' + escapeHtml(m.name) + '</button>');
        });

        $select.append('<option value="' + MULTI_PAY_VALUE + '">' + t('multi_pay', 'Multi Pay') + '</option>');
        $pills.append('<button type="button" class="pos-pill" data-value="' + MULTI_PAY_VALUE + '">' + t('multi_pay', 'Multi Pay') + '</button>');
    }

    // Cash is the default tender for a fresh sale (opening the POS or
    // starting a new order after completing/resetting one) - cashiers pay
    // in cash far more often than any other method, so this saves a tap.
    function selectDefaultPaymentMethod() {
        var methods = filterPaymentMethodsForDisplay(CFG.payment_methods);
        var cash = methods.find(function (m) { return m.type === 'cash'; }) || methods[0];

        if (cash) {
            selectPaymentTile(cash.payment_method_id, false);
        } else {
            resetPaymentSelection();
        }
    }

    // User picked a payment method from the dropdown - starts a fresh
    // payment set (amount defaults to the current total for a one-tap
    // cash/card/bank/credit sale).
    function selectPaymentTile(methodId, isMulti) {
        if (isMulti) {
            state.payments = state.payments.length ? state.payments : [{ payment_method_id: '', amount: 0, reference_no: '' }];
        } else {
            var total = parseFloat($('#sumTotal').text()) || 0;
            state.payments = [{ payment_method_id: methodId, amount: total, reference_no: '' }];
        }

        activatePaymentUI(isMulti ? null : methodId, isMulti);

        if (isMulti) {
            renderPayments();
        } else {
            $('#paidAmountInput').val(money(state.payments[0].amount));
            updateCreditCustomerSummary();
            recalcPayments();
        }

        updateCheckoutSummary();
    }

    // UI-only: sets the dropdown's value and shows the matching block,
    // without touching state.payments - used both by selectPaymentTile()
    // above and when restoring a resumed held order's existing payments.
    function activatePaymentUI(methodId, isMulti) {
        state.payment_mode = isMulti ? 'multi' : (methodId ? 'single' : null);
        state.selected_payment_method_id = isMulti ? null : methodId;

        $('#paymentMethodSelect').val(isMulti ? MULTI_PAY_VALUE : (methodId || ''));

        $('#multiPaymentBlock').toggleClass('d-none', !isMulti);
        $('#singlePaymentBlock').toggleClass('d-none', !!isMulti);
        updateCreditCustomerSummary();
        syncPillsFromSelect();
        updateCheckoutSummary();
    }

    function resetPaymentSelection() {
        state.payment_mode = null;
        state.selected_payment_method_id = null;
        $('#paymentMethodSelect').val('');
        $('#multiPaymentBlock').addClass('d-none');
        $('#singlePaymentBlock').removeClass('d-none');
        $('#paidAmountInput').val('');
        $('#creditCustomerSummary').addClass('d-none');
        syncPillsFromSelect();
        updateCheckoutSummary();
    }

    function updateCreditCustomerSummary() {
        var methods = CFG.payment_methods || [];
        var selected = methods.find(function (m) { return m.payment_method_id === state.selected_payment_method_id; });
        var isCredit = !!(selected && selected.type === 'credit');

        $('#creditCustomerSummary').toggleClass('d-none', !isCredit);
        if (isCredit) {
            var $opt = $('#customer_id').find(':selected');
            var name = $.trim($opt.text());
            var limit = parseFloat($opt.data('credit-limit') || 0);

            $('#creditCustomerText').text(limit > 0
                ? tr('customer_with_credit', 'Customer: :name · Credit limit: :amount', {name: name, amount: money(limit)})
                : tr('customer_label', 'Customer: :name', {name: name}));
        }

        updateStoreCreditSummary(selected);
    }

    function updateStoreCreditSummary(selected) {
        var isStoreCredit = !!(selected && selected.type === 'store_credit');

        $('#storeCreditSummary').toggleClass('d-none', !isStoreCredit);
        if (!isStoreCredit) return;

        var $opt = $('#customer_id').find(':selected');
        var balance = parseFloat($opt.data('store-credit-balance') || 0);

        $('#storeCreditText').text(tr('available_store_credit', 'Available store credit: :amount', {amount: money(balance)}));
    }

    // True when any tendered payment (single or multi-pay) uses a
    // credit-type PaymentMethod - matches the type === 'credit' check
    // OrderService::post() already uses for its JV generation.
    function hasCreditPayment() {
        return state.payments.some(function (p) {
            var m = (CFG.payment_methods || []).find(function (x) { return x.payment_method_id === p.payment_method_id; });
            return m && m.type === 'credit';
        });
    }

    // Mirrors hasCreditPayment() for the store_credit type.
    function hasStoreCreditPayment() {
        return state.payments.some(function (p) {
            var m = (CFG.payment_methods || []).find(function (x) { return x.payment_method_id === p.payment_method_id; });
            return m && m.type === 'store_credit';
        });
    }

    // Sum of tendered amounts across store_credit-type payments - used for
    // the client-side "amount <= available balance" convenience check
    // (the authoritative check is server-side, in
    // CustomerStoreCreditService::redeem()).
    function storeCreditAmountTendered() {
        var total = 0;
        state.payments.forEach(function (p) {
            var m = (CFG.payment_methods || []).find(function (x) { return x.payment_method_id === p.payment_method_id; });
            if (m && m.type === 'store_credit') {
                total += parseFloat(p.amount) || 0;
            }
        });
        return total;
    }

    // Shown after a Credit-type sale completes - due date/note are optional
    // (see submitCreditInfo()), the customer itself is already guaranteed
    // non-walk-in before checkout starts (see completeSale()'s hard gate).
    function showCreditPaymentModal(order) {
        var $opt = $('#customer_id').find(':selected');
        var creditDays = parseInt($opt.data('credit-days'), 10) || 0;
        var orderDate = order.order_date ? new Date(order.order_date) : new Date();

        state.credit_payment_order_id = order.order_id;
        $('#creditCustomerName').text($.trim($opt.text()));
        $('#creditNote').val('');

        if (!isNaN(orderDate.getTime())) {
            orderDate.setDate(orderDate.getDate() + creditDays);
            $('#creditDueDate').val(orderDate.toISOString().slice(0, 10));
        } else {
            $('#creditDueDate').val('');
        }

        state.credit_payment_modal.show();
    }

    // saveFields=false is the "Skip" path - still records nothing but still
    // completes the reset-for-new-sale flow (see completeSale()).
    function submitCreditInfo(saveFields) {
        var order_id = state.credit_payment_order_id;

        function finish() {
            state.credit_payment_order_id = null;
            state.credit_payment_modal.hide();
            resetForNewSale();
        }

        if (!saveFields || !order_id) {
            finish();
            return;
        }

        ajaxRequest({
            url: URLS.order_credit_info,
            method: 'POST',
            data: {
                order_id: order_id,
                due_date: $('#creditDueDate').val() || null,
                notes: $('#creditNote').val() || null,
            },
        })
            .then(finish)
            .catch(function (err) {
                errorMessage(err.Message || t('unable_save_credit_details', 'Unable to save credit payment details.'));
                finish();
            });
    }

    function renderPayments() {
        var $wrap = $('#paymentRows');
        $wrap.empty();

        var methods = filterPaymentMethodsForDisplay(CFG.payment_methods);

        state.payments.forEach(function (payment, idx) {
            var optionsHtml = methods.map(function (m) {
                return '<option value="' + m.payment_method_id + '" data-type="' + m.type + '"' +
                    (m.payment_method_id === payment.payment_method_id ? ' selected' : '') + '>' +
                    escapeHtml(m.name) + '</option>';
            }).join('');

            var selectedMethod = methods.find(function (m) { return m.payment_method_id === payment.payment_method_id; });
            var showRef = selectedMethod && ['card', 'bank'].includes(selectedMethod.type);

            var $row = $(
                '<div class="row g-2 mb-2 payment-row" data-idx="' + idx + '">' +
                    '<div class="col-5"><select class="form-select form-select-sm payment-method">' +
                        '<option value="">--Method--</option>' + optionsHtml + '</select></div>' +
                    '<div class="col-4"><input type="number" step="0.01" min="0" class="form-control form-control-sm payment-amount" value="' + payment.amount + '" placeholder="' + t('amount', 'Amount') + '"></div>' +
                    '<div class="col-2 payment-ref-wrap" style="display:' + (showRef ? 'block' : 'none') + '">' +
                        '<input type="text" class="form-control form-control-sm payment-ref" value="' + (payment.reference_no || '') + '" placeholder="' + t('ref_placeholder', 'Ref #') + '"></div>' +
                    '<div class="col-1"><button type="button" class="btn btn-sm btn-outline-danger payment-remove"><i class="fa fa-times"></i></button></div>' +
                '</div>'
            );

            $wrap.append($row);
        });

        wirePaymentRowEvents();
        recalcLocal();
    }

    function wirePaymentRowEvents() {
        $('#paymentRows').off('change', '.payment-method').on('change', '.payment-method', function () {
            var idx = $(this).closest('.payment-row').data('idx');
            var method_id = $(this).val();
            var type = $(this).find(':selected').data('type');

            state.payments[idx].payment_method_id = method_id;
            $(this).closest('.payment-row').find('.payment-ref-wrap').toggle(['card', 'bank'].includes(type));
            recalcLocal();
        });

        $('#paymentRows').off('input', '.payment-amount').on('input', '.payment-amount', function () {
            var idx = $(this).closest('.payment-row').data('idx');
            state.payments[idx].amount = parseFloat($(this).val()) || 0;
            recalcPayments();
        });

        $('#paymentRows').off('input', '.payment-ref').on('input', '.payment-ref', function () {
            var idx = $(this).closest('.payment-row').data('idx');
            state.payments[idx].reference_no = $(this).val();
        });

        $('#paymentRows').off('click', '.payment-remove').on('click', '.payment-remove', function () {
            var idx = $(this).closest('.payment-row').data('idx');
            state.payments.splice(idx, 1);
            renderPayments();
        });
    }

    function recalcPayments(total) {
        if (total === undefined) {
            total = parseFloat($('#sumTotal').text()) || 0;
        }

        var entered = state.payments.reduce(function (sum, p) { return sum + (parseFloat(p.amount) || 0); }, 0);
        var diff = total - entered;

        $('#paymentEntered').text(money(entered));

        if (diff > 0.004) {
            $('#paymentRemainingLabel').text(t('remaining', 'Remaining'));
            $('#paymentRemaining').text(money(diff));
        } else {
            $('#paymentRemainingLabel').text(t('change_due', 'Change Due'));
            $('#paymentRemaining').text(money(Math.abs(diff)));
        }
    }

    // ==============================
    // BUILD PAYLOAD / STORE
    // ==============================
    function buildStorePayload(status) {
        var products = state.cart.map(function (line) {
            var item = {
                product_variation_id: line.product_variation_id,
                quantity: line.quantity,
                unit_id: line.unit_id,
                product_variation_unit_conversion_id: line.product_variation_unit_conversion_id || null,
                notes: line.notes || null,
            };

            if (line.track_serial_number) {
                item.serial_numbers = line.serial_numbers || [];
            }

            // unit_price is only sent when the cashier actually edited the
            // price field - otherwise the server's VariationPricingService
            // resolves it fresh from this line's Sale Type. Always sending
            // line.unit_price here (even when it's just whatever the last
            // price-resolution response filled in) would make the server
            // treat every line as a manual override.
            if (can('order.price.change') && line.manual_override) {
                item.unit_price = line.unit_price;
            }
            if (SETTING.enable_discount && ['line', 'both'].includes(SETTING.discount_level)) {
                item.discount = line.discount;
            }
            // Only sent when the cashier overrode this one line's Sale Type -
            // otherwise it inherits the order-level sale_type_id below.
            if (line.sale_type_id) {
                item.sale_type_id = line.sale_type_id;
            }

            return item;
        });

        var payload = {
            // Order's register_session_id FK - value still comes from the
            // register session's own (unrenamed) pos_register_session_id PK.
            register_session_id: state.session.pos_register_session_id,
            status: status,
            customer_id: $('#customer_id').val(),
            order_type_id: $('#order_type_id').val(),
            order_source_id: $('#order_source_id').val(),
            sale_type_id: $('#sale_type_id').val(),
            delivery_address: $('#delivery_address').val(),
            products: products,
        };

        if (canOverrideMinPrice()) {
            payload.override_minimum_price = true;
        }

        if (state.order_id) {
            payload.order_id = state.order_id;
        }

        // "Enable Discount" only toggles the Discount dropdown (a flat named
        // rate) - vouchers are a separate, independent feature (their own
        // order.coupon.apply permission) and must always be sendable
        // regardless of that setting.
        if (SETTING.enable_discount) {
            var discount_id = $('#discount_id').val();
            if (discount_id) {
                payload.discount_id = discount_id;
            }
        }
        var voucher_code = $('#voucher_code').val();
        if (voucher_code) {
            payload.voucher_code = voucher_code;
        }
        var voucher_id = $('#voucher_id').val();
        if (voucher_id) {
            payload.voucher_id = voucher_id;
        }
        if ($('#use_loyalty_points').is(':checked')) {
            payload.use_loyalty_points = true;
        }

        if (state.payments && state.payments.length) {
            payload.payments = state.payments;
        }

        return payload;
    }

    function renderFromServerOrder(order) {
        var itemDiscount = (order.details || []).reduce(function (sum, d) {
            return sum + (parseFloat(d.discount_amount) || 0);
        }, 0);
        // order.discount_amount is the combined line+order+voucher+loyalty
        // total (see OrderService::saveLinesAndComputeTotals()) - loyalty is
        // broken back out into its own row here, same as item discount is,
        // so "Order Discount" only ever shows the named-discount/voucher
        // portion.
        var loyaltyDiscount = parseFloat(order.loyalty_discount_amount) || 0;
        var orderDiscount = Math.max(0, (parseFloat(order.discount_amount) || 0) - itemDiscount - loyaltyDiscount);

        $('#sumSubtotal').text(money(order.subtotal));
        $('#sumItemDiscount').text(money(itemDiscount));
        $('#sumOrderDiscount').text(money(orderDiscount));
        $('#sumLoyaltyDiscountRow').toggleClass('d-none', loyaltyDiscount <= 0);
        $('#sumLoyaltyDiscount').text(money(loyaltyDiscount));
        $('#sumTax').text(money(order.tax_amount));
        $('#sumTotal').text(money(order.total));
        recalcPayments(parseFloat(order.total) || 0);
    }

    // ==============================
    // HOLD / RESUME
    // ==============================
    function holdOrder() {
        if (state.correction_mode) {
            errorMessage(t('hold_not_while_correcting', 'Hold is not available while correcting a posted order.'));
            return;
        }
        if (!state.session) {
            errorMessage(t('open_session_before_order', 'Open a register session before placing an order.'));
            return;
        }

        if (!state.cart.length) {
            errorMessage(t('cart_is_empty', 'Cart is empty.') || t('cart_empty', 'Cart is empty.'));
            return;
        }

        if (isDeliveryOrderType() && !$('#delivery_address').val().trim()) {
            errorMessage(t('delivery_address_required', 'Delivery address is required for delivery orders.'));
            return;
        }

        var payload = buildStorePayload('hold');

        ajaxRequest({ url: URLS.order_store, method: 'POST', data: payload })
            .then(function (response) {
                var order = response.Data || {};

                // Keep the cart open on this same order (create-vs-update in
                // OrderService::save() is keyed off order_id) rather than
                // resetting to a blank sale - otherwise the cashier has no way
                // to keep adjusting this held order without going through the
                // Held Orders panel first, and a second Hold click with no
                // order_id would silently create a duplicate order instead of
                // updating this one.
                state.order_id = order.order_id || state.order_id;
                state.order_daily_id = order.daily_order_id || state.order_daily_id;

                successMessage(tr('order_held_message', 'Order held:suffix. Keep editing to update it, or use Clear Cart to start a new sale.', {suffix: state.order_daily_id ? (' (#' + state.order_daily_id + ')') : ''}));
                renderCart();
                loadHeldOrdersCount();
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_hold_order', 'Unable to hold order.'));
            });
    }

    function loadHeldOrdersCount() {
        fetchHeldOrders(function (rows) {
            $('#heldOrdersCount').text(rows.length);
        });
    }

    // ==============================
    // REORDER (from admin/order/show's Reorder button - ?reorder_from=<id>
    // on the pos-screen URL, see PosScreenController::index()/POS_CONFIG)
    // ==============================
    function reorderFromOrder(order_id) {
        if (state.reorder_applied) return;
        state.reorder_applied = true;

        ajaxRequest({ url: URLS.order_details + '/' + order_id })
            .then(function (response) {
                var data = response.Data || {};
                var header = data.header || {};
                var details = data.details || [];

                if (header.business_id && CFG.business_id && header.business_id !== CFG.business_id) {
                    errorMessage(t('order_different_business', 'This order belongs to a different business and cannot be reordered here.'));
                    return;
                }

                // Deliberately left null - Hold/Pay must create a brand-new
                // order (new daily_order_id, current date/time) rather than
                // editing the source order (see OrderService::save()'s
                // create-vs-update branch, keyed off order_id presence).
                state.order_id = null;
                state.order_daily_id = null;
                state.cart = [];
                state.line_seq = 0;

                details.forEach(function (d) {
                    state.line_seq += 1;
                    state.cart.push({
                        line_key: 'line_' + state.line_seq,
                        product_variation_id: d.product_variation_id,
                        product_name: d.product_name || '',
                        variation_name: d.product_variation_name || '',
                        unit_id: d.unit_id,
                        unit_name: d.unit_name || t('unit', 'Unit'),
                        quantity: d.quantity,
                        unit_price: d.unit_price,
                        discount: d.discount,
                        sale_type_id: d.sale_type_id || null,
                        manual_override: false,
                        notes: d.notes || '',
                        is_track_stock: !!d.is_track_stock,
                        available_stock: d.available_stock !== undefined ? d.available_stock : null,
                        track_serial_number: !!d.track_serial_number,
                        serial_numbers: d.serial_numbers || [],
                    });
                });

                $('#delivery_address').val(header.delivery_address || '');

                if (header.customer_id) {
                    $('#customer_id').val(header.customer_id).trigger('change');
                }
                if (header.order_type_id) {
                    $('#order_type_id').val(header.order_type_id).trigger('change');
                }
                if (header.sale_type_id) {
                    $('#sale_type_id').val(header.sale_type_id).trigger('change');
                }

                // Payments/discount/voucher are intentionally NOT carried
                // over - a reorder is a fresh sale and the server always
                // recomputes totals from scratch on save anyway.
                state.payments = [];
                renderCart();
                resetPaymentSelection();
                selectDefaultPaymentMethod();

                successMessage(tr('cart_loaded_reorder', 'Cart loaded from order #:id for reorder.', {id: header.daily_order_id || ''}));

                if (window.history && window.history.replaceState) {
                    var url = new URL(window.location.href);
                    url.searchParams.delete('reorder_from');
                    window.history.replaceState(null, '', url.toString());
                }
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_load_reorder', 'Unable to load order for reorder.'));
            });
    }

    // ==============================
    // REPORTS (my register sessions - non-transactional)
    // ==============================
    function loadPosReports() {
        var $list = $('#posReportsList');
        $('#posReportsSummary').addClass('d-none');
        $list.html('<div class="text-muted text-center py-3">' + t('loading', 'Loading...') + '</div>');

        ajaxRequest({ url: URLS.session_my_history })
            .then(function (response) {
                var rows = response.Data || [];
                $list.empty();

                if (!rows.length) {
                    $list.append('<div class="text-muted text-center py-3">No sessions found</div>');
                    return;
                }

                rows.forEach(function (row) {
                    var $item = $(
                        '<a href="javascript:void(0);" class="list-group-item list-group-item-action">' +
                            '<div class="d-flex justify-content-between">' +
                                '<span>' + escapeHtml(row.register && row.register.name || t('register', 'Register')) + '</span>' +
                                '<span class="badge ' + (row.status === 'open' ? 'bg-label-success' : 'bg-label-secondary') + '">' + escapeHtml(row.status) + '</span>' +
                            '</div>' +
                            '<small class="text-muted">' + escapeHtml(row.opening_datetime || '') + '</small>' +
                        '</a>'
                    );

                    $item.on('click', function () {
                        loadPosReportSummary(row.pos_register_session_id);
                    });

                    $list.append($item);
                });
            })
            .catch(function (err) {
                $list.html('<div class="text-danger text-center py-3">' + escapeHtml(err.Message || t('unable_load_sessions', 'Unable to load sessions.')) + '</div>');
            });
    }

    function loadPosReportSummary(pos_register_session_id) {
        ajaxRequest({ url: URLS.session_summary + '/' + pos_register_session_id })
            .then(function (response) {
                var s = response.Data || {};
                state.reports_viewed_session_id = pos_register_session_id;

                $('#repTotalOrders').text(s.total_orders || 0);
                $('#repTotalSales').text(money(s.total_sales_amount));

                var $paymentRows = $('#repPaymentRows').empty();
                (s.payment_method_totals || []).forEach(function (row) {
                    $paymentRows.append(
                        '<tr><td>' + escapeHtml(row.name) + '</td>' +
                        '<td class="text-end">' + (row.order_count || 0) + '</td>' +
                        '<td class="text-end">' + money(row.total) + '</td></tr>'
                    );
                });
                if (s.multi_payment_order_count) {
                    $paymentRows.append(
                        '<tr><td>Multi</td><td class="text-end">' + s.multi_payment_order_count +
                        '</td><td class="text-end">' + money(s.multi_payment_amount) + '</td></tr>'
                    );
                }

                var $sourceRows = $('#repSourceRows').empty();
                (s.order_source_totals || []).forEach(function (row) {
                    $sourceRows.append(
                        '<tr><td>' + escapeHtml(row.name) + '</td>' +
                        '<td class="text-end">' + (row.order_count || 0) + '</td>' +
                        '<td class="text-end">' + money(row.total) + '</td></tr>'
                    );
                });

                $('#repDiscountOrderCount').text(s.discount_order_count || 0);
                $('#repTotalDiscount').text(money(s.total_discount));
                $('#repTaxOrderCount').text(s.tax_order_count || 0);
                $('#repTotalTax').text(money(s.total_tax));

                $('#repOpeningCash').text(money(s.opening_cash));
                $('#repCashRefunds').text(money(s.cash_refunds));
                $('#repCashIn').text(money(s.cash_movements_in));
                $('#repCashOut').text(money(s.cash_movements_out));
                $('#repExpenses').text(money(s.total_expenses));
                $('#repExpectedCash').text(money(s.expected_cash));
                $('#repActualCash').text(s.actual_cash != null ? money(s.actual_cash) : '-');

                $('#posReportsSummary').removeClass('d-none');
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_load_session_summary', 'Unable to load session summary.'));
            });
    }

    function loadHeldOrders() {
        fetchHeldOrders(function (rows) {
            var $list = $('#heldOrdersList');
            $list.empty();

            if (!rows.length) {
                $list.append('<div class="text-muted text-center py-3">No held orders</div>');
                return;
            }

            rows.forEach(function (row) {
                var $item = $(
                    '<a href="javascript:void(0);" class="list-group-item list-group-item-action">' +
                        '<div class="d-flex justify-content-between">' +
                            '<span>#' + escapeHtml(row.daily_order_id) + '</span>' +
                            '<span class="fw-bold">' + money(row.total) + '</span>' +
                        '</div>' +
                    '</a>'
                );

                $item.on('click', function () {
                    resumeOrder(row.order_id, row.raw_status);
                });

                $list.append($item);
            });
        });
    }

    // Uses a plain $.ajax call (not the shared ajaxRequest() helper) because
    // /admin/order/data is a Yajra DataTables endpoint - it responds with the
    // raw {draw, recordsTotal, recordsFiltered, data} shape, not the
    // {Success, Message, Data} envelope ajaxRequest() requires to resolve.
    // Routing this through ajaxRequest() would always hit its rejection
    // branch (no `Success` key) and silently return an empty list.
    function fetchHeldOrders(callback) {
        $.ajax({
            url: URLS.order_data,
            method: 'POST',
            data: {
                draw: 1,
                start: 0,
                length: 50,
                // Both statuses are editable/resumable from POS - 'draft'
                // covers orders left stuck mid-checkout (e.g. completeSale()'s
                // store-then-complete second step failed), which used to be
                // invisible here even though they're fully recoverable.
                status: ['draft', 'hold'],
                cashier_id: state.session ? state.session.cashier_id : null,
                include_null_cashier: 1,
                business_id: CFG.business_id,
            },
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content'),
            },
        })
            .done(function (response) {
                callback((response && response.data) || []);
            })
            .fail(function () {
                callback([]);
            });
    }

    // Loads the order's details into the cart AFTER any hold -> draft
    // transition (see below) rather than before - OrderService::resume()
    // re-checks stock and can remove/reduce lines in place
    // (revalidateStockOnResume()), so fetching details first would load the
    // cart with stale, pre-adjustment quantities.
    function resumeOrder(order_id, status) {
        function loadDetailsIntoCart() {
            ajaxRequest({ url: URLS.order_details + '/' + order_id })
                .then(function (response) {
                    loadCartFromDetails(response.Data);
                    state.held_orders_offcanvas.hide();
                    loadHeldOrdersCount();
                })
                .catch(function (err) {
                    errorMessage(err.Message || t('unable_load_order_details', 'Unable to load order details.'));
                });
        }

        // OrderService::resume() only guards a hold -> draft transition and
        // throws otherwise - an order that's already 'draft' (e.g. left
        // stuck mid-checkout) just needs loading into the cart for editing,
        // no status transition/stock revalidation.
        if (status !== 'hold') {
            successMessage(t('order_loaded_editing', 'Order loaded for editing.'));
            loadDetailsIntoCart();
            return;
        }

        ajaxRequest({ url: URLS.order_resume, method: 'POST', data: { order_id: order_id } })
            .then(function (response) {
                var warnings = (response.Data && response.Data.stock_warnings) || [];

                if (warnings.length) {
                    warningMessage(tr('order_resumed_stock_changed', 'Order resumed - stock changed while it was on hold: :warnings', {warnings: warnings.join(' ')}));
                } else {
                    successMessage(t('order_resumed', 'Order resumed.'));
                }

                loadDetailsIntoCart();
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_resume_order', 'Unable to resume order.'));
            });
    }

    function loadCartFromDetails(data) {
        var header = data.header || {};
        var details = data.details || [];
        var payments = data.payments || [];

        state.order_id = header.order_id;
        state.order_daily_id = header.daily_order_id || null;
        state.cart = [];
        state.line_seq = 0;

        details.forEach(function (d) {
            state.line_seq += 1;
            state.cart.push({
                line_key: 'line_' + state.line_seq,
                product_variation_id: d.product_variation_id,
                product_name: d.product_name || '',
                variation_name: d.product_variation_name || '',
                unit_id: d.unit_id,
                unit_name: d.unit_name || t('unit', 'Unit'),
                quantity: d.quantity,
                unit_price: d.unit_price,
                discount: d.discount,
                sale_type_id: d.sale_type_id || null,
                manual_override: false,
                notes: d.notes || '',
                is_track_stock: !!d.is_track_stock,
                available_stock: d.available_stock !== undefined ? d.available_stock : null,
                track_serial_number: !!d.track_serial_number,
                serial_numbers: d.serial_numbers || [],
            });
        });

        $('#delivery_address').val(header.delivery_address || '');

        if (header.customer_id) {
            $('#customer_id').val(header.customer_id).trigger('change');
        }
        if (header.order_type_id) {
            $('#order_type_id').val(header.order_type_id).trigger('change');
        }
        if (header.order_source_id) {
            $('#order_source_id').val(header.order_source_id).trigger('change');
        }
        if (header.sale_type_id) {
            // Restores a held order's exact saved prices - set without
            // triggering repriceCartForSaleType() (that would overwrite them
            // with freshly-resolved current prices instead).
            $('#sale_type_id').val(header.sale_type_id);
            syncPillsFromSelect();
        }
        if (header.discount_id) {
            $('#discount_id').val(header.discount_id).trigger('change');
        }
        if (header.voucher_code) {
            $('#voucher_code').val(header.voucher_code);
        }
        if (parseFloat(header.loyalty_points_used) > 0) {
            $('#use_loyalty_points').prop('checked', true);
        }

        state.payments = payments.map(function (p) {
            return {
                payment_method_id: p.payment_method_id,
                amount: p.amount,
                reference_no: p.reference_no,
            };
        });

        renderCart();

        if (state.payments.length === 1) {
            activatePaymentUI(state.payments[0].payment_method_id, false);
            $('#paidAmountInput').val(money(state.payments[0].amount));
            recalcPayments();
        } else if (state.payments.length > 1) {
            activatePaymentUI(null, true);
            renderPayments();
        } else {
            // Hold orders are typically saved before any payment is taken -
            // default to Cash + full total the same way a brand-new sale
            // does (selectDefaultPaymentMethod()) instead of leaving the
            // payment method unselected, which left state.payments empty
            // and made a directly-typed Paid Amount silently not count
            // toward completeSale()'s total check.
            selectDefaultPaymentMethod();
        }
    }

    // ==============================
    // COMPLETE SALE / CORRECT
    // ==============================
    function completeSale() {
        if (!state.session) {
            errorMessage(t('open_session_before_order', 'Open a register session before placing an order.'));
            return;
        }

        if (!state.cart.length) {
            errorMessage(t('cart_is_empty', 'Cart is empty.') || t('cart_empty', 'Cart is empty.'));
            return;
        }

        if (isDeliveryOrderType() && !$('#delivery_address').val().trim()) {
            errorMessage(t('delivery_address_required', 'Delivery address is required for delivery orders.'));
            return;
        }

        // Hard gate: a credit sale must be tied to a real customer, not the
        // walk-in one, so it can actually be tracked/recovered on their
        // ledger. Checked before any request is sent.
        if (hasCreditPayment() && $('#customer_id').find(':selected').data('walkin') == 1) {
            errorMessage(t('select_real_customer_credit', 'Select a real customer before completing a credit sale.'));
            return;
        }

        // Same hard gate for Store Credit, plus a client-side convenience
        // check against the balance shown - the authoritative check is
        // still server-side (CustomerStoreCreditService::redeem()).
        if (hasStoreCreditPayment()) {
            if ($('#customer_id').find(':selected').data('walkin') == 1) {
                errorMessage(t('select_real_customer_store_credit', 'Select a real customer before applying store credit.'));
                return;
            }

            var storeCreditBalance = parseFloat($('#customer_id').find(':selected').data('store-credit-balance') || 0);

            if (storeCreditAmountTendered() > storeCreditBalance + 0.0001) {
                errorMessage(tr('insufficient_store_credit', 'This customer only has :amount in store credit available.', {amount: money(storeCreditBalance)}));
                return;
            }
        }

        if (state.correction_mode) {
            openCorrectionReasonModal();
            return;
        }

        var payload = buildStorePayload('draft');

        ajaxRequest({ url: URLS.order_store, method: 'POST', data: payload })
            .then(function (response) {
                var order = response.Data;
                state.order_id = order.order_id;
                renderFromServerOrder(order);

                // Rounded to cents (not just a tiny epsilon subtracted) so
                // float accumulation across lines/payments can't push an
                // exact "Paid Amount = Total" a fraction of a cent under -
                // and the 0.01 tolerance matches OrderService::post()'s own
                // cash_required/applied_total checks, so a payment the
                // server will accept never gets rejected here first.
                var total = Math.round((parseFloat(order.total) || 0) * 100) / 100;
                var entered = Math.round(state.payments.reduce(function (sum, p) { return sum + (parseFloat(p.amount) || 0); }, 0) * 100) / 100;

                if (entered + 0.01 < total) {
                    errorMessage(t('payment_does_not_cover', 'Payment amount does not cover the total. Please adjust payments.'));
                    return;
                }

                ajaxRequest({
                    url: URLS.order_complete,
                    method: 'POST',
                    data: {
                        order_id: order.order_id,
                        payments: state.payments,
                    },
                })
                    .then(function (completeResponse) {
                        var posted = completeResponse.Data;
                        successMessage(t('sale_completed', 'Sale completed.'));

                        if (SETTING.auto_print_invoice) {
                            silentPrintReceipt(posted.order_id);
                        }

                        if (hasCreditPayment()) {
                            showCreditPaymentModal(posted);
                        } else {
                            resetForNewSale();
                        }
                    })
                    .catch(function (err) {
                        errorMessage(err.Message || t('unable_complete_sale', 'Unable to complete sale.'));
                        // Keep the cart intact so the cashier can correct and retry.
                    });
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_save_order', 'Unable to save order.'));
            });
    }

    function openCorrectionReasonModal() {
        if (!can('order.correct')) {
            errorMessage(t('no_correct_permission', 'You do not have permission to correct orders.'));
            return;
        }

        if (!state.order_id) {
            errorMessage(t('no_order_for_correction', 'No order loaded for correction.'));
            return;
        }

        $('#correction_reason').val('');
        if (state.correction_reason_modal) {
            state.correction_reason_modal.show();
        }
    }

    function submitCorrectionWithReason() {
        var reason = ($('#correction_reason').val() || '').trim();

        if (!reason) {
            errorMessage(t('correction_reason_required', 'A correction reason is required.'));
            return;
        }

        var payload = buildStorePayload('posted');
        payload.order_id = state.order_id;
        payload.reason = reason;
        payload.payments = state.payments;

        if (!payload.payments || !payload.payments.length) {
            errorMessage(t('at_least_one_payment', 'At least one payment is required.'));
            return;
        }

        ajaxRequest({ url: URLS.order_correct, method: 'POST', data: payload })
            .then(function (response) {
                var order = response.Data;

                if (state.correction_reason_modal) {
                    state.correction_reason_modal.hide();
                }

                successMessage(t('order_corrected', 'Order corrected.'));

                if (SETTING.auto_print_invoice) {
                    silentPrintReceipt(order.order_id);
                }

                resetForNewSale();
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_correct_order', 'Unable to correct order.'));
            });
    }

    function loadCorrectionOrder(order_id) {
        ajaxRequest({ url: URLS.order_details + '/' + order_id })
            .then(function (response) {
                var data = response.Data || {};
                var header = data.header || {};

                if (!header.can_correct) {
                    errorMessage(t('order_cannot_be_corrected', 'This order cannot be corrected (same-day POS posted orders only, with no returns or settlements).'));
                    clearCorrectQueryParam();
                    return;
                }

                loadCartFromDetails(data);
                enterCorrectionMode(header.daily_order_id || '');
                clearCorrectQueryParam();
                successMessage(tr('order_loaded_correction', 'Order #:id loaded for same-day correction.', {id: header.daily_order_id || ''}));
            })
            .catch(function (err) {
                errorMessage(err.Message || t('unable_load_correction', 'Unable to load order for correction.'));
                clearCorrectQueryParam();
            });
    }

    function enterCorrectionMode(dailyOrderId) {
        state.correction_mode = true;
        $('#posCorrectionBanner').removeClass('d-none').addClass('d-flex');
        $('#posCorrectionOrderLabel').text(dailyOrderId ? ('#' + dailyOrderId) : '');
        $('#holdOrderBtn').addClass('d-none');
        $('#completeSaleBtn').html('<i class="fa fa-pencil"></i> ' + t('apply_correction', 'Apply Correction'));
        $('#cancelCorrectionBtn').removeClass('d-none');
    }

    function cancelCorrectionMode() {
        if (!state.correction_mode) {
            return;
        }

        resetForNewSale();
        successMessage(t('correction_cancelled', 'Correction cancelled.'));
    }

    function clearCorrectQueryParam() {
        try {
            var url = new URL(window.location.href);
            if (url.searchParams.has('correct')) {
                url.searchParams.delete('correct');
                window.history.replaceState({}, '', url.pathname + url.search + url.hash);
            }
        } catch (e) {
            // Ignore history API failures in older browsers.
        }
    }

    // ==============================
    // SILENT RECEIPT PRINT
    // ==============================
    // Prints the order's receipt (thermal or A4, per the business's existing
    // print/order_print resolution) directly to the configured printer via
    // a hidden iframe + auto window.print() - no new tab/popup, no toolbar,
    // no manual "Print"/"OK" click. Never blocks the POS: any failure just
    // shows a notification so the cashier can move on to the next sale.
    function silentPrintReceipt(orderId) {
        if (!orderId) {
            return;
        }

        var iframe = document.createElement('iframe');
        iframe.style.position = 'fixed';
        iframe.style.right = '0';
        iframe.style.bottom = '0';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.style.border = '0';
        iframe.setAttribute('aria-hidden', 'true');

        var cleaned = false;
        var cleanupTimer = null;

        function cleanup() {
            if (cleaned) {
                return;
            }
            cleaned = true;
            if (cleanupTimer) {
                clearTimeout(cleanupTimer);
            }
            if (iframe.parentNode) {
                iframe.parentNode.removeChild(iframe);
            }
        }

        var printed = false;

        iframe.onload = function () {
            // Assigning src on an already-inserted iframe makes it load
            // about:blank first, which also fires onload - guard so the
            // receipt is only ever sent to the printer once.
            if (printed) {
                return;
            }
            printed = true;

            var win = null;
            var doc = null;
            try {
                win = iframe.contentWindow;
                doc = iframe.contentDocument || (win && win.document);
            } catch (e) {
                win = null;
                doc = null;
            }

            if (!win || !doc) {
                errorMessage(t('receipt_prepare_failed', 'Receipt could not be prepared for printing.'));
                cleanup();
                return;
            }

            // The receipt view (thermal or A4) always renders a .print-page
            // element; its absence means the request hit an error page
            // (order not found, server error, etc.) instead of a receipt -
            // notify instead of printing a broken page.
            if (!doc.querySelector('.print-page')) {
                errorMessage(t('receipt_generate_failed_sale_ok', 'Receipt could not be generated for the printer. The sale was still completed - reprint from Order History if needed.'));
                cleanup();
                return;
            }

            try {
                win.addEventListener('afterprint', cleanup);
            } catch (e) {
                // Some browsers don't support afterprint on iframe windows;
                // the fallback timeout below still cleans up.
            }

            try {
                win.focus();
                win.print();
            } catch (e) {
                errorMessage(t('thermal_print_failed', 'Unable to send the receipt to the thermal printer. Please check the printer connection.'));
                cleanup();
                return;
            }

            // Fallback cleanup in case afterprint never fires (e.g. the
            // printer is offline and the browser doesn't emit the event).
            // Long enough that on a non-kiosk-printing browser (native print
            // dialog still visible) this doesn't yank the iframe - and the
            // dialog with it - out from under a cashier who pauses on it.
            cleanupTimer = setTimeout(cleanup, 60000);
        };

        iframe.onerror = function () {
            errorMessage(t('receipt_generate_failed', 'Receipt could not be generated. The order was placed, but printing failed.'));
            cleanup();
        };

        // Set src before inserting into the DOM so the browser navigates
        // straight to the receipt instead of loading about:blank first.
        iframe.src = URLS.order_print + '/' + orderId + '/print?auto=1';
        document.body.appendChild(iframe);
    }

    // ==============================
    // RESET
    // ==============================
    function resetForNewSale() {
        state.cart = [];
        state.payments = [];
        state.order_id = null;
        state.order_daily_id = null;
        state.correction_mode = false;
        $('#posCorrectionBanner').addClass('d-none').removeClass('d-flex');
        $('#posCorrectionOrderLabel').text('');
        $('#holdOrderBtn').removeClass('d-none');
        $('#cancelCorrectionBtn').addClass('d-none');
        $('#completeSaleBtn').html('<i class="fa fa-check"></i> ' + t('pay', 'Pay') + ' <span class="pos-key-hint">(F9)</span>');
        $('#voucher_code').val('');
        $('#voucher_id').val('');
        $('#voucherSearchResults').hide().empty();
        $('#discount_id').val('').trigger('change');
        $('#use_loyalty_points').prop('checked', false);
        $('#sumLoyaltyDiscountRow').addClass('d-none');
        $('#sumLoyaltyDiscount').text(money(0));
        $('#delivery_address').val('');
        $('#sale_type_id').val(state.default_sale_type_id);
        syncPillsFromSelect();
        renderCart();
        renderPayments();
        selectDefaultPaymentMethod();
        toggleCheckoutPanel(false);
    }

    function resetScreenState() {
        resetForNewSale();
        $('#registerBadge').addClass('d-none');
        $('#cashInBtn, #cashOutBtn, #closeRegisterBtn').addClass('d-none');
    }
})();
