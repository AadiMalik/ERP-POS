$("#createNewJournal").click(function () {
    $("#journal_form")[0].reset();
    $("#journal_id").val('');
    $("#name").val('');
    $("#short").val('');
    $("#saveBtn").show();
    $("#modelHeading").html("Create New journal");
    $("#ajaxModel").modal("show");
    enableForm();
});

editRecord({
    buttonClass: "#editJournal",
    url: url_local + "/admin/journal",
    onSuccess: function (response) {
        let data = response.Data;
        $("#journal_id").val(data.journal_id);
        $("#name").val(data.name);
        $("#short").val(data.short);
        $("#logo").prop("required", false);
        $("#modelHeading").html("Edit Journal");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#journal_form",
    url: url_local + "/admin/journal",
    modalId: "#ajaxModel",
    tableCallback: function () {
        initDataTablejournal_table();
    },
    beforeSubmit: function () {
        if ($("#name").val() == "") {
            errorMessage("Please Enter Name");
            return false;
        }
        if ($("#short").val() == "") {
            errorMessage("Please Enter Short");
            return false;
        }
        return true;
    }
});


deleteRecord({
    buttonClass: "#deleteJournal",
    url: url_local + "/admin/journal",

    tableCallback: function () {
        initDataTablejournal_table();
    }
});

function disableForm() {

    $("#journal_form")
        .find("input, select, textarea")
        .prop("disabled", true);

    $("#saveBtn").hide();
}

function enableForm() {

    $("#journal_form")
        .find("input, select, textarea")
        .prop("disabled", false);

    $("#saveBtn").show();
}