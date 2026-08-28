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
previewImage("featured_image", "featured_image_preview");
previewImage("og_image", "og_image_preview");

function toLocalInput(dt) {
    if (!dt) return '';
    let d = new Date(dt);
    if (isNaN(d.getTime())) return String(dt).slice(0, 16);
    let pad = n => String(n).padStart(2, '0');
    return d.getFullYear() + '-' + pad(d.getMonth()+1) + '-' + pad(d.getDate()) + 'T' + pad(d.getHours()) + ':' + pad(d.getMinutes());
}

$("#createIntroBlog").click(function () {
    $("#intro_blog_form")[0].reset();
    $("#intro_blog_id").val('');
    $("#tag_ids").val(null).trigger('change');
    $("#featured_image_preview,#og_image_preview").hide();
    $("#saveBtn").show();
    $("#modelHeading").html("Add Blog Post");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: ".editIntroItem",
    url: url_local + "/admin/intro/blogs",
    onSuccess: function (response) {
        let data = response.Data;
        $("#intro_blog_id").val(data.intro_blog_id);
        $("#title").val(data.title);
        $("#slug").val(data.slug);
        $("#intro_blog_category_id").val(data.intro_blog_category_id);
        $("#status").val(data.status || 'draft');
        $("#is_featured").val(data.is_featured ? 1 : 0);
        $("#excerpt").val(data.excerpt);
        $("#content").val(typeof data.content === 'string' ? data.content : JSON.stringify(data.content || '', null, 2));
        let tags = (data.tags || []).map(t => t.intro_blog_tag_id || t.id);
        $("#tag_ids").val(tags).trigger('change');
        $("#reading_time").val(data.reading_time);
        $("#published_at").val(toLocalInput(data.published_at));
        $("#seo_title").val(data.seo_title);
        $("#meta_description").val(data.meta_description);
        $("#meta_keywords").val(data.meta_keywords);
        $("#canonical_url").val(data.canonical_url);
        $("#og_title").val(data.og_title);
        $("#og_description").val(data.og_description);
        if (data.featured_image_url) { $("#featured_image_preview").attr("src", data.featured_image_url).show(); } else { $("#featured_image_preview").hide(); }
        if (data.og_image_url) { $("#og_image_preview").attr("src", data.og_image_url).show(); } else { $("#og_image_preview").hide(); }
        $("#modelHeading").html("Edit Blog Post");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#intro_blog_form",
    url: url_local + "/admin/intro/blogs",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTableintro_blogs_table(); },
    beforeSubmit: function () {
        if (!$("#title").val()) { errorMessage("Title is required"); return false; }
        return true;
    }
});

deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/blogs",
    tableCallback: function () { initDataTableintro_blogs_table(); }
});