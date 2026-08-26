$("#createNewWebsiteFaq").click(function () {
    $("#website_faq_form")[0].reset();
    $("#faq_id").val('');
    $("#business_id").val('').trigger('change.select2');
    $("#saveBtn").show();
    $("#modelHeading").html("Add FAQ");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: "#editWebsiteFaq",
    url: url_local + "/admin/website-faq",
    onSuccess: function (response) {
        let data = response.Data;
        $("#faq_id").val(data.faq_id);
        $("#business_id").val(data.business_id).trigger('change.select2');
        $("#question").val(data.question);
        $("#answer").val(data.answer);
        $("#sort_order").val(data.sort_order);
        $("#modelHeading").html("Edit FAQ");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#website_faq_form",
    url: url_local + "/admin/website-faq",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTablewebsite_faq_table(); },
    beforeSubmit: function () {
        if ($("#question").val() == "") { errorMessage("Please enter a question"); return false; }
        return true;
    }
});

updateStatus({
    buttonClass: ".statusWebsiteFaq",
    url: url_local + "/admin/website-faq/change-status",
    tableCallback: function () { initDataTablewebsite_faq_table(); }
});

deleteRecord({
    buttonClass: "#deleteWebsiteFaq",
    url: url_local + "/admin/website-faq",
    tableCallback: function () { initDataTablewebsite_faq_table(); }
});
