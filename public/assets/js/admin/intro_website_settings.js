$("#intro_settings_form").on("submit", function (e) {
    e.preventDefault();
    let formData = new FormData(this);
    $("#saveBtn").prop("disabled", true);
    ajaxRequest({
        url: url_local + "/admin/intro/website-settings",
        method: "POST",
        data: formData,
        isFormData: true
    }).then(function (response) {
        successMessage(response.Message || "Settings saved");
        $("#saveBtn").prop("disabled", false);
    }).catch(function (err) {
        errorMessage(err.Message || "Save failed");
        $("#saveBtn").prop("disabled", false);
    });
});
