
$("#createIntroBlogCategory").click(function () {
    $("#intro_blog_category_form")[0].reset();
    $("#intro_blog_category_id").val('');
    
    $("#saveBtn").show();
    $("#modelHeading").html("Add Category");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: ".editIntroItem",
    url: url_local + "/admin/intro/blog-categories",
    onSuccess: function (response) {
        let data = response.Data;
        $("#intro_blog_category_id").val(data.intro_blog_category_id);
        $("#name").val(data.name);
        $("#slug").val(data.slug);
        $("#description").val(data.description);
        $("#display_order").val(data.display_order);
        $("#status").val(data.status||'active');
        $("#seo_title").val(data.seo_title);
        $("#meta_description").val(data.meta_description);
        $("#modelHeading").html("Edit Category");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#intro_blog_category_form",
    url: url_local + "/admin/intro/blog-categories",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTableintro_blog_categories_table(); },
    beforeSubmit: function () {
        if(!$("#name").val()){errorMessage("Name is required");return false;}return true;
    }
});

updateStatus({
    buttonClass: ".statusToggle",
    url: url_local + "/admin/intro/blog-categories/change-status",
    tableCallback: function () { initDataTableintro_blog_categories_table(); }
});

deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/blog-categories",
    tableCallback: function () { initDataTableintro_blog_categories_table(); }
});