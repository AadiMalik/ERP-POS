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
previewImage('image','image_preview');

$("#createIntroTestimonial").click(function () {
    $("#intro_testimonial_form")[0].reset();
    $("#intro_testimonial_id").val('');
    $("#image_preview").hide();
    $("#saveBtn").show();
    $("#modelHeading").html("Add Testimonial");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: ".editIntroItem",
    url: url_local + "/admin/intro/testimonials",
    onSuccess: function (response) {
        let data = response.Data;
        $("#intro_testimonial_id").val(data.intro_testimonial_id);
        $("#customer_name").val(data.customer_name);
        $("#designation").val(data.designation);
        $("#business_name").val(data.business_name);
        $("#business_type").val(data.business_type);
        $("#review_text").val(data.review_text);
        $("#rating").val(data.rating);
        $("#display_order").val(data.display_order);
        $("#status").val(data.status||'active');
        if(data.image_url){$("#image_preview").attr("src",data.image_url).show();}else{$("#image_preview").hide();}
        $("#modelHeading").html("Edit Testimonial");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#intro_testimonial_form",
    url: url_local + "/admin/intro/testimonials",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTableintro_testimonials_table(); },
    beforeSubmit: function () {
        if(!$("#customer_name").val()||!$("#review_text").val()){errorMessage("Customer name and review are required");return false;}return true;
    }
});

updateStatus({
    buttonClass: ".statusToggle",
    url: url_local + "/admin/intro/testimonials/change-status",
    tableCallback: function () { initDataTableintro_testimonials_table(); }
});

deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/testimonials",
    tableCallback: function () { initDataTableintro_testimonials_table(); }
});