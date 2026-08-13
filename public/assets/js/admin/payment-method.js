function toggleAccountRequired() {
      if ($("#type").val() === "credit") {
            $("#account_id").prop("required", false);
            $("#account_id_wrapper").find("span.text-danger").hide();
      } else {
            $("#account_id").prop("required", true);
            $("#account_id_wrapper").find("span.text-danger").show();
      }
}

$("#type").on("change", toggleAccountRequired);

$("#createNewPaymentMethod").click(function () {
      $("#pos_payment_method_form")[0].reset();
      $("#payment_method_id").val('');
      $("#business_id").val('').trigger('change.select2');
      $("#account_id").val('').trigger('change.select2');
      $("#type").val('cash');
      $("#status").val('active');
      toggleAccountRequired();
      $("#saveBtn").show();
      $("#modelHeading").html("Create New Payment Method");
      $("#ajaxModel").modal("show");
});

editRecord({
      buttonClass: "#editPaymentMethod",
      url: url_local + "/admin/payment-method",
      onSuccess: function (response) {
            let data = response.Data;
            $("#payment_method_id").val(data.payment_method_id);
            $("#business_id").val(data.business_id).trigger('change.select2');
            $("#name").val(data.name);
            $("#code").val(data.code);
            $("#type").val(data.type);
            $("#account_id").val(data.account_id).trigger('change.select2');
            $("#sort_order").val(data.sort_order);
            $("#is_default").prop("checked", data.is_default == 1);
            $("#status").val(data.status);
            toggleAccountRequired();
            $("#modelHeading").html("Edit Payment Method");
            $("#saveBtn").show();
            $("#ajaxModel").modal("show");
      }
});

saveRecord({
      formId: "#pos_payment_method_form",
      url: url_local + "/admin/payment-method",
      modalId: "#ajaxModel",
      tableCallback: function () {
            initDataTablepos_payment_method_table();
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
            if ($("#type").val() !== "credit" && $("#account_id").val() == "") {
                  errorMessage("Please Select Account");
                  return false;
            }
            return true;
      }
});

updateStatus({
      buttonClass: ".statusPaymentMethod",
      url: url_local + "/admin/payment-method/change-status",
      tableCallback: function () {
            initDataTablepos_payment_method_table();
      }
});


deleteRecord({
      buttonClass: "#deletePaymentMethod",
      url: url_local + "/admin/payment-method",

      tableCallback: function () {
            initDataTablepos_payment_method_table();
      }
});
