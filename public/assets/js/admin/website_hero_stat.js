$("#createNewHeroStat").click(function () {
    $("#hero_stat_form")[0].reset();
    $("#hero_stat_id").val('');
    $("#business_id").val('').trigger('change.select2');
    $("#saveBtn").show();
    $("#modelHeading").html("Add Hero Stat");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: "#editHeroStat",
    url: url_local + "/admin/website-hero-stat",
    onSuccess: function (response) {
        let data = response.Data;
        $("#hero_stat_id").val(data.hero_stat_id);
        $("#business_id").val(data.business_id).trigger('change.select2');
        $("#value").val(data.value);
        $("#label").val(data.label);
        $("#icon").val(data.icon);
        $("#icon_color").val(data.icon_color || '#666666');
        $("#sort_order").val(data.sort_order);
        $("#modelHeading").html("Edit Hero Stat");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#hero_stat_form",
    url: url_local + "/admin/website-hero-stat",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTablehero_stat_table(); },
    beforeSubmit: function () {
        if ($("#value").val() == "" || $("#label").val() == "") { errorMessage("Value and Label are required"); return false; }
        return true;
    }
});

updateStatus({
    buttonClass: ".statusHeroStat",
    url: url_local + "/admin/website-hero-stat/change-status",
    tableCallback: function () { initDataTablehero_stat_table(); }
});

deleteRecord({
    buttonClass: "#deleteHeroStat",
    url: url_local + "/admin/website-hero-stat",
    tableCallback: function () { initDataTablehero_stat_table(); }
});
