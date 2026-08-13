$("#createNewOrderSource").click(function () {
      $("#pos_order_source_form")[0].reset();
      $("#order_source_id").val('');
      $("#business_id").val('').trigger('change.select2');
      $("#status").val('active');
      $("#saveBtn").show();
      $("#modelHeading").html("Create New Order Source");
      $("#ajaxModel").modal("show");
});

editRecord({
      buttonClass: "#editOrderSource",
      url: url_local + "/admin/order-source",
      onSuccess: function (response) {
            let data = response.Data;
            $("#order_source_id").val(data.order_source_id);
            $("#business_id").val(data.business_id).trigger('change.select2');
            $("#name").val(data.name);
            $("#code").val(data.code);
            $("#sort_order").val(data.sort_order);
            $("#is_default").prop("checked", data.is_default == 1);
            $("#status").val(data.status);
            $("#modelHeading").html("Edit Order Source");
            $("#saveBtn").show();
            $("#ajaxModel").modal("show");
      }
});

saveRecord({
      formId: "#pos_order_source_form",
      url: url_local + "/admin/order-source",
      modalId: "#ajaxModel",
      tableCallback: function () {
            initDataTablepos_order_source_table();
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
            return true;
      }
});

updateStatus({
      buttonClass: ".statusOrderSource",
      url: url_local + "/admin/order-source/change-status",
      tableCallback: function () {
            initDataTablepos_order_source_table();
      }
});


deleteRecord({
      buttonClass: "#deleteOrderSource",
      url: url_local + "/admin/order-source",

      tableCallback: function () {
            initDataTablepos_order_source_table();
      }
});
