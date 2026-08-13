$("#createNewVoucher").click(function () {
      $("#pos_voucher_form")[0].reset();
      $("#voucher_id").val('');
      $("#business_id").val('').trigger('change.select2');
      $("#type").val('percent');
      $("#status").val('active');
      $("#product_ids").val(null).trigger('change.select2');
      $("#category_ids").val(null).trigger('change.select2');
      $("#customer_ids").val(null).trigger('change.select2');
      $("#order_type_ids").val(null).trigger('change.select2');
      $("#branch_ids").val(null).trigger('change.select2');
      $("#saveBtn").show();
      $("#modelHeading").html("Create New Voucher");
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
            $("#type").val(data.type);
            $("#value").val(data.value);
            $("#valid_from").val(data.valid_from);
            $("#valid_to").val(data.valid_to);
            $("#usage_limit_total").val(data.usage_limit_total);
            $("#usage_limit_per_customer").val(data.usage_limit_per_customer);
            $("#min_order_amount").val(data.min_order_amount);
            $("#status").val(data.status);

            $("#product_ids").val((data.products || []).map(item => item.product_id)).trigger('change.select2');
            $("#category_ids").val((data.categories || []).map(item => item.category_id)).trigger('change.select2');
            $("#customer_ids").val((data.customers || []).map(item => item.customer_id)).trigger('change.select2');
            $("#order_type_ids").val((data.order_types || []).map(item => item.order_type_id)).trigger('change.select2');
            $("#branch_ids").val((data.branches || []).map(item => item.branch_id)).trigger('change.select2');

            $("#modelHeading").html("Edit Voucher");
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
                  errorMessage("Please Enter Name");
                  return false;
            }
            if ($("#code").val() == "") {
                  errorMessage("Please Enter Code");
                  return false;
            }
            if ($("#value").val() == "") {
                  errorMessage("Please Enter Value");
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
