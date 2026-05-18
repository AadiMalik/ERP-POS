$("#createNewPermission").click(function () {
    $("#saveBtn").val("creating..");
    $("#modelHeading").html("Create New Permission");
    $("#banner").prop("required", true);
    $("#show_website").prop("checked", false);
    $("#show_mobile").prop("checked", false);
    $("#ajaxModel").modal("show");
});