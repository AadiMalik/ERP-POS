function populateBenefitGroupSelect(selected) {
    let $select = $("#group");
    $select.empty();
    $.each(WEBSITE_BENEFIT_GROUPS, function (key, label) {
        $select.append(new Option(label, key, false, key === selected));
    });
}

$("#createNewBenefit").click(function () {
    $("#benefit_form")[0].reset();
    $("#benefit_id").val('');
    $("#business_id").val('').trigger('change.select2');
    populateBenefitGroupSelect($("#filter_group").val() || "why_shop_with_us");
    $("#saveBtn").show();
    $("#modelHeading").html("Add Content Card");
    $("#ajaxModel").modal("show");
});

editRecord({
    buttonClass: "#editBenefit",
    url: url_local + "/admin/website-benefit",
    onSuccess: function (response) {
        let data = response.Data;
        $("#benefit_id").val(data.benefit_id);
        $("#business_id").val(data.business_id).trigger('change.select2');
        populateBenefitGroupSelect(data.group);
        $("#title").val(data.title);
        $("#description").val(data.description);
        $("#value").val(data.value);
        $("#code").val(data.code);
        $("#icon").val(data.icon);
        $("#icon_color").val(data.icon_color || '#666666');
        $("#sort_order").val(data.sort_order);
        $("#modelHeading").html("Edit Content Card");
        $("#saveBtn").show();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#benefit_form",
    url: url_local + "/admin/website-benefit",
    modalId: "#ajaxModel",
    tableCallback: function () { initDataTablebenefit_table(); },
    beforeSubmit: function () {
        if ($("#title").val() == "") { errorMessage("Title is required"); return false; }
        if ($("#group").val() == "") { errorMessage("Group is required"); return false; }
        return true;
    }
});

updateStatus({
    buttonClass: ".statusBenefit",
    url: url_local + "/admin/website-benefit/change-status",
    tableCallback: function () { initDataTablebenefit_table(); }
});

deleteRecord({
    buttonClass: "#deleteBenefit",
    url: url_local + "/admin/website-benefit",
    tableCallback: function () { initDataTablebenefit_table(); }
});
