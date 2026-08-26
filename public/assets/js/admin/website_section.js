function previewImage(inputId, previewId) {
    $("#" + inputId).on("change", function () {
        let file = this.files[0];
        if (file) {
            let reader = new FileReader();
            reader.onload = function (e) {
                $("#" + previewId).attr("src", e.target.result).show();
            };
            reader.readAsDataURL(file);
        }
    });
}
previewImage("image", "image_preview");
previewImage("image_mobile", "image_mobile_preview");

$("#createNewWebsiteSection").click(function () {
    $("#website_section_form")[0].reset();
    $("#section_id").val('');
    $("#business_id").val('').trigger('change.select2');
    $("#image_preview,#image_mobile_preview").hide();
    $("#saveBtn").show();
    $("#modelHeading").html("Add Section");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: "#editWebsiteSection",
    url: url_local + "/admin/website-section",
    onSuccess: function (response) {
        let data = response.Data;
        $("#section_id").val(data.section_id);
        $("#business_id").val(data.business_id).trigger('change.select2');
        $("#type").val(data.type);
        $("#tagline").val(data.tagline);
        $("#tagline_icon").val(data.tagline_icon);
        $("#heading").val(data.heading);
        $("#heading_icon").val(data.heading_icon);
        $("#description").val(data.description);
        $("#button_text").val(data.button_text);
        $("#button_link").val(data.button_link);
        $("#link_type").val(data.link_type);
        $("#link_target_id").val(data.link_target_id);
        $("#secondary_button_text").val(data.secondary_button_text);
        $("#secondary_button_link").val(data.secondary_button_link);
        $("#secondary_link_type").val(data.secondary_link_type);
        $("#secondary_link_target_id").val(data.secondary_link_target_id);
        $("#countdown_end_at").val(data.countdown_end_at ? data.countdown_end_at.replace(' ', 'T').substring(0, 16) : '');
        $("#sort_order").val(data.sort_order);
        if (data.image_url) { $("#image_preview").attr("src", data.image_url).show(); } else { $("#image_preview").hide(); }
        if (data.image_mobile_url) { $("#image_mobile_preview").attr("src", data.image_mobile_url).show(); } else { $("#image_mobile_preview").hide(); }
        $("#modelHeading").html("Edit Section");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#website_section_form",
    url: url_local + "/admin/website-section",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTablewebsite_section_table(); },
    beforeSubmit: function () {
        if ($("#type").val() == "") { errorMessage("Please select a section type"); return false; }
        return true;
    }
});

updateStatus({
    buttonClass: ".statusWebsiteSection",
    url: url_local + "/admin/website-section/change-status",
    tableCallback: function () { initDataTablewebsite_section_table(); }
});

deleteRecord({
    buttonClass: "#deleteWebsiteSection",
    url: url_local + "/admin/website-section",
    tableCallback: function () { initDataTablewebsite_section_table(); }
});
