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
previewImage("avatar", "avatar_preview");

$("#createNewTestimonial").click(function () {
    $("#testimonial_form")[0].reset();
    $("#testimonial_id").val('');
    $("#business_id").val('').trigger('change.select2');
    $("#avatar_preview").hide();
    $("#saveBtn").show();
    $("#modelHeading").html("Add Testimonial");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: "#editTestimonial",
    url: url_local + "/admin/website-testimonial",
    onSuccess: function (response) {
        let data = response.Data;
        $("#testimonial_id").val(data.testimonial_id);
        $("#business_id").val(data.business_id).trigger('change.select2');
        $("#author_name").val(data.author_name);
        $("#author_title").val(data.author_title);
        $("#quote").val(data.quote);
        $("#rating").val(data.rating);
        $("#sort_order").val(data.sort_order);
        if (data.avatar_url) { $("#avatar_preview").attr("src", data.avatar_url).show(); } else { $("#avatar_preview").hide(); }
        $("#modelHeading").html("Edit Testimonial");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#testimonial_form",
    url: url_local + "/admin/website-testimonial",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTabletestimonial_table(); },
    beforeSubmit: function () {
        if ($("#author_name").val() == "" || $("#quote").val() == "") { errorMessage("Author name and quote are required"); return false; }
        return true;
    }
});

updateStatus({
    buttonClass: ".statusTestimonial",
    url: url_local + "/admin/website-testimonial/change-status",
    tableCallback: function () { initDataTabletestimonial_table(); }
});

deleteRecord({
    buttonClass: "#deleteTestimonial",
    url: url_local + "/admin/website-testimonial",
    tableCallback: function () { initDataTabletestimonial_table(); }
});
