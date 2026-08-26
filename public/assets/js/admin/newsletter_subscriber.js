updateStatus({
    buttonClass: ".statusNewsletterSubscriber",
    url: url_local + "/admin/newsletter-subscriber/change-status",
    tableCallback: function () { initDataTablenewsletter_subscriber_table(); }
});

deleteRecord({
    buttonClass: "#deleteNewsletterSubscriber",
    url: url_local + "/admin/newsletter-subscriber",
    tableCallback: function () { initDataTablenewsletter_subscriber_table(); }
});
