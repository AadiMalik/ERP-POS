updateStatus({
    buttonClass: ".statusProductReview",
    url: url_local + "/admin/product-review/change-status",
    tableCallback: function () { initDataTableproduct_review_table(); }
});

deleteRecord({
    buttonClass: "#deleteProductReview",
    url: url_local + "/admin/product-review",
    tableCallback: function () { initDataTableproduct_review_table(); }
});
