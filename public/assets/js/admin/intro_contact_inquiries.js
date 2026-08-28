$("body").on("click", "#viewIntroInquiry", function () {
    let id = $(this).data("id");
    ajaxRequest({ url: url_local + "/admin/intro/contact-inquiries/" + id }).then(function (response) {
        let d = response.Data;
        $("#intro_contact_inquiry_id").val(d.intro_contact_inquiry_id);
        $("#inq_name").text(d.name);
        $("#inq_email").text(d.email);
        $("#inq_subject").text(d.subject || '-');
        $("#inq_message").text(d.message || '');
        $("#inq_status").val(d.status);
        $("#reply_message").val('');
        let html = '';
        (d.replies || []).forEach(function (r) {
            html += '<div class="border rounded p-2 mb-2"><small class="text-muted">' + (r.date_created || '') + ' · ' + (r.send_status || '') + '</small><div>' + (r.reply_message || '') + '</div></div>';
        });
        $("#inq_replies").html(html || '<p class="text-muted mb-0">No replies yet.</p>');
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

deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/contact-inquiries",
    tableCallback: function () { initDataTableintro_inquiries_table(); }
});