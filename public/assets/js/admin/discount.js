$("#createNewDiscount").click(function () {
      $("#pos_discount_form")[0].reset();
      $("#discount_id").val('');
      $("#business_id").val('').trigger('change.select2');
      $("#type").val('percent');
      $("#status").val('active');
      $("#saveBtn").show();
      $("#modelHeading").html("Create New Discount");
      $("#ajaxModel").modal("show");
});

editRecord({
      buttonClass: "#editDiscount",
      url: url_local + "/admin/discount",
      onSuccess: function (response) {
            let data = response.Data;
            $("#discount_id").val(data.discount_id);
            $("#business_id").val(data.business_id).trigger('change.select2');
            $("#name").val(data.name);
            $("#type").val(data.type);
            $("#value").val(data.value);
            $("#status").val(data.status);

            $("#modelHeading").html("Edit Discount");
            $("#saveBtn").show();
            $("#ajaxModel").modal("show");
      }
});

saveRecord({
      formId: "#pos_discount_form",
      url: url_local + "/admin/discount",
      modalId: "#ajaxModel",
      tableCallback: function () {
            initDataTablepos_discount_table();
      },
      beforeSubmit: function () {
            if ($("#name").val() == "") {
                  errorMessage("Please Enter Name");
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
      buttonClass: ".statusDiscount",
      url: url_local + "/admin/discount/change-status",
      tableCallback: function () {
            initDataTablepos_discount_table();
      }
});


deleteRecord({
      buttonClass: "#deleteDiscount",
      url: url_local + "/admin/discount",

      tableCallback: function () {
            initDataTablepos_discount_table();
      }
});
