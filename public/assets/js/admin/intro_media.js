$("#createIntroMedia").click(function () {
    $("#intro_media_form")[0].reset();
    $("#collection").val('general');
    $("#saveBtn").show();
    $("#modelHeading").html("Upload Media");
    $("#ajaxModel").modal("show");
});

saveRecord({
    formId: "#intro_media_form",
    url: url_local + "/admin/intro/media",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTableintro_media_table(); },
    beforeSubmit: function () {
        if (!$("#file")[0].files.length) { errorMessage("Select a file"); return false; }
        return true;
    }
});

deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/media",
    tableCallback: function () { initDataTableintro_media_table(); }
});