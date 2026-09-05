function togglePosRegisterAssignedUser() {
    if ($("#mode").val() === "manual") {
        $("#assignedUserWrapper").show();
    } else {
        $("#assignedUserWrapper").hide();
        $("#assigned_user_id").val('').trigger('change.select2');
    }
}

$("#mode").change(function () {
    togglePosRegisterAssignedUser();
});

$("#createNewPosRegister").click(function () {
      $("#pos_register_form")[0].reset();
      $("#pos_register_id").val('');
      $("#business_id").val('').trigger('change.select2');
      $("#branch_id").val('').trigger('change.select2');
      $("#warehouse_id").val('').trigger('change.select2');
      $("#assigned_user_id").val('').trigger('change.select2');
      $("#mode").val('manual');
      $("#status").val('active');
      togglePosRegisterAssignedUser();
      $("#saveBtn").show();
      $("#modelHeading").html((window.i18n_pos && window.i18n_pos.create_register) || "Create New Register");
      $("#ajaxModel").modal("show");
});

editRecord({
      buttonClass: "#editPosRegister",
      url: url_local + "/admin/pos-register",
      onSuccess: function (response) {
            let data = response.Data;
            $("#pos_register_id").val(data.pos_register_id);
            $("#business_id").val(data.business_id).trigger('change.select2');
            $("#name").val(data.name);
            $("#code").val(data.code);
            $("#branch_id").val(data.branch_id).trigger('change.select2');
            $("#warehouse_id").val(data.warehouse_id).trigger('change.select2');
            $("#mode").val(data.mode);
            togglePosRegisterAssignedUser();
            $("#assigned_user_id").val(data.assigned_user_id).trigger('change.select2');
            $("#status").val(data.status);
            $("#modelHeading").html((window.i18n_pos && window.i18n_pos.edit_register) || "Edit Register");
            $("#saveBtn").show();
            $("#ajaxModel").modal("show");
      }
});

saveRecord({
      formId: "#pos_register_form",
      url: url_local + "/admin/pos-register",
      modalId: "#ajaxModel",
      tableCallback: function () {
            initDataTablepos_register_table();
      },
      beforeSubmit: function () {
            const i18n = window.i18n_pos || {};
            if ($("#name").val() == "") {
                  errorMessage(i18n.please_enter_name || "Please Enter Name");
                  return false;
            }
            if ($("#code").val() == "") {
                  errorMessage(i18n.please_enter_code || "Please Enter Code");
                  return false;
            }
            if ($("#branch_id").val() == "") {
                  errorMessage(i18n.please_select_branch || "Please Select Branch");
                  return false;
            }
            if ($("#warehouse_id").val() == "") {
                  errorMessage(i18n.please_select_warehouse || "Please Select Warehouse");
                  return false;
            }
            return true;
      }
});

updateStatus({
      buttonClass: ".statusPosRegister",
      url: url_local + "/admin/pos-register/change-status",
      tableCallback: function () {
            initDataTablepos_register_table();
      }
});


deleteRecord({
      buttonClass: "#deletePosRegister",
      url: url_local + "/admin/pos-register",

      tableCallback: function () {
            initDataTablepos_register_table();
      }
});
