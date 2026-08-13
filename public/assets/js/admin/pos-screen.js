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
        cart: [], // {line_key, product_variation_id, product_name, variation_name, unit_id, unit_options, quantity, unit_price, discount, notes}
        payments: [],
        order_id: null,
        line_seq: 0,
        cash_movement_open_modal: null,
        close_session_modal: null,
        open_session_modal: null,
        held_orders_offcanvas: null,
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
        $('.select2').not('#open_pos_register_id').select2();

        // Select2 appends its dropdown to <body> by default, which renders it
        // behind/outside the Bootstrap modal (and outside its focus trap) -
        // clicks land on the modal backdrop instead of the option list. Scope
        // this dropdown to the modal itself so it opens correctly.
        $('#open_pos_register_id').select2({
            dropdownParent: $('#openSessionModal'),
        });

        state.open_session_modal = new bootstrap.Modal(document.getElementById('openSessionModal'));
        state.close_session_modal = new bootstrap.Modal(document.getElementById('closeSessionModal'));
        state.cash_movement_modal = new bootstrap.Modal(document.getElementById('cashMovementModal'));
        state.held_orders_offcanvas = new bootstrap.Offcanvas(document.getElementById('heldOrdersOffcanvas'));
        state.order_history_offcanvas = new bootstrap.Offcanvas(document.getElementById('orderHistoryOffcanvas'));
        state.pos_reports_offcanvas = new bootstrap.Offcanvas(document.getElementById('posReportsOffcanvas'));

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
        $('#cashInBtn, #cashOutBtn, #closeRegisterBtn').addClass('d-none');
        state.open_session_modal.show();
    }

    function onSessionReady() {
        $('#posNoSessionArea').hide();
        $('#posScreenBody').show();

        var registerName = (state.session.register && state.session.register.name) || 'Register';
        var openedAt = state.session.opening_datetime || '';
        $('#registerBadge')
            .removeClass('d-none')
            .text(registerName + ' - opened ' + openedAt);

        $('#cashInBtn, #cashOutBtn, #closeRegisterBtn').removeClass('d-none');

        loadHeldOrdersCount();
    }

    function wireEvents() {
        $('#openSessionSubmitBtn').on('click', submitOpenSession);
        $('#openRegisterFromBrowseBtn').on('click', function () { state.open_session_modal.show(); });
        $('#orderHistoryBtn').on('click', function () {
            loadOrderHistory();
            state.order_history_offcanvas.show();
        });
        $('#orderHistoryStatusFilter').on('change', loadOrderHistory);
        $('#posReportsBtn').on('click', function () {
            loadPosReports();
            state.pos_reports_offcanvas.show();
        });
        $('#cashInBtn').on('click', function () { openCashMovementModal('in'); });
        $('#cashOutBtn').on('click', function () { openCashMovementModal('out'); });
        $('#cashMovementSubmitBtn').on('click', submitCashMovement);
        $('#closeRegisterBtn').on('click', openCloseSessionModal);
        $('#closeSessionSubmitBtn').on('click', submitCloseSession);

        var searchTimer = null;
        $('#productSearchInput').on('input', function () {
            var term = $(this).val().trim();
            clearTimeout(searchTimer);

            if (!term) {
                $('#productSearchResults').hide().empty();
                return;
            }

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
                searchProducts(term, true);
            }
        });

        $(document).on('click', function (e) {
            if (!$(e.target).closest('#productSearchInput, #productSearchResults').length) {
                $('#productSearchResults').hide();
            }
        });

        $('#cartRows').on('input change', '.line-qty, .line-price, .line-discount, .line-unit', function () {
            var key = $(this).closest('tr').data('key');
            updateLineFromRow(key, $(this).closest('tr'));
        });

        $('#cartRows').on('click', '.line-remove', function () {
            var key = $(this).closest('tr').data('key');
            state.cart = state.cart.filter(function (l) { return l.line_key !== key; });
            renderCart();
        });

        $('#customer_id').on('change', updateCreditHint);
        updateCreditHint();

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

    function openCloseSessionModal() {
        ajaxRequest({ url: URLS.session_summary + '/' + state.session.pos_register_session_id })
            .then(function (response) {
                var s = response.Data || {};
                $('#sumOpeningCash').text(money(s.opening_cash));
                $('#sumCashSales').text(money(s.cash_sales));
                $('#sumCashRefunds').text(money(s.cash_refunds));
                $('#sumCashIn').text(money(s.cash_movements_in));
                $('#sumCashOut').text(money(s.cash_movements_out));
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
                        addProductToCart(results[0]);
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
            var unit_name = (item.saleUnit && item.saleUnit.name) || '';

            var $row = $(
                '<a href="javascript:void(0);" class="list-group-item list-group-item-action d-flex justify-content-between align-items-center">' +
                    '<span>' + escapeHtml(product_name) + (variation_name ? ' - ' + escapeHtml(variation_name) : '') +
                        '<small class="text-muted d-block">' + escapeHtml(item.sku || '') + ' ' + escapeHtml(item.barcode || '') + '</small>' +
                    '</span>' +
                    '<span class="fw-bold">' + money(item.sale_price) + ' / ' + escapeHtml(unit_name) + '</span>' +
                '</a>'
            );

            $row.on('click', function () {
                addProductToCart(item);
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
    // CART
    // ==============================
    function addProductToCart(pv) {
        var unit_options = [{
            unit_id: pv.sale_unit_id,
            name: (pv.saleUnit && pv.saleUnit.name) || 'Base Unit',
            product_variation_unit_conversion_id: null,
        }];

        (pv.productVariationUnitConversion || []).forEach(function (conv) {
            unit_options.push({
                unit_id: conv.to_unit_id,
                name: 'Alt unit (x' + conv.conversion_factor + ')',
                product_variation_unit_conversion_id: conv.product_variation_unit_conversion_id,
            });
        });

        // Same variation + same base unit already in cart -> just bump qty.
        var existing = state.cart.find(function (l) {
            return l.product_variation_id === pv.product_variation_id && l.unit_id === pv.sale_unit_id;
        });

        if (existing) {
            existing.quantity = (parseFloat(existing.quantity) || 0) + 1;
            renderCart();
            return;
        }

        state.line_seq += 1;

        state.cart.push({
            line_key: 'line_' + state.line_seq,
            product_variation_id: pv.product_variation_id,
            product_name: (pv.product && pv.product.name) || '',
            variation_name: pv.name || '',
            unit_id: pv.sale_unit_id,
            product_variation_unit_conversion_id: null,
            unit_options: unit_options,
            quantity: 1,
            unit_price: pv.sale_price || 0,
            discount: 0,
            notes: '',
        });

        renderCart();
    }

    function renderCart() {
        var $tbody = $('#cartRows');
        $tbody.empty();

        var showLineDiscount = SETTING.enable_discount && ['line', 'both'].includes(SETTING.discount_level);
        $('.line-discount-col').toggle(!!showLineDiscount);

        if (!state.cart.length) {
            $tbody.append('<tr id="cartEmptyRow"><td colspan="7" class="text-center text-muted py-4">Cart is empty</td></tr>');
            recalcLocal();
            return;
        }

        state.cart.forEach(function (line) {
            var unitOptionsHtml = line.unit_options.map(function (u) {
                return '<option value="' + u.unit_id + '" data-conv="' + (u.product_variation_unit_conversion_id || '') + '"' +
                    (u.unit_id === line.unit_id ? ' selected' : '') + '>' + escapeHtml(u.name) + '</option>';
            }).join('');

            var priceCell = can('order.price.change')
                ? '<input type="number" step="0.01" min="0" class="form-control form-control-sm line-price" value="' + line.unit_price + '">'
                : '<span>' + money(line.unit_price) + '</span>';

            var discountCell = showLineDiscount
                ? '<input type="number" step="0.01" min="0" max="100" class="form-control form-control-sm line-discount" value="' + line.discount + '">'
                : '<span class="text-muted">-</span>';

            var $tr = $('<tr></tr>').attr('data-key', line.line_key);
            $tr.html(
                '<td>' + escapeHtml(line.product_name) + (line.variation_name ? '<br><small class="text-muted">' + escapeHtml(line.variation_name) + '</small>' : '') + '</td>' +
                '<td><select class="form-select form-select-sm line-unit">' + unitOptionsHtml + '</select></td>' +
                '<td><input type="number" step="0.01" min="0.01" class="form-control form-control-sm line-qty" value="' + line.quantity + '"></td>' +
                '<td>' + priceCell + '</td>' +
                '<td>' + discountCell + '</td>' +
                '<td class="line-total fw-bold">0.00</td>' +
                '<td><button type="button" class="btn btn-sm btn-outline-danger line-remove"><i class="fa fa-trash"></i></button></td>'
            );

            $tbody.append($tr);
        });

        recalcLocal();
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

        var $unitSelect = $row.find('.line-unit');
        line.unit_id = $unitSelect.val();
        line.product_variation_unit_conversion_id = $unitSelect.find(':selected').data('conv') || null;

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

        $('#cartRows tr[data-key]').each(function () {
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
    // ORDER HISTORY (non-transactional - viewable with no open register)
    // ==============================
    function loadOrderHistory() {
        var $list = $('#orderHistoryList');
        $list.html('<div class="text-muted text-center py-3">Loading...</div>');

        var data = {
            draw: 1,
            start: 0,
            length: 50,
            business_id: CFG.business_id,
            branch_id: CFG.branch_id,
        };

        var status = $('#orderHistoryStatusFilter').val();
        if (status) {
            data.status = status;
        }

        ajaxRequest({ url: URLS.order_data, method: 'POST', data: data })
            .then(function (response) {
                var rows = response.data || response.Data || [];
                $list.empty();

                if (!rows.length) {
                    $list.append('<div class="text-muted text-center py-3">No orders found</div>');
                    return;
                }

                rows.forEach(function (row) {
                    $list.append(
                        '<div class="list-group-item">' +
                            '<div class="d-flex justify-content-between">' +
                                '<span>#' + escapeHtml(row.daily_order_id) + '</span>' +
                                '<span class="fw-bold">' + money(row.total) + '</span>' +
                            '</div>' +
                            '<small class="text-muted">' + escapeHtml(row.status || '') + '</small>' +
                        '</div>'
                    );
                });
            })
            .catch(function (err) {
                $list.html('<div class="text-danger text-center py-3">' + escapeHtml(err.Message || 'Unable to load order history.') + '</div>');
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
                product_variation_unit_conversion_id: null,
                unit_options: [{ unit_id: d.unit_id, name: d.unit_name || 'Unit', product_variation_unit_conversion_id: null }],
                quantity: d.quantity,
                unit_price: d.unit_price,
                discount: d.discount,
                notes: d.notes || '',
            });
        });

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
        renderPayments();
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
        $('#voucher_code').val('');
        $('#discount_id').val('').trigger('change');
        renderCart();
        renderPayments();
    }

    function resetScreenState() {
        resetForNewSale();
        $('#registerBadge').addClass('d-none');
        $('#cashInBtn, #cashOutBtn, #closeRegisterBtn').addClass('d-none');
    }
})();
