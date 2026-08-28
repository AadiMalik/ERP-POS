function moderateComment(id, status) {
    ajaxRequest({
        url: url_local + "/admin/intro/blog-comments/" + id + "/moderate",
        method: "POST",
        data: { _token: $('meta[name="csrf-token"]').attr('content'), status: status }
    }).then(function (response) {
        successMessage(response.Message || "Updated");
        initDataTableintro_comments_table();
    }).catch(function (err) {
        errorMessage(err.Message || "Failed");
    });
}

$("body").on("click", "#approveComment", function () { moderateComment($(this).data("id"), "approved"); });
$("body").on("click", "#rejectComment", function () { moderateComment($(this).data("id"), "rejected"); });
$("body").on("click", "#spamComment", function () { moderateComment($(this).data("id"), "spam"); });

deleteRecord({
    buttonClass: ".deleteIntroItem",
    url: url_local + "/admin/intro/blog-comments",
    tableCallback: function () { initDataTableintro_comments_table(); }
});