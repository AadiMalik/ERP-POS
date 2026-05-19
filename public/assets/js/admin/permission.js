$("#createNewPermission").click(function () {
    $("#saveBtn").val("creating..");
    $("#modelHeading").html("Create New Permission");
    $("#banner").prop("required", true);
    $("#show_website").prop("checked", false);
    $("#show_mobile").prop("checked", false);
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: "#editPermission",
    url: url_local + "/admin/permissions",

    onSuccess: function (data) {
        var data = data.Data;
        $("#id").val(data.id);
        $("#name").val(data.name);
        if (data.is_system_only == 1) {
            $("#toggleSwitch2").prop("checked", true);
        } else {
            $("#toggleSwitch2").prop("checked", false);
        }

        $("#modelHeading").html("Edit ermission");
        $("#ajaxModel").modal("show");
    }
});

//save
saveRecord({
    formId: "#permission_form",
    url: url_local + "/admin/permissions",
    modalId: "#ajaxModel",

    tableCallback: function () {
        initDataTablepermission_table();
    },

    beforeSubmit: function () {

        if ($("#name").val() == "") {

            errorMessage("Please Enter Permission Name");
            return false;
        }

        return true;
    }
});

//status
updateStatus({
    buttonClass: ".changeStatus",
    url: url_local + "/status-banner",

    tableCallback: function () {
        initDataTablepermission_table();
    }
});

//delete
deleteRecord({
    buttonClass: "#deletePermission",
    url: url_local + "/admin/permissions",

    tableCallback: function () {
        initDataTablepermission_table();
    }
});