viewRecord({
    buttonClass: "#viewContactMessage",
    url: url_local + "/admin/contact-message",
    onSuccess: function (response) {
        let data = response.Data;
        $("#contact_message_id").val(data.contact_message_id);
        $("#view_name").text(data.name);
        $("#view_email").text(data.email);
        $("#view_phone").text(data.phone || '-');
        $("#view_date").text(data.date_created);
        $("#view_subject").text(data.subject || '-');
        $("#view_message").text(data.message);
        $("#reply_message").val('');

        if (data.status === 'replied') {
            $("#view_reply").text(data.reply_message);
            $("#existing_reply_wrap").show();
        } else {
            $("#existing_reply_wrap").hide();
        }

        $("#ajaxModel").modal("show");
        initDataTablecontact_message_table();
    }
});

$("#sendReplyBtn").click(function () {
    let id = $("#contact_message_id").val();
    let reply = $("#reply_message").val();

    if (!reply || !reply.trim()) {
        errorMessage("Please enter a reply message");
        return;
    }

    ajaxRequest({
        url: url_local + "/admin/contact-message/" + id + "/reply",
        method: "POST",
        data: { reply_message: reply, _token: $('meta[name="csrf-token"]').attr('content') },
    }).then(function (response) {
        successMessage(response.Message || "Reply sent");
        $("#ajaxModel").modal("hide");
        initDataTablecontact_message_table();
    }).catch(function (err) {
        errorMessage(err.Message || "Failed to send reply");
    });
});

deleteRecord({
    buttonClass: "#deleteContactMessage",
    url: url_local + "/admin/contact-message",
    tableCallback: function () { initDataTablecontact_message_table(); }
});
