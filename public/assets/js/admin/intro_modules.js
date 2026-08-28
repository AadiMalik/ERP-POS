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
previewImage('icon','icon_preview');
previewImage('image','image_preview');

$("#createIntroModule").click(function () {
    $("#intro_module_form")[0].reset();
    $("#intro_module_id").val('');
    $("#icon_preview,#image_preview").hide();
    $("#saveBtn").show();
    $("#modelHeading").html("Add Module");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: ".editIntroItem",
    url: url_local + "/admin/intro/modules",
    onSuccess: function (response) {
        let data = response.Data;
        $("#intro_module_id").val(data.intro_module_id);
        $("#name").val(data.name);
        $("#slug").val(data.slug);
        $("#description").val(data.description);
        $("#category").val(data.category);
        $("#display_order").val(data.display_order);
        $("#is_featured").val(data.is_featured?1:0);
        $("#status").val(data.status||'active');
        if(data.icon_url){$("#icon_preview").attr("src",data.icon_url).show();}else{$("#icon_preview").hide();}
        if(data.image_url){$("#image_preview").attr("src",data.image_url).show();}else{$("#image_preview").hide();}
        $("#modelHeading").html("Edit Module");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#intro_module_form",
    url: url_local + "/admin/intro/modules",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTableintro_modules_table(); },
    beforeSubmit: function () {
        if(!$("#name").val()){errorMessage("Name is required");return false;}return true;
    }
});

updateStatus({
    buttonClass: ".statusToggle",
    url: url_local + "/admin/intro/modules/change-status",
    tableCallback: function () { initDataTableintro_modules_table(); }
});

deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/modules",
    tableCallback: function () { initDataTableintro_modules_table(); }
});