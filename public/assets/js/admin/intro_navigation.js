
$("#createIntroNav").click(function () {
    $("#intro_navigation_form")[0].reset();
    $("#intro_navigation_item_id").val('');
    
    $("#saveBtn").show();
    $("#modelHeading").html("Add Nav Item");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: ".editIntroItem",
    url: url_local + "/admin/intro/navigation",
    onSuccess: function (response) {
        let data = response.Data;
        $("#intro_navigation_item_id").val(data.intro_navigation_item_id);
        $("#label").val(data.label);
        $("#url").val(data.url);
        $("#location").val(data.location||'header');
        $("#section_key").val(data.section_key);
        $("#match_key").val(data.match_key);
        $("#parent_id").val(data.parent_id);
        $("#display_order").val(data.display_order);
        $("#status").val(data.status||'active');
        $("#modelHeading").html("Edit Nav Item");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#intro_navigation_form",
    url: url_local + "/admin/intro/navigation",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTableintro_navigation_table(); },
    beforeSubmit: function () {
        if(!$("#label").val()){errorMessage("Label is required");return false;}return true;
    }
});

updateStatus({
    buttonClass: ".statusToggle",
    url: url_local + "/admin/intro/navigation/change-status",
    tableCallback: function () { initDataTableintro_navigation_table(); }
});

deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/navigation",
    tableCallback: function () { initDataTableintro_navigation_table(); }
});