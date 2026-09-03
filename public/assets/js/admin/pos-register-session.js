$("body").off("click", "#voidPosRegisterSession").on("click", "#voidPosRegisterSession", function () {

    let id = $(this).data("id");

    Swal.fire({
        title: "Void this register session?",
        text: "This marks the closed session as voided. This cannot be undone.",
        icon: "warning",
        input: "text",
        inputPlaceholder: "Reason (optional)",
        showCancelButton: true,
        confirmButtonText: "Yes, void it!",
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
                    errorMessage(err.Message || "Void failed");
                });
        }
    });
});
