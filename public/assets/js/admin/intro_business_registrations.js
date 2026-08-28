$("body").on("click", "#viewIntroRegistration", function () {
    let id = $(this).data("id");
    ajaxRequest({ url: url_local + "/admin/intro/business-registrations/" + id }).then(function (response) {
        let d = response.Data;
        $("#intro_business_registration_id").val(d.intro_business_registration_id);
        $("#reg_business_name").text(d.business_name || '-');
        $("#reg_owner_name").text(d.owner_name || '-');
        $("#reg_owner_email").text(d.owner_email || '-');
        $("#reg_owner_phone").text(d.owner_phone || '-');
        $("#reg_business_type").text(d.business_type || '-');
        $("#reg_city").text(d.city || '-');
        $("#reg_package").text((d.package && d.package.name) || '-');
        $("#reg_cycle").text(d.billing_cycle || '-');
        $("#reg_sub_status").text(
            (d.business && (d.business.current_subscription || d.business.currentSubscription) &&
                (d.business.current_subscription || d.business.currentSubscription).status) || '-'
        );
        $("#reg_notes").text(d.notes || '-');
        $("#reg_status").val(d.status || 'pending');
        $("#ajaxModel").modal("show");
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});

$("#btnRegStatus").click(function () {
    let id = $("#intro_business_registration_id").val();
    ajaxRequest({
        url: url_local + "/admin/intro/business-registrations/" + id + "/status",
        method: "POST",
        data: { _token: $('meta[name="csrf-token"]').attr('content'), status: $("#reg_status").val() }
    }).then(function (r) {
        successMessage(r.Message || "Status updated");
        $("#ajaxModel").modal("hide");
        initDataTableintro_registrations_table();
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});