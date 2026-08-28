
$("#createIntroBlogTag").click(function () {
    $("#intro_blog_tag_form")[0].reset();
    $("#intro_blog_tag_id").val('');
    
    $("#saveBtn").show();
    $("#modelHeading").html("Add Tag");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: ".editIntroItem",
    url: url_local + "/admin/intro/blog-tags",
    onSuccess: function (response) {
        let data = response.Data;
        $("#intro_blog_tag_id").val(data.intro_blog_tag_id);
        $("#name").val(data.name);
        $("#slug").val(data.slug);
        $("#status").val(data.status||'active');
        $("#modelHeading").html("Edit Tag");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#intro_blog_tag_form",
    url: url_local + "/admin/intro/blog-tags",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTableintro_blog_tags_table(); },
    beforeSubmit: function () {
        if(!$("#name").val()){errorMessage("Name is required");return false;}return true;
    }
});

updateStatus({
    buttonClass: ".statusToggle",
    url: url_local + "/admin/intro/blog-tags/change-status",
    tableCallback: function () { initDataTableintro_blog_tags_table(); }
});

deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/blog-tags",
    tableCallback: function () { initDataTableintro_blog_tags_table(); }
});