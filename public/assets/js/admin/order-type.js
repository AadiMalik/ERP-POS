$("#createNewOrderType").click(function () {
      $("#pos_order_type_form")[0].reset();
      $("#order_type_id").val('');
      $("#status").val('active');
      $("#saveBtn").show();
      $("#modelHeading").html("Create New Order Type");
      $("#ajaxModel").modal("show");
});

editRecord({
      buttonClass: "#editOrderType",
      url: url_local + "/admin/order-type",
      onSuccess: function (response) {
            let data = response.Data;
            $("#order_type_id").val(data.order_type_id);
            $("#name").val(data.name);
            $("#code").val(data.code);
            $("#sort_order").val(data.sort_order);
            $("#is_default").prop("checked", data.is_default == 1);
            $("#status").val(data.status);
            $("#modelHeading").html("Edit Order Type");
            $("#saveBtn").show();
            $("#ajaxModel").modal("show");
      }
});

saveRecord({
      formId: "#pos_order_type_form",
      url: url_local + "/admin/order-type",
      modalId: "#ajaxModel",
      tableCallback: function () {
            initDataTablepos_order_type_table();
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
      buttonClass: ".statusOrderType",
      url: url_local + "/admin/order-type/change-status",
      tableCallback: function () {
            initDataTablepos_order_type_table();
      }
});


deleteRecord({
      buttonClass: "#deleteOrderType",
      url: url_local + "/admin/order-type",

      tableCallback: function () {
            initDataTablepos_order_type_table();
      }
});
