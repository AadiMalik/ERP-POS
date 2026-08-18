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
    };

    function can(perm) {
        return !!PERM[perm];
    }

    function money(v) {
        v = parseFloat(v || 0);
        if (isNaN(v)) v = 0;
        return v.toFixed(2);
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

        // Customer search also matches phone/email (not just the visible
        // name text) - each <option data-phone data-email> is rendered by
        // index.blade.php from the User model's own fields.
        $('#customer_id').select2({
            placeholder: 'Walk-in Customer',
            matcher: function (params, data) {
                var term = ($.trim(params.term) || '').toLowerCase();
                if (!term || !data.id) return data;

                var $opt = data.element ? $(data.element) : null;
                var haystack = [
                    data.text || '',
                    $opt ? ($opt.data('phone') || '') : '',
                    $opt ? ($opt.data('email') || '') : '',
                ].join(' ').toLowerCase();

                return haystack.indexOf(term) > -1 ? data : null;
            },
        });

        state.open_session_modal = new bootstrap.Modal(document.getElementById('openSessionModal'));
        state.close_session_modal = new bootstrap.Modal(document.getElementById('closeSessionModal'));
        state.cash_movement_modal = new bootstrap.Modal(document.getElementById('cashMovementModal'));
        state.held_orders_offcanvas = new bootstrap.Offcanvas(document.getElementById('heldOrdersOffcanvas'));
        state.pos_reports_offcanvas = new bootstrap.Offcanvas(document.getElementById('posReportsOffcanvas'));
        state.product_picker_modal = new bootstrap.Modal(document.getElementById('productPickerModal'));
        state.add_customer_modal = new bootstrap.Modal(document.getElementById('addCustomerModal'));
        if ($('#addExpenseModal').length) {
            state.add_expense_modal = new bootstrap.Modal(document.getElementById('addExpenseModal'));
        }
        if ($('#changeBranchModal').length) {
            state.change_branch_modal = new bootstrap.Modal(document.getElementById('changeBranchModal'));
        }

        renderPaymentMethodTiles();
        selectDefaultPaymentMethod();
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
                errorMessage(err.Message || 'Unable to check register session.');
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

        var registerName = (state.session.register && state.session.register.name) || 'Register';
        $('#registerBadge')
            .removeClass('d-none')
            .html(escapeHtml(registerName) + ' <span class="pos-register-status-pill">OPEN</span>');

        $('#cashInBtn, #cashOutBtn, #closeRegisterBtn').removeClass('d-none');
        if (can('expense.access')) {
            $('#addExpenseBtn').removeClass('d-none');
        }

        loadHeldOrdersCount();
        loadProductsByCategory('');

        if (CFG.reorder_from) {
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
            if (product) {
                handleGridProductClick(product);
            }
        });

        $('#productPickerGrid').on('click', '.product-card', function () {
            var idx = $(this).data('idx');
            var pv = state.picker.variations[idx];
            if (!pv) return;

            addProductToCart(pv, { image: firstImageOf(state.picker.product) });
            state.product_picker_modal.hide();
        });

        $('#cartRows').on('input change', '.line-qty, .line-price, .line-discount', function () {
            var key = $(this).closest('.cart-line').data('key');
            updateLineFromRow(key, $(this).closest('.cart-line'));
        });

        $('#cartRows').on('click', '.line-remove', function () {
            var key = $(this).closest('.cart-line').data('key');
            state.cart = state.cart.filter(function (l) { return l.line_key !== key; });
            renderCart();
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
            renderCart();
        });

        $('#customer_id').on('change', function () {
            updateCreditHint();
            updateCreditCustomerSummary();
        });
        updateCreditHint();

        $('#creditCustomerChangeLink').on('click', function () {
            $('#customer_id').select2('open');
        });

        $('#addPaymentRowBtn').on('click', function () {
            state.payments.push({ payment_method_id: '', amount: 0, reference_no: '' });
            renderPayments();
        });

        $('#applyVoucherBtn').on('click', function () {
            // Voucher application is not a separate endpoint - it rides along
            // with the next store() call. Just recompute the local preview.
            recalcLocal();
        });

        $('#discount_id').on('change', recalcLocal);

        $('#holdOrderBtn').on('click', holdOrder);
        $('#completeSaleBtn').on('click', completeSale);

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

        $('#order_type_id, #order_source_id, #paymentMethodSelect').on('change', syncPillsFromSelect);
        syncPillsFromSelect();

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
                $('#changeBranchBranchId').html('<option value="">--Select Branch--</option>');
                $('#changeBranchWarehouseId').html('<option value="">--Select Warehouse--</option>');
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
                        errorMessage(err.Message || 'Unable to load branches.');
                    });
            });
        }

        // ---- Add Customer modal ----
        $('#addCustomerBtn').on('click', function () {
            $('#new_customer_name, #new_customer_email, #new_customer_phone').val('');
            state.add_customer_modal.show();
        });
        $('#addCustomerSubmitBtn').on('click', submitAddCustomer);

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
            if (!state.payments.length) return;
            state.payments[0].amount = parseFloat($(this).val()) || 0;
            recalcPayments();
        });

        // ---- Keyboard shortcuts matching the on-screen (F3/F6/F9) hints ----
        $(document).on('keydown', function (e) {
            if (e.key === 'F3') {
                e.preventDefault();
                $('#customer_id').select2('open');
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
    }

    // "Delivery" order types are identified by the seeded order_types.code
    // 'DELIVERY' (see OrderTypeService::$default_types / OrderService::save())
    // - mirrors the server-side check so the field only shows/blocks
    // submission when it will actually be required.
    function isDeliveryOrderType() {
        return $('#order_type_id').find(':selected').data('code') === 'DELIVERY';
    }

    function updateDeliveryAddressVisibility() {
        $('#deliveryAddressWrap').toggleClass('d-none', !isDeliveryOrderType());
    }

    // ==============================
    // QUICK ADD CUSTOMER
    // ==============================
    function submitAddCustomer() {
        var name = $('#new_customer_name').val().trim();
        var email = $('#new_customer_email').val().trim();
        var phone = $('#new_customer_phone').val().trim();

        if (!name || !email) {
            errorMessage('Name and Email are required.');
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
                    .attr('data-credit-limit', customer.credit_limit || 0)
                    .attr('data-walkin', customer.is_walkin ? 1 : 0)
                    .attr('data-phone', customer.phone || '')
                    .attr('data-email', customer.email || '')
                    .text(customer.name || '');

                $('#customer_id').append($option).val(customer.user_id).trigger('change');

                state.add_customer_modal.hide();
                successMessage('Customer added.');
            })
            .catch(function (err) {
                errorMessage(err.Message || 'Unable to add customer.');
            });
    }

    // ==============================
    // OPEN / CLOSE SESSION
    // ==============================
    function submitOpenSession() {
        var opening_cash = $('#opening_cash').val();

        if (opening_cash === '' || isNaN(opening_cash)) {
            errorMessage('Please enter a valid opening cash amount.');
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
                errorMessage('Please select a register.');
                return;
            }
            data.pos_register_id = register_id;
        }

        ajaxRequest({ url: URLS.session_open, method: 'POST', data: data })
            .then(function (response) {
                state.session = response.Data;
                state.open_session_modal.hide();
                successMessage('Register session opened.');
                onSessionReady();
            })
            .catch(function (err) {
                errorMessage(err.Message || 'Unable to open register session.');
            });
    }

    function openCashMovementModal(type) {
        $('#cash_movement_type').val(type);
        $('#cashMovementModalTitle').text(type === 'in' ? 'Add Cash (In)' : 'Remove Cash (Out)');
        $('#cash_movement_amount').val('');
        $('#cash_movement_reason').val('');
        state.cash_movement_modal.show();
    }

    function submitCashMovement() {
        var amount = $('#cash_movement_amount').val();

        if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
            errorMessage('Please enter a valid amount.');
            return;
        }

        ajaxRequest({
            url: URLS.session_cash_movement,
            method: 'POST',
            data: {
                pos_register_session_id: state.session.pos_register_session_id,
                type: $('#cash_movement_type').val(),
                amount: amount,
                reason: $('#cash_movement_reason').val(),
            },
        })
            .then(function () {
                successMessage('Cash movement recorded.');
                state.cash_movement_modal.hide();
            })
            .catch(function (err) {
                errorMessage(err.Message || 'Unable to record cash movement.');
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
            errorMessage('Please select an expense category.');
            return;
        }
        if (!amount || isNaN(amount) || parseFloat(amount) <= 0) {
            errorMessage('Please enter a valid amount.');
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
                successMessage('Expense recorded.');
                state.add_expense_modal.hide();
            })
            .catch(function (err) {
                errorMessage(err.Message || 'Unable to record expense.');
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
                errorMessage(err.Message || 'Unable to load session summary.');
            });
    }

    function submitCloseSession() {
        var actual_cash = $('#actual_cash').val();

        if (actual_cash === '' || isNaN(actual_cash)) {
            errorMessage('Please enter the actual cash amount.');
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
                successMessage('Register session closed.');
                state.close_session_modal.hide();
                state.session = null;
                resetScreenState();
                bootstrapSession();
            })
            .catch(function (err) {
                errorMessage(err.Message || 'Unable to close register session.');
            });
    }

    // ==============================
    // PRODUCT SEARCH
    // ==============================
    function searchProducts(term, isScan) {
        ajaxRequest({
            url: URLS.search_products,
            data: { business_id: CFG.business_id, term: term },
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
                errorMessage(err.Message || 'Product search failed.');
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

            var $row = $(
                '<a href="javascript:void(0);" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">' +
                    '<span>' + escapeHtml(product_name) + (variation_name ? ' - ' + escapeHtml(variation_name) : '') +
                        '<small class="text-muted d-block">' + escapeHtml(item.sku || '') + ' ' + escapeHtml(item.barcode || '') + '</small>' +
                    '</span>' +
                    '<span class="fw-bold">' + money(item.sale_price) + ' / ' + escapeHtml(unit_name) + '</span>' +
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

    // A variation's default selling unit is its Sale Unit; when that isn't
    // configured (sale_unit_id null), fall back to its Base (stocking) unit -
    // pv.unit is the base-unit relation (ProductVariation::unit() maps to
    // base_unit_id, see OrderService::searchProducts()/getProductsByCategory()).
    function primaryUnitOf(pv) {
        if (pv.sale_unit_id) {
            return { unit_id: pv.sale_unit_id, name: (pv.sale_unit && pv.sale_unit.name) || 'Sale Unit' };
        }

        if (pv.base_unit_id) {
            return { unit_id: pv.base_unit_id, name: (pv.unit && pv.unit.name) || 'Base Unit' };
        }

        return { unit_id: null, name: 'Unit' };
    }

    // ==============================
    // CART
    // ==============================
    // overrides (optional): { quantity, image } - POS never offers a unit
    // choice (see primaryUnitOf()), so every line always uses the
    // variation's primary (Sale, else Base) unit. Every call site keeps
    // calling this with no quantity override, so the default (qty 1) is
    // what's actually used.
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

        if (existing) {
            existing.quantity = (parseFloat(existing.quantity) || 0) + quantity;
            renderCart();
            return;
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
            unit_price: pv.sale_price || 0,
            discount: 0,
            notes: '',
            image: overrides.image || null,
        });

        renderCart();
    }

    // ==============================
    // CATEGORY BROWSING / PRODUCT GRID
    // ==============================
    function loadProductsByCategory(category_id) {
        state.active_category_id = category_id || '';

        ajaxRequest({
            url: URLS.products_by_category,
            data: { business_id: CFG.business_id, category_id: category_id || '' },
        })
            .then(function (response) {
                renderProductGrid(response.Data || []);
            })
            .catch(function (err) {
                errorMessage(err.Message || 'Unable to load products.');
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

            $card.html(
                '<div class="product-card-img-wrap">' + imgHtml + badgeHtml + '</div>' +
                '<div class="product-card-body">' +
                    '<span class="product-card-add-btn"><i class="fa fa-plus"></i></span>' +
                    '<div class="product-card-name">' + escapeHtml(product.name || '') + '</div>' +
                    '<div class="product-card-sku">' + escapeHtml(firstVariation.sku || '') + '</div>' +
                    '<div class="product-card-footer">' +
                        '<span class="product-card-price">' + money(firstVariation.sale_price) + '</span>' +
                        '<span class="product-card-unit">' + escapeHtml(unitName) + '</span>' +
                    '</div>' +
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
            base_unit_id: variation.base_unit_id,
            unit: variation.unit,
            sale_unit_id: variation.sale_unit_id,
            sale_unit: variation.sale_unit,
            product_variation_unit_conversion: variation.product_variation_unit_conversion,
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
            errorMessage('This product has no sellable variation.');
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

        $('#productPickerTitle').text('Select a variation for ' + (product.name || ''));
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

            var $card = $('<div class="product-card"></div>').data('idx', idx);
            $card.html(
                '<div class="product-card-img-wrap">' + imgHtml + '</div>' +
                '<div class="product-card-body">' +
                    '<div class="product-card-name">' + escapeHtml(pv.name || product.name || '') + '</div>' +
                    (pv.sku ? '<div class="product-card-sku">' + escapeHtml(pv.sku) + '</div>' : '') +
                    '<div class="product-card-footer">' +
                        '<span class="product-card-price">' + money(pv.sale_price) + '</span>' +
                        '<span class="product-card-unit">' + escapeHtml(unitName) + '</span>' +
                    '</div>' +
                '</div>'
            );

            $grid.append($card);
        });
    }

    function renderCart() {
        var $rows = $('#cartRows');
        $rows.empty();

        $('#cartItemCount').text('(' + state.cart.length + ' Item' + (state.cart.length === 1 ? '' : 's') + ')');
        $('#clearCartBtn').toggleClass('d-none', !state.cart.length);
        updateCartOrderBadge();

        var showLineDiscount = SETTING.enable_discount && ['line', 'both'].includes(SETTING.discount_level);

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
            var unitCell = '<span class="text-muted">' + escapeHtml(line.unit_name || '') + '</span>';

            var priceCell = can('order.price.change')
                ? '<input type="number" step="0.01" min="0" class="line-price" value="' + line.unit_price + '">'
                : money(line.unit_price);

            var discountCell = showLineDiscount
                ? '<div class="cart-line-discount">' +
                    '<input type="number" step="0.01" min="0" max="100" class="line-discount" value="' + line.discount + '"><span>%</span></div>'
                : '<div class="cart-line-discount"></div>';

            var imgHtml = line.image
                ? '<img class="cart-line-img" src="' + line.image + '" alt="">'
                : '<div class="cart-line-img-placeholder"><i class="fa fa-image"></i></div>';

            var $row = $('<div class="cart-line"></div>').attr('data-key', line.line_key);
            $row.html(
                imgHtml +
                '<div class="cart-line-info">' +
                    '<div class="cart-line-name">' + escapeHtml(line.product_name) + (line.variation_name ? ' - ' + escapeHtml(line.variation_name) : '') + '</div>' +
                    '<div class="cart-line-meta">' + unitCell + '</div>' +
                '</div>' +
                '<div class="cart-line-price">' + priceCell + '</div>' +
                discountCell +
                '<div class="cart-line-qty-stepper">' +
                    '<button type="button" class="qty-dec">-</button>' +
                    '<input type="number" step="0.01" min="0.01" class="line-qty" value="' + line.quantity + '">' +
                    '<button type="button" class="qty-inc">+</button>' +
                '</div>' +
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
            $badge.removeClass('d-none').text('Order #' + state.order_daily_id);
        } else {
            $badge.addClass('d-none').text('');
        }
    }

    function updateLineFromRow(key, $row) {
        var line = state.cart.find(function (l) { return l.line_key === key; });
        if (!line) return;

        line.quantity = parseFloat($row.find('.line-qty').val()) || 0;

        if (can('order.price.change')) {
            line.unit_price = parseFloat($row.find('.line-price').val()) || 0;
        }

        var $discountInput = $row.find('.line-discount');
        if ($discountInput.length) {
            line.discount = parseFloat($discountInput.val()) || 0;
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

        var orderDiscount = 0;
        var $discountSelect = $('#discount_id');
        if ($discountSelect.length && $discountSelect.val()) {
            // Purely a visual placeholder - eligibility/value is authoritative
            // only from the server response after store().
            orderDiscount = 0;
        }

        var totalDiscount = lineDiscount + orderDiscount;
        var total = subtotal - totalDiscount + tax;

        $('#sumSubtotal').text(money(subtotal));
        $('#sumDiscount').text(money(totalDiscount));
        $('#sumTax').text(money(tax));
        $('#sumTotal').text(money(total));

        recalcPayments(total);
    }

    function updateCreditHint() {
        var $opt = $('#customer_id').find(':selected');
        var limit = parseFloat($opt.data('credit-limit') || 0);

        if (limit > 0) {
            $('#creditLimitHint').removeClass('d-none').text('Credit limit: ' + money(limit));
        } else {
            $('#creditLimitHint').addClass('d-none');
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

    function renderPaymentMethodTiles() {
        var $select = $('#paymentMethodSelect');
        $select.empty().append('<option value="">Payment Method</option>');

        var methods = (CFG.payment_methods || []).filter(function (m) {
            return m.type !== 'credit' || can('order.payment.credit');
        });

        // Visible badge-style selector mirrors the Order Type pills - the
        // hidden <select> above stays the source of truth the rest of the
        // payment logic (selectPaymentTile/activatePaymentUI/etc) reads from.
        var $pills = $('#paymentMethodPills').empty();

        methods.forEach(function (m) {
            $select.append('<option value="' + m.payment_method_id + '">' + escapeHtml(m.name) + '</option>');
            $pills.append('<button type="button" class="pos-pill" data-value="' + m.payment_method_id + '">' + escapeHtml(m.name) + '</button>');
        });

        $select.append('<option value="' + MULTI_PAY_VALUE + '">Multi Pay (Split)</option>');
        $pills.append('<button type="button" class="pos-pill" data-value="' + MULTI_PAY_VALUE + '">Multi Pay</button>');
    }

    // Cash is the default tender for a fresh sale (opening the POS or
    // starting a new order after completing/resetting one) - cashiers pay
    // in cash far more often than any other method, so this saves a tap.
    function selectDefaultPaymentMethod() {
        var methods = (CFG.payment_methods || []).filter(function (m) {
            return m.type !== 'credit' || can('order.payment.credit');
        });
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
    }

    function updateCreditCustomerSummary() {
        var methods = CFG.payment_methods || [];
        var selected = methods.find(function (m) { return m.payment_method_id === state.selected_payment_method_id; });
        var isCredit = !!(selected && selected.type === 'credit');

        $('#creditCustomerSummary').toggleClass('d-none', !isCredit);
        if (!isCredit) return;

        var $opt = $('#customer_id').find(':selected');
        var name = $.trim($opt.text());
        var limit = parseFloat($opt.data('credit-limit') || 0);

        $('#creditCustomerText').text('Customer: ' + name + (limit > 0 ? ' · Credit limit: ' + money(limit) : ''));
    }

    function renderPayments() {
        var $wrap = $('#paymentRows');
        $wrap.empty();

        var methods = (CFG.payment_methods || []).filter(function (m) {
            return m.type !== 'credit' || can('order.payment.credit');
        });

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
                    '<div class="col-4"><input type="number" step="0.01" min="0" class="form-control form-control-sm payment-amount" value="' + payment.amount + '" placeholder="Amount"></div>' +
                    '<div class="col-2 payment-ref-wrap" style="display:' + (showRef ? 'block' : 'none') + '">' +
                        '<input type="text" class="form-control form-control-sm payment-ref" value="' + (payment.reference_no || '') + '" placeholder="Ref #"></div>' +
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
            $('#paymentRemainingLabel').text('Remaining');
            $('#paymentRemaining').text(money(diff));
        } else {
            $('#paymentRemainingLabel').text('Change Due');
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

            if (can('order.price.change')) {
                item.unit_price = line.unit_price;
            }
            if (SETTING.enable_discount && ['line', 'both'].includes(SETTING.discount_level)) {
                item.discount = line.discount;
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
            delivery_address: $('#delivery_address').val(),
            products: products,
        };

        if (state.order_id) {
            payload.order_id = state.order_id;
        }

        if (SETTING.enable_discount) {
            var discount_id = $('#discount_id').val();
            if (discount_id) {
                payload.discount_id = discount_id;
            }
            var voucher_code = $('#voucher_code').val();
            if (voucher_code) {
                payload.voucher_code = voucher_code;
            }
        }

        return payload;
    }

    function renderFromServerOrder(order) {
        $('#sumSubtotal').text(money(order.subtotal));
        $('#sumDiscount').text(money(order.discount_amount));
        $('#sumTax').text(money(order.tax_amount));
        $('#sumTotal').text(money(order.total));
        recalcPayments(parseFloat(order.total) || 0);
    }

    // ==============================
    // HOLD / RESUME
    // ==============================
    function holdOrder() {
        if (!state.session) {
            errorMessage('Open a register session before placing an order.');
            return;
        }

        if (!state.cart.length) {
            errorMessage('Cart is empty.');
            return;
        }

        if (isDeliveryOrderType() && !$('#delivery_address').val().trim()) {
            errorMessage('Delivery address is required for delivery orders.');
            return;
        }

        var payload = buildStorePayload('hold');

        ajaxRequest({ url: URLS.order_store, method: 'POST', data: payload })
            .then(function (response) {
                successMessage('Order held.');
                resetForNewSale();
                loadHeldOrdersCount();
            })
            .catch(function (err) {
                errorMessage(err.Message || 'Unable to hold order.');
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
                    errorMessage('This order belongs to a different business and cannot be reordered here.');
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
                        unit_name: d.unit_name || 'Unit',
                        quantity: d.quantity,
                        unit_price: d.unit_price,
                        discount: d.discount,
                        notes: d.notes || '',
                    });
                });

                $('#delivery_address').val(header.delivery_address || '');

                if (header.customer_id) {
                    $('#customer_id').val(header.customer_id).trigger('change');
                }
                if (header.order_type_id) {
                    $('#order_type_id').val(header.order_type_id).trigger('change');
                }

                // Payments/discount/voucher are intentionally NOT carried
                // over - a reorder is a fresh sale and the server always
                // recomputes totals from scratch on save anyway.
                state.payments = [];
                renderCart();
                resetPaymentSelection();
                selectDefaultPaymentMethod();

                successMessage('Cart loaded from order #' + (header.daily_order_id || '') + ' for reorder.');

                if (window.history && window.history.replaceState) {
                    var url = new URL(window.location.href);
                    url.searchParams.delete('reorder_from');
                    window.history.replaceState(null, '', url.toString());
                }
            })
            .catch(function (err) {
                errorMessage(err.Message || 'Unable to load order for reorder.');
            });
    }

    // ==============================
    // REPORTS (my register sessions - non-transactional)
    // ==============================
    function loadPosReports() {
        var $list = $('#posReportsList');
        $('#posReportsSummary').addClass('d-none');
        $list.html('<div class="text-muted text-center py-3">Loading...</div>');

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
                                '<span>' + escapeHtml(row.register && row.register.name || 'Register') + '</span>' +
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
                $list.html('<div class="text-danger text-center py-3">' + escapeHtml(err.Message || 'Unable to load sessions.') + '</div>');
            });
    }

    function loadPosReportSummary(pos_register_session_id) {
        ajaxRequest({ url: URLS.session_summary + '/' + pos_register_session_id })
            .then(function (response) {
                var s = response.Data || {};
                $('#repOpeningCash').text(money(s.opening_cash));
                $('#repCashSales').text(money(s.cash_sales));
                $('#repCashIn').text(money(s.cash_movements_in));
                $('#repCashOut').text(money(s.cash_movements_out));
                $('#repExpenses').text(money(s.total_expenses));
                $('#repTotalOrders').text(s.total_orders || 0);
                $('#repTotalSales').text(money(s.total_sales_amount));
                $('#repExpectedCash').text(money(s.expected_cash));
                $('#repActualCash').text(s.actual_cash != null ? money(s.actual_cash) : '-');
                $('#posReportsSummary').removeClass('d-none');
            })
            .catch(function (err) {
                errorMessage(err.Message || 'Unable to load session summary.');
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
                    resumeOrder(row.order_id);
                });

                $list.append($item);
            });
        });
    }

    function fetchHeldOrders(callback) {
        ajaxRequest({
            url: URLS.order_data,
            method: 'POST',
            data: {
                draw: 1,
                start: 0,
                length: 50,
                status: 'hold',
                cashier_id: state.session ? state.session.cashier_id : null,
                business_id: CFG.business_id,
            },
        })
            .then(function (response) {
                callback(response.data || response.Data || []);
            })
            .catch(function () {
                callback([]);
            });
    }

    function resumeOrder(order_id) {
        ajaxRequest({ url: URLS.order_details + '/' + order_id })
            .then(function (response) {
                var data = response.Data;
                loadCartFromDetails(data);

                ajaxRequest({ url: URLS.order_resume, method: 'POST', data: { order_id: order_id } })
                    .then(function () {
                        successMessage('Order resumed.');
                        state.held_orders_offcanvas.hide();
                        loadHeldOrdersCount();
                    })
                    .catch(function (err) {
                        errorMessage(err.Message || 'Unable to resume order.');
                    });
            })
            .catch(function (err) {
                errorMessage(err.Message || 'Unable to load order details.');
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
                unit_name: d.unit_name || 'Unit',
                quantity: d.quantity,
                unit_price: d.unit_price,
                discount: d.discount,
                notes: d.notes || '',
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
        if (header.discount_id) {
            $('#discount_id').val(header.discount_id).trigger('change');
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
            resetPaymentSelection();
        }
    }

    // ==============================
    // COMPLETE SALE
    // ==============================
    function completeSale() {
        if (!state.session) {
            errorMessage('Open a register session before placing an order.');
            return;
        }

        if (!state.cart.length) {
            errorMessage('Cart is empty.');
            return;
        }

        if (isDeliveryOrderType() && !$('#delivery_address').val().trim()) {
            errorMessage('Delivery address is required for delivery orders.');
            return;
        }

        var payload = buildStorePayload('draft');

        ajaxRequest({ url: URLS.order_store, method: 'POST', data: payload })
            .then(function (response) {
                var order = response.Data;
                state.order_id = order.order_id;
                renderFromServerOrder(order);

                var total = parseFloat(order.total) || 0;
                var entered = state.payments.reduce(function (sum, p) { return sum + (parseFloat(p.amount) || 0); }, 0);

                if (entered + 0.004 < total) {
                    errorMessage('Payment amount does not cover the total. Please adjust payments.');
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
                        successMessage('Sale completed.');

                        if (SETTING.auto_print_invoice) {
                            window.open(URLS.order_print + '/' + posted.order_id + '/print', '_blank');
                        }

                        resetForNewSale();
                    })
                    .catch(function (err) {
                        errorMessage(err.Message || 'Unable to complete sale.');
                        // Keep the cart intact so the cashier can correct and retry.
                    });
            })
            .catch(function (err) {
                errorMessage(err.Message || 'Unable to save order.');
            });
    }

    // ==============================
    // RESET
    // ==============================
    function resetForNewSale() {
        state.cart = [];
        state.payments = [];
        state.order_id = null;
        state.order_daily_id = null;
        $('#voucher_code').val('');
        $('#discount_id').val('').trigger('change');
        $('#delivery_address').val('');
        renderCart();
        renderPayments();
        selectDefaultPaymentMethod();
    }

    function resetScreenState() {
        resetForNewSale();
        $('#registerBadge').addClass('d-none');
        $('#cashInBtn, #cashOutBtn, #closeRegisterBtn').addClass('d-none');
    }
})();
