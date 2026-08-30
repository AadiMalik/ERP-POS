$("body").on("click", "#viewIntroInquiry", function () {
    let id = $(this).data("id");
    ajaxRequest({ url: url_local + "/admin/intro/contact-inquiries/" + id }).then(function (response) {
        let d = response.Data;
        $("#intro_contact_inquiry_id").val(d.intro_contact_inquiry_id);
        $("#inq_name").text(d.name);
        $("#inq_email").text(d.email);
        $("#inq_phone").text(d.phone || '-');
        $("#inq_subject").text(d.subject || '-');
        $("#inq_message").text(d.message || '');
        $("#inq_status").val(d.status);
        $("#reply_message").val('');
        $("#reg_business_name").val(d.name || '');
        $("#reg_payment_reference").val('');
        $("#reg_activate").prop('checked', false);

        let html = '';
        (d.replies || []).forEach(function (r) {
            html += '<div class="border rounded p-2 mb-2"><small class="text-muted">' + (r.date_created || '') + ' · ' + (r.send_status || '') + '</small><div>' + (r.reply_message || '') + '</div></div>';
        });
        $("#inq_replies").html(html || '<p class="text-muted mb-0">No replies yet.</p>');

        if (d.business_id) {
            $("#register_business_form").addClass('d-none');
            $("#btnRegisterBusiness").addClass('d-none');
            $("#inq_linked_business").removeClass('d-none').text(
                'Linked business: ' + ((d.business && d.business.name) ? d.business.name : d.business_id) +
                ' · Status: ' + ((d.business && d.business.status) ? d.business.status : '-')
            );
            $("#inq_payment_actions").removeClass('d-none');
            if (d.subscription_invoice_id) {
                $("#btnViewInvoice").attr('href', url_local + '/admin/subscription-invoices/' + d.subscription_invoice_id);
            } else {
                $("#btnViewInvoice").attr('href', '#');
            }
        } else {
            $("#register_business_form").removeClass('d-none');
            $("#btnRegisterBusiness").removeClass('d-none');
            $("#inq_linked_business").addClass('d-none').text('');
            $("#inq_payment_actions").addClass('d-none');
        }

        $("#ajaxModel").modal("show");
        initDataTableintro_inquiries_table();
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});

$("#btnUpdateStatus").click(function () {
    let id = $("#intro_contact_inquiry_id").val();
    ajaxRequest({
        url: url_local + "/admin/intro/contact-inquiries/" + id + "/status",
        method: "POST",
        data: { _token: $('meta[name="csrf-token"]').attr('content'), status: $("#inq_status").val() }
    }).then(function (r) {
        successMessage(r.Message || "Status updated");
        initDataTableintro_inquiries_table();
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});

$("#btnSendReply").click(function () {
    let id = $("#intro_contact_inquiry_id").val();
    let msg = $("#reply_message").val();
    if (!msg) { errorMessage("Reply message is required"); return; }
    ajaxRequest({
        url: url_local + "/admin/intro/contact-inquiries/" + id + "/reply",
        method: "POST",
        data: { _token: $('meta[name="csrf-token"]').attr('content'), reply_message: msg }
    }).then(function (r) {
        successMessage(r.Message || "Reply sent");
        $("#ajaxModel").modal("hide");
        initDataTableintro_inquiries_table();
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});

$("#btnRegisterBusiness").click(function () {
    let id = $("#intro_contact_inquiry_id").val();
    let packageId = $("#reg_package_id").val();
    if (!packageId) { errorMessage("Please select a package"); return; }
    ajaxRequest({
        url: url_local + "/admin/intro/contact-inquiries/" + id + "/register-business",
        method: "POST",
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            package_id: packageId,
            business_name: $("#reg_business_name").val(),
            payment_method: $("#reg_payment_method").val(),
            payment_reference: $("#reg_payment_reference").val(),
            activate: $("#reg_activate").is(':checked') ? 1 : 0
        }
    }).then(function (r) {
        successMessage(r.Message || "Business registered");
        $("#ajaxModel").modal("hide");
        initDataTableintro_inquiries_table();
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});

$("#btnUpdatePayment").click(function () {
    let id = $("#intro_contact_inquiry_id").val();
    ajaxRequest({
        url: url_local + "/admin/intro/contact-inquiries/" + id + "/payment",
        method: "POST",
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            payment_method: $("#reg_payment_method").val(),
            payment_reference: $("#reg_payment_reference").val()
        }
    }).then(function (r) {
        successMessage(r.Message || "Payment updated");
    }).catch(function (err) { errorMessage(err.Message || "Failed"); });
});

$("#btnActivateBusiness").click(function () {
    let id = $("#intro_contact_inquiry_id").val();
    Swal.fire({
        title: 'Confirm payment and activate business?',
        showCancelButton: true,
        confirmButtonText: 'Activate'
    }).then(function (result) {
        if (!result.isConfirmed) return;
        ajaxRequest({
            url: url_local + "/admin/intro/contact-inquiries/" + id + "/activate",
            method: "POST",
            data: { _token: $('meta[name="csrf-token"]').attr('content') }
        }).then(function (r) {
            successMessage(r.Message || "Activated");
            $("#ajaxModel").modal("hide");
            initDataTableintro_inquiries_table();
        }).catch(function (err) { errorMessage(err.Message || "Failed"); });
    });
});

deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/contact-inquiries",
    tableCallback: function () { initDataTableintro_inquiries_table(); }
});
