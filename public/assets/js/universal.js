// ======================================================
// UNIVERSAL AJAX CRUD FUNCTIONS
// ======================================================

// ==============================
// TOASTER FUNCTIONS
// ==============================

// Sweet Alert Toast
const Toast = Swal.mixin({
    toast: true,
    position: "top-end",
    showConfirmButton: false,
    timer: 3000,
    timerProgressBar: true,
});

function successMessage(message) {
    Toast.fire({
        icon: "success",
        title: message,
    });
}

function errorMessage(message) {
    Toast.fire({
        icon: "error",
        title: message,
    });
}

function warningMessage(message) {
    Toast.fire({
        icon: "warning",
        title: message,
    });
}

function showLoader() {
    $("#preloader").fadeIn(100);
}

function hideLoader() {
    $("#preloader").fadeOut(100);
}

// ==============================
// UNIVERSAL AJAX REQUEST
// ==============================

function ajaxRequest({
    url,
    method = "GET",
    data = {},
    isFormData = false,
    beforeSend = null,
}) {
    return new Promise((resolve, reject) => {
        $.ajax({
            url: url,
            type: method,
            data: data,
            contentType: isFormData ? false : "application/x-www-form-urlencoded; charset=UTF-8",
            processData: !isFormData,
            cache: false,

            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },

            beforeSend: function () {
                showLoader();

                if (typeof beforeSend === "function") {
                    beforeSend();
                }
            },

            success: function (response) {
                hideLoader();

                if (
                    response.Success ||
                    response.Success ||
                    response.Status
                ) {
                    resolve(response);
                } else {
                    reject(response);
                }
            },

            error: function (xhr) {
                hideLoader();

                let message = "Something went wrong";

                if (xhr.responseJSON?.message) {
                    message = xhr.responseJSON.message;
                }

                reject({
                    Message: message,
                    xhr: xhr,
                });
            },
        });
    });
}

// ==============================
// UNIVERSAL EDIT FUNCTION
// ==============================

function editRecord({
    buttonClass,
    url,
    onSuccess,
}) {
    $("body").off("click", buttonClass).on("click", buttonClass, function (e) {
        e.preventDefault();

        let id = $(this).data("id");

        ajaxRequest({
            url: `${url}/${id}/edit`,
        })
            .then((response) => {
                if (typeof onSuccess === "function") {
                    onSuccess(response);
                }
            })
            .catch((err) => {
                errorMessage(err.Message || "Edit failed");
            });
    });
}

// ==============================
// UNIVERSAL VIEW FUNCTION
// ==============================

function viewRecord({
    buttonClass,
    url,
    onSuccess,
}) {
    $("body").off("click", buttonClass).on("click", buttonClass, function (e) {
        e.preventDefault();

        let id = $(this).data("id");

        ajaxRequest({
            url: `${url}/${id}/show`,
        })
            .then((response) => {
                if (typeof onSuccess === "function") {
                    onSuccess(response);
                }
            })
            .catch((err) => {
                errorMessage(err.Message || "View failed");
            });
    });
}

// ==============================
// UNIVERSAL SAVE / UPDATE
// ==============================

function saveRecord({
    formId,
    url,
    method = "POST",
    tableCallback = null,
    modalId = null,
    beforeSubmit = null,
    onSuccess = null,
}) {

    $(formId).off("submit").on("submit", function (e) {

        e.preventDefault();

        if (typeof beforeSubmit === "function") {
            let valid = beforeSubmit();

            if (!valid) {
                return false;
            }
        }

        let formData = new FormData(this);

        $("#saveBtn").prop("disabled", true);

        ajaxRequest({
            url: url,
            method: method,
            data: formData,
            isFormData: true,
        })
            .then((response) => {

                successMessage(response.Message || "Saved Successfully");

                $(formId).trigger("reset");

                if (modalId) {
                    $(modalId).modal("hide");
                }

                if (typeof tableCallback === "function") {
                    tableCallback();
                }

                if (typeof onSuccess === "function") {
                    onSuccess(response);
                }

                $("#saveBtn").prop("disabled", false);
            })
            .catch((err) => {

                errorMessage(err.Message || "Save failed");

                $("#saveBtn").prop("disabled", false);
            });
    });
}

// ==============================
// UNIVERSAL DELETE FUNCTION
// ==============================

function deleteRecord({
    buttonClass,
    url,
    tableCallback = null,
    text = "You won't be able to revert this!",
}) {
    $("body").off("click", buttonClass).on("click", buttonClass, function () {

        let id = $(this).data("id");

        Swal.fire({
            title: "Are you sure?",
            text: text,
            icon: "warning",
            showCancelButton: true,
            confirmButtonText: "Yes, delete it!",
        }).then((result) => {

            if (result.isConfirmed) {

                ajaxRequest({
                    url: `${url}/${id}`,
                    method: "DELETE",
                })
                    .then((response) => {

                        successMessage(response.Message);

                        if (typeof tableCallback === "function") {
                            tableCallback();
                        }
                    })
                    .catch((err) => {
                        errorMessage(err.Message || "Delete failed");
                    });
            }
        });
    });
}

// ==============================
// UNIVERSAL STATUS UPDATE
// ==============================

function updateStatus({
    buttonClass,
    url,
    tableCallback = null,
}) {

    $("body").off("click", buttonClass).on("click", buttonClass, function () {

        let id = $(this).data("id");
        ajaxRequest({
            url: `${url}/${id}`,
            method: "POST",
            data: {
                _token: $('meta[name="csrf-token"]').attr('content')
            },
        })
            .then((response) => {

                successMessage(response.Message || "Status Updated");

                if (typeof tableCallback === "function") {
                    tableCallback();
                }
            })
            .catch((err) => {
                errorMessage(err.Message || "Status update failed");
            });
    });
}

//number only
function isNumberKey(evt) {
    var charCode = evt.which ? evt.which : evt.keyCode;
    if (charCode != 46 && charCode > 31 && (charCode < 48 || charCode > 57))
        return false;

    return true;
}


//Helper Functions
function decimal(value) {
    value = parseFloat(value || 0);

    return value.toFixed(decimal_points);
}

function currency(amount) {
    amount = decimal(amount);

    return currency_position === 'after'
        ? `${amount} ${currency_symbol}`
        : `${currency_symbol} ${amount}`;
}
$('.currency-label').each(function () {

    $(this).append(' (' + currency_symbol + ')');

});