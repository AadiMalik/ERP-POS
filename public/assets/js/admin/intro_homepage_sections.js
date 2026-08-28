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

$("#createIntroSection").click(function () {
    $("#intro_section_form")[0].reset();
    $("#intro_homepage_section_id").val('');
    $("#image_preview").hide();
    $("#saveBtn").show();
    $("#modelHeading").html("Add Section");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: ".editIntroItem",
    url: url_local + "/admin/intro/homepage-sections",
    onSuccess: function (response) {
        let data = response.Data;
        $("#intro_homepage_section_id").val(data.intro_homepage_section_id);
        $("#section_key").val(data.section_key);
        $("#title").val(data.title);
        $("#subtitle").val(data.subtitle);
        $("#content").val(data.content);
        $("#content_json").val(data.content_json ? (typeof data.content_json==='string'?data.content_json:JSON.stringify(data.content_json,null,2)) : '');
        $("#button_text").val(data.button_text);
        $("#button_link").val(data.button_link);
        $("#display_order").val(data.display_order);
        $("#is_enabled").val(data.is_enabled?1:0);
        $("#status").val(data.status||'active');
        if(data.image_url){$("#image_preview").attr("src",data.image_url).show();}else{$("#image_preview").hide();}
        $("#modelHeading").html("Edit Section");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#intro_section_form",
    url: url_local + "/admin/intro/homepage-sections",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTableintro_sections_table(); },
    beforeSubmit: function () {
        if(!$("#section_key").val()){errorMessage("Section key is required");return false;}return true;
    }
});

updateStatus({
    buttonClass: ".statusToggle",
    url: url_local + "/admin/intro/homepage-sections/change-status",
    tableCallback: function () { initDataTableintro_sections_table(); }
});

deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/homepage-sections",
    tableCallback: function () { initDataTableintro_sections_table(); }
});