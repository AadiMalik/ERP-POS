// Rebuilds one <select>'s <option> list from freshly-fetched rows, keeping
// whatever was already selected (if still present in the new list) so
// switching Business doesn't silently drop a valid prior selection.
function populateVoucherPicker(selectId, items, valueFn, labelFn) {
      let $select = $('#' + selectId);
      let previous = $select.val() || [];
      $select.empty();
      (items || []).forEach(function (item) {
            $select.append($('<option>', { value: valueFn(item), text: labelFn(item) }));
      });
      $select.val(previous.filter(function (v) {
            return $select.find('option[value="' + v + '"]').length > 0;
      })).trigger('change.select2');
}

// A Super Admin has no business of their own, so every scope picker starts
// empty until a specific Business is picked - fetches VoucherController::
// byBusiness() and rebuilds every picker's option list. Returns the ajax
// promise so callers (the change handler below, and editRecord's onSuccess)
// can chain setting the actually-selected values afterward.
function loadVoucherPickers(business_id) {
      return ajaxRequest({
            url: url_local + '/admin/voucher/by-business/' + business_id,
      }).then(function (response) {
            let data = response.Data || {};
            populateVoucherPicker('product_ids', data.products, i => i.product_id, i => i.name);
            populateVoucherPicker('category_ids', data.categories, i => i.category_id, i => i.name);
            populateVoucherPicker('brand_ids', data.brands, i => i.brand_id, i => i.name);
            populateVoucherPicker('variation_ids', data.variations, i => i.product_variation_id, i => (i.product ? i.product.name + ' - ' : '') + i.name);
            populateVoucherPicker('customer_ids', data.customers, i => i.user_id, i => (i.user ? i.user.name : ''));
            populateVoucherPicker('order_type_ids', data.order_types, i => i.order_type_id, i => i.name);
            populateVoucherPicker('branch_ids', data.branches, i => i.branch_id, i => i.name);
            populateVoucherPicker('sale_type_ids', data.sale_types, i => i.sale_type_id, i => i.name);
            populateVoucherPicker('order_source_ids', data.order_sources, i => i.order_source_id, i => i.name);
            populateVoucherPicker('payment_method_ids', data.payment_methods, i => i.payment_method_id, i => i.name);
            populateVoucherPicker('get_product_ids', data.products, i => i.product_id, i => i.name);
            populateVoucherPicker('get_category_ids', data.categories, i => i.category_id, i => i.name);
      }).catch(function (err) {
            errorMessage(err.Message || (window.i18n_vouchers?.unable_load_options || 'Unable to load options for the selected business.'));
      });
}

$(document).on('change', '#business_id', function () {
      let business_id = $(this).val();
      if (business_id) loadVoucherPickers(business_id);
});

function toggleVoucherPromoFields() {
      let promo_type = $("#promo_type").val();
      if (promo_type === 'bogo' || promo_type === 'buy_x_get_y') {
            $(".discount-only-field").hide();
            $(".bogo-only-field").show();
      } else {
            $(".discount-only-field").show();
            $(".bogo-only-field").hide();
      }
}

$(document).on('change', '#promo_type', toggleVoucherPromoFields);

$("#createNewVoucher").click(function () {
      $("#pos_voucher_form")[0].reset();
      $("#voucher_id").val('');
      $("#business_id").val('').trigger('change.select2');
      $("#promo_type").val('discount');
      $("#type").val('percent');
      $("#status").val('active');
      $("#is_exclusive").prop('checked', false);
      $(".days-of-week").prop('checked', false);
      $("#product_ids").val(null).trigger('change.select2');
      $("#category_ids").val(null).trigger('change.select2');
      $("#brand_ids").val(null).trigger('change.select2');
      $("#variation_ids").val(null).trigger('change.select2');
      $("#customer_ids").val(null).trigger('change.select2');
      $("#order_type_ids").val(null).trigger('change.select2');
      $("#branch_ids").val(null).trigger('change.select2');
      $("#sale_type_ids").val(null).trigger('change.select2');
      $("#order_source_ids").val(null).trigger('change.select2');
      $("#payment_method_ids").val(null).trigger('change.select2');
      $("#get_product_ids").val(null).trigger('change.select2');
      $("#get_category_ids").val(null).trigger('change.select2');
      toggleVoucherPromoFields();
      $("#saveBtn").show();
      $("#modelHeading").html(window.i18n_vouchers?.create_new || "Create New Voucher");
      $("#ajaxModel").modal("show");
});

editRecord({
      buttonClass: "#editVoucher",
      url: url_local + "/admin/voucher",
      onSuccess: function (response) {
            let data = response.Data;
            $("#voucher_id").val(data.voucher_id);
            $("#business_id").val(data.business_id).trigger('change.select2');
            $("#name").val(data.name);
            $("#code").val(data.code);
            $("#promo_type").val(data.promo_type || 'discount');
            $("#type").val(data.type);
            $("#value").val(data.value);
            $("#valid_from").val(data.valid_from);
            $("#valid_to").val(data.valid_to);
            $("#time_start").val(data.time_start ? data.time_start.substring(0, 5) : '');
            $("#time_end").val(data.time_end ? data.time_end.substring(0, 5) : '');
            $("#usage_limit_total").val(data.usage_limit_total);
            $("#usage_limit_per_customer").val(data.usage_limit_per_customer);
            $("#min_order_amount").val(data.min_order_amount);
            $("#max_discount_amount").val(data.max_discount_amount);
            $("#is_exclusive").prop('checked', !!Number(data.is_exclusive));
            $("#buy_quantity").val(data.buy_quantity);
            $("#get_quantity").val(data.get_quantity);
            $("#get_discount_percent").val(data.get_discount_percent);
            $("#status").val(data.status);

            $(".days-of-week").prop('checked', false);
            (data.days_of_week ? String(data.days_of_week).split(',') : []).forEach(function (day) {
                  $("#dow_" + day).prop('checked', true);
            });

            function applySelectedScopes() {
                  $("#product_ids").val((data.products || []).map(item => item.product_id)).trigger('change.select2');
                  $("#category_ids").val((data.categories || []).map(item => item.category_id)).trigger('change.select2');
                  $("#brand_ids").val((data.brands || []).map(item => item.brand_id)).trigger('change.select2');
                  $("#variation_ids").val((data.variations || []).map(item => item.product_variation_id)).trigger('change.select2');
                  $("#customer_ids").val((data.users || []).map(item => item.id)).trigger('change.select2');
                  $("#order_type_ids").val((data.order_types || []).map(item => item.order_type_id)).trigger('change.select2');
                  $("#branch_ids").val((data.branches || []).map(item => item.branch_id)).trigger('change.select2');
                  $("#sale_type_ids").val((data.sale_types || []).map(item => item.sale_type_id)).trigger('change.select2');
                  $("#order_source_ids").val((data.order_sources || []).map(item => item.order_source_id)).trigger('change.select2');
                  $("#payment_method_ids").val((data.payment_methods || []).map(item => item.payment_method_id)).trigger('change.select2');
                  $("#get_product_ids").val((data.get_products || []).map(item => item.product_id)).trigger('change.select2');
                  $("#get_category_ids").val((data.get_categories || []).map(item => item.category_id)).trigger('change.select2');
            }

            // A Super Admin's pickers are only populated for whichever business is
            // selected (see loadVoucherPickers()) - for that role, this voucher's
            // own business's option lists must be (re)loaded before the saved
            // selections can be applied. A business-scoped user has no #business_id
            // element at all, and their pickers are already correct from page load.
            if ($("#business_id").length && data.business_id) {
                  loadVoucherPickers(data.business_id).then(applySelectedScopes);
            } else {
                  applySelectedScopes();
            }

            toggleVoucherPromoFields();

            $("#modelHeading").html(window.i18n_vouchers?.edit_heading || "Edit Voucher");
            $("#saveBtn").show();
            $("#ajaxModel").modal("show");
      }
});

saveRecord({
      formId: "#pos_voucher_form",
      url: url_local + "/admin/voucher",
      modalId: "#ajaxModel",
      tableCallback: function () {
            initDataTablepos_voucher_table();
      },
      beforeSubmit: function () {
            if ($("#name").val() == "") {
                  errorMessage(window.i18n_vouchers?.please_enter_name || "Please Enter Name");
                  return false;
            }
            if ($("#code").val() == "") {
                  errorMessage(window.i18n_vouchers?.please_enter_code || "Please Enter Code");
                  return false;
            }
            let promo_type = $("#promo_type").val();
            if (promo_type === 'bogo' || promo_type === 'buy_x_get_y') {
                  if ($("#buy_quantity").val() == "" || $("#get_quantity").val() == "") {
                        errorMessage(window.i18n_vouchers?.please_enter_buy_get_qty || "Please enter Buy Quantity and Get Quantity");
                        return false;
                  }
            } else if ($("#value").val() == "") {
                  errorMessage(window.i18n_vouchers?.please_enter_value || "Please Enter Value");
                  return false;
            }
            return true;
      }
});

updateStatus({
      buttonClass: ".statusVoucher",
      url: url_local + "/admin/voucher/change-status",
      tableCallback: function () {
            initDataTablepos_voucher_table();
      }
});


deleteRecord({
      buttonClass: "#deleteVoucher",
      url: url_local + "/admin/voucher",

      tableCallback: function () {
            initDataTablepos_voucher_table();
      }
});

$(document).on('click', '#viewVoucherHistory', function () {
      var voucherId = $(this).data('id');
      $('#voucherHistoryLoading').show();
      $('#voucherHistoryContent').hide();
      $('#voucherHistoryBody').empty();
      $('#voucherHistoryModal').modal('show');

      ajaxRequest({
            url: url_local + '/admin/voucher/' + voucherId + '/redemptions',
            method: 'GET',
      }).then(function (response) {
            var data = response.Data || {};
            var voucher = data.voucher || {};
            var summary = data.summary || {};
            var history = data.history || [];

            $('#vh_code').text(voucher.code || '-');
            $('#vh_name').text(voucher.name || '');
            $('#vh_rule').text(voucher.rule || '');
            $('#vh_total_uses').text(summary.total_uses ?? 0);
            $('#vh_total_discount').text(typeof money === 'function' ? money(summary.total_discount || 0) : (summary.total_discount || 0));
            $('#vh_unique_customers').text(summary.unique_customers ?? 0);

            if (!history.length) {
                  $('#voucherHistoryBody').append('<tr><td colspan="6" class="text-muted text-center">No usage recorded yet.</td></tr>');
            } else {
                  history.forEach(function (row) {
                        $('#voucherHistoryBody').append(
                              '<tr>' +
                              '<td>' + escapeHtml(row.used_at || '-') + '</td>' +
                              '<td>' + escapeHtml(row.customer || (window.i18n_vouchers?.walk_in || 'Walk-in')) + '</td>' +
                              '<td>' + escapeHtml(row.customer_email || '-') + '</td>' +
                              '<td>' + escapeHtml(row.order_no || '-') + '</td>' +
                              '<td>' + escapeHtml(row.order_status || '-') + '</td>' +
                              '<td>' + (typeof money === 'function' ? money(row.discount_amount || 0) : (row.discount_amount || 0)) + '</td>' +
                              '</tr>'
                        );
                  });
            }

            $('#voucherHistoryLoading').hide();
            $('#voucherHistoryContent').show();
      }).catch(function (err) {
            $('#voucherHistoryLoading').hide();
            errorMessage(err.Message || (window.i18n_vouchers?.unable_load_history || 'Unable to load voucher history.'));
            $('#voucherHistoryModal').modal('hide');
      });
});
