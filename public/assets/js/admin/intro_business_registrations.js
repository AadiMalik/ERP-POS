function fillRegistrationPayment(d) {
    let payment = d.payment || null;
    let invoice = d.invoice || null;

    if (!payment) {
        $("#reg_payment_box").addClass("d-none");
        $("#reg_payment_empty").removeClass("d-none");
        return;
    }

    $("#reg_payment_empty").addClass("d-none");
    $("#reg_payment_box").removeClass("d-none");
    $("#reg_invoice_no").text((invoice && invoice.invoice_no) ? invoice.invoice_no : "-");
    $("#reg_payment_amount").text(payment.amount != null ? payment.amount : "-");
    let method = (payment.payment_method || "-").replace(/_/g, " ");
    $("#reg_payment_method").text(method);
    let isBank = payment.payment_method === "bank_transfer";
    $("#reg_payment_reference_label").text(isBank ? "Bank Reference No" : "Reference");
    $("#reg_payment_proof_label").text(isBank ? "Bank Receipt" : "Receipt / Proof");
    $("#reg_payment_reference").text(payment.payment_reference || "—");
    $("#reg_payment_status").text(payment.status || "-");

    if (payment.payment_proof) {
        let url = url_local + "/public/uploads/subscription_payments/" + payment.payment_proof;
        $("#reg_payment_proof_missing").addClass("d-none");
        $("#reg_payment_proof").removeClass("d-none").attr("href", url);
        let isImage = /\.(jpe?g|png|gif|webp)$/i.test(payment.payment_proof);
        if (isImage) {
            $("#reg_payment_proof_preview")
                .removeClass("d-none")
                .html('<a href="' + url + '" target="_blank"><img src="' + url + '" alt="Receipt" class="img-fluid border rounded" style="max-height:200px;"></a>');
        } else {
            $("#reg_payment_proof_preview").addClass("d-none").empty();
        }
    } else {
        $("#reg_payment_proof_missing").removeClass("d-none");
        $("#reg_payment_proof").addClass("d-none").attr("href", "#");
        $("#reg_payment_proof_preview").addClass("d-none").empty();
    }

    if (invoice && invoice.subscription_invoice_id) {
        $("#reg_open_invoice").attr("href", url_local + "/admin/subscription-invoices/" + invoice.subscription_invoice_id).removeClass("d-none");
    } else {
        $("#reg_open_invoice").addClass("d-none");
    }

    if (payment.status === "pending") {
        $("#reg_payment_actions").removeClass("d-none");
        $("#btnApprovePayment, #btnRejectPayment").removeClass("d-none");
        $("#reg_payment_locked").addClass("d-none").text("");
    } else {
        $("#btnApprovePayment, #btnRejectPayment").addClass("d-none");
        $("#reg_payment_actions").removeClass("d-none");
        let msg = payment.status === "confirmed"
            ? "Confirmed — cannot reject."
            : "Rejected — cannot confirm.";
        $("#reg_payment_locked").removeClass("d-none").text(msg);
    }
}

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
        fillRegistrationPayment(d);
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

$("#btnApprovePayment").click(function () {
    let id = $("#intro_business_registration_id").val();
    Swal.fire({
        title: "Confirm this payment?",
        text: "Business will be activated and an invoice email will be sent.",
        showCancelButton: true,
        confirmButtonText: "Confirm Payment"
    }).then(function (result) {
        if (!result.isConfirmed) return;
        ajaxRequest({
            url: url_local + "/admin/intro/business-registrations/" + id + "/approve-payment",
            method: "POST",
            data: { _token: $('meta[name="csrf-token"]').attr('content') }
        }).then(function (r) {
            successMessage(r.Message || "Payment confirmed");
            fillRegistrationPayment(r.Data || {});
            $("#reg_status").val((r.Data && r.Data.status) || "activated");
            initDataTableintro_registrations_table();
        }).catch(function (err) { errorMessage(err.Message || "Failed"); });
    });
});

$("#btnRejectPayment").click(function () {
    let id = $("#intro_business_registration_id").val();
    Swal.fire({
        title: "Reject this payment?",
        input: "text",
        inputPlaceholder: "Reason",
        showCancelButton: true,
        confirmButtonText: "Reject"
    }).then(function (result) {
        if (!result.isConfirmed) return;
        ajaxRequest({
            url: url_local + "/admin/intro/business-registrations/" + id + "/reject-payment",
            method: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr('content'),
                reason: result.value || "Rejected by Super Admin"
            }
        }).then(function (r) {
            successMessage(r.Message || "Payment rejected");
            fillRegistrationPayment(r.Data || {});
            $("#reg_status").val((r.Data && r.Data.status) || "rejected");
            initDataTableintro_registrations_table();
        }).catch(function (err) { errorMessage(err.Message || "Failed"); });
    });
});
