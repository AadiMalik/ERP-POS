$("body").off("click", "#voidPosRegisterSession").on("click", "#voidPosRegisterSession", function () {

    let id = $(this).data("id");

    Swal.fire({
        title: (window.i18n_pos && window.i18n_pos.void_session_title) || "Void this register session?",
        text: (window.i18n_pos && window.i18n_pos.void_session_text) || "This marks the closed session as voided. This cannot be undone.",
        icon: "warning",
        input: "text",
        inputPlaceholder: (window.i18n_pos && window.i18n_pos.void_reason_placeholder) || "Reason (optional)",
        showCancelButton: true,
        cancelButtonText: (window.i18n_pos && window.i18n_pos.cancel) || "Cancel",
        confirmButtonText: (window.i18n_pos && window.i18n_pos.yes_void_it) || "Yes, void it!",
    }).then((result) => {

        if (result.isConfirmed) {

            ajaxRequest({
                url: url_local + "/admin/pos-register-session/void",
                method: "POST",
                data: {
                    pos_register_session_id: id,
                    reason: result.value,
                    _token: $('meta[name="csrf-token"]').attr('content')
                },
            })
                .then((response) => {

                    successMessage(response.Message);
                    initDataTablepos_register_session_table();
                })
                .catch((err) => {
                    errorMessage(err.Message || (window.i18n_pos && window.i18n_pos.void_failed) || "Void failed");
                });
        }
    });
});
