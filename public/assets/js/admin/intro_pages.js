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
previewImage('og_image','og_image_preview');

$("#createIntroPage").click(function () {
    $("#intro_page_form")[0].reset();
    $("#intro_page_id").val('');
    $("#og_image_preview").hide();
    $("#saveBtn").show();
    $("#modelHeading").html("Add Page");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: ".editIntroItem",
    url: url_local + "/admin/intro/pages",
    onSuccess: function (response) {
        let data = response.Data;
        $("#intro_page_id").val(data.intro_page_id);
        $("#title").val(data.title);
        $("#slug").val(data.slug);
        $("#content").val(data.content);
        $("#status").val(data.status||'published');
        $("#seo_title").val(data.seo_title);
        $("#meta_description").val(data.meta_description);
        $("#meta_keywords").val(data.meta_keywords);
        $("#canonical_url").val(data.canonical_url);
        $("#og_title").val(data.og_title);
        $("#og_description").val(data.og_description);
        $("#robots_index").val(data.robots_index?1:0);
        $("#robots_follow").val(data.robots_follow?1:0);
        if(data.og_image_url){$("#og_image_preview").attr("src",data.og_image_url).show();}else{$("#og_image_preview").hide();}
        $("#modelHeading").html("Edit Page");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#intro_page_form",
    url: url_local + "/admin/intro/pages",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTableintro_pages_table(); },
    beforeSubmit: function () {
        if(!$("#title").val()){errorMessage("Title is required");return false;}return true;
    }
});


deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/pages",
    tableCallback: function () { initDataTableintro_pages_table(); }
});