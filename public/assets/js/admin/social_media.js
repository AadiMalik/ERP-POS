$("#createNewSocialMedia").click(function () {
    $("#social_media_form")[0].reset();
    $("#social_media_link_id").val('');
    $("#business_id").val('').trigger('change.select2');
    $("#saveBtn").show();
    $("#modelHeading").html("Add Social Link");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: "#editSocialMedia",
    url: url_local + "/admin/social-media",
    onSuccess: function (response) {
        let data = response.Data;
        $("#social_media_link_id").val(data.social_media_link_id);
        $("#business_id").val(data.business_id).trigger('change.select2');
        $("#platform").val(data.platform);
        $("#url").val(data.url);
        $("#icon").val(data.icon);
        $("#icon_color").val(data.icon_color || '#666666');
        $("#display_color").val(data.display_color || '#666666');
        $("#sort_order").val(data.sort_order);
        $("#modelHeading").html("Edit Social Link");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#social_media_form",
    url: url_local + "/admin/social-media",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTablesocial_media_table(); },
    beforeSubmit: function () {
        if ($("#platform").val() == "" || $("#url").val() == "") { errorMessage("Platform and URL are required"); return false; }
        return true;
    }
});

updateStatus({
    buttonClass: ".statusSocialMedia",
    url: url_local + "/admin/social-media/change-status",
    tableCallback: function () { initDataTablesocial_media_table(); }
});

deleteRecord({
    buttonClass: "#deleteSocialMedia",
    url: url_local + "/admin/social-media",
    tableCallback: function () { initDataTablesocial_media_table(); }
});
