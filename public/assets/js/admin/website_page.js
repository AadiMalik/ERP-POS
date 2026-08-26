$("#og_image").on("change", function () {
    let file = this.files[0];
    if (file) {
        let reader = new FileReader();
        reader.onload = function (e) { $("#og_image_preview").attr("src", e.target.result).show(); };
        reader.readAsDataURL(file);
    }
});

editRecord({
    buttonClass: "#editWebsitePage",
    url: url_local + "/admin/website-page",
    onSuccess: function (response) {
        let data = response.Data;
        $("#page_id").val(data.page_id);
        $("#title").val(data.title);
        $("#content").val(data.content);
        $("#seo_title").val(data.seo_title);
        $("#seo_description").val(data.seo_description);
        $("#seo_keywords").val(data.seo_keywords);
        if (data.og_image_url) { $("#og_image_preview").attr("src", data.og_image_url).show(); } else { $("#og_image_preview").hide(); }
        $("#modelHeading").html("Edit " + data.title);
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#website_page_form",
    url: url_local + "/admin/website-page",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTablewebsite_page_table(); },
});

updateStatus({
    buttonClass: ".statusWebsitePage",
    url: url_local + "/admin/website-page/change-status",
    tableCallback: function () { initDataTablewebsite_page_table(); }
});
