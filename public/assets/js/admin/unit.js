$("#createNewUnit").click(function () {
      $("#unit_form")[0].reset();
      $("#unit_id").val('');
      $("#name").val('');
      $("#saveBtn").show();
      $("#modelHeading").html(window.i18n_units?.create_title || "Create New Unit");
      $("#ajaxModel").modal("show");
});

editRecord({
      buttonClass: "#editUnit",
      url: url_local + "/admin/unit",
      onSuccess: function (response) {
            let data = response.Data;
            $("#unit_id").val(data.unit_id);
            $("#name").val(data.name);
            $("#modelHeading").html(window.i18n_units?.edit_title || "Edit Unit");
            $("#saveBtn").show();
            $("#ajaxModel").modal("show");
      }
});

saveRecord({
      formId: "#unit_form",
      url: url_local + "/admin/unit",
      modalId: "#ajaxModel",
      tableCallback: function () {
            initDataTableunit_table();
      },
      beforeSubmit: function () {
            if ($("#name").val() == "") {
                  errorMessage(window.i18n_units?.please_enter_name || window.i18n?.please_enter_name || "Please Enter Name");
                  return false;
            }
            return true;
      }
});

updateStatus({
      buttonClass: ".statusUnit",
      url: url_local + "/admin/unit/change-status",
      tableCallback: function () {
            initDataTableunit_table();
      }
});


deleteRecord({
      buttonClass: "#deleteUnit",
      url: url_local + "/admin/unit",

      tableCallback: function () {
            initDataTableunit_table();
      }
});
