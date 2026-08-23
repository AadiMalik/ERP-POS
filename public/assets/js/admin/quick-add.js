// ======================================================
// QUICK-ADD DROPDOWN HELPER
// ======================================================
//
// Generalizes the "open a small create modal from a foreign form, save via
// AJAX, then append + select the new record in a <select>" flow (first built
// bespoke for the POS Add Customer modal) so any master-data dropdown can get
// the same behavior without duplicating the AJAX/option-append logic.
//
// Usage:
//   initQuickAdd({
//       modalId: "#quickAddSupplierModal",
//       formId: "#quickAddSupplierForm",
//       url: url_local + "/admin/supplier",
//       valueField: "supplier_id",
//       labelField: "name",
//       targetSelectIds: ["supplier_id"],
//   });

function initQuickAdd({
    modalId,
    formId,
    url,
    method = "POST",
    valueField,
    labelField,
    targetSelectIds = [],
    extraOptionAttrs = null,
    beforeSubmit = null,
    beforeOpen = null,
    onSuccess = null,
}) {
    let $modal = $(modalId);

    $modal.off("shown.bs.modal.quickAdd").on("shown.bs.modal.quickAdd", function () {
        $(formId)[0].reset();

        if (typeof beforeOpen === "function") {
            beforeOpen();
        }
    });

    $(formId).off("submit").on("submit", function (e) {
        e.preventDefault();

        if (typeof beforeSubmit === "function") {
            let valid = beforeSubmit();

            if (!valid) {
                return false;
            }
        }

        let formData = new FormData(this);
        let $btn = $(this).find('[type="submit"]');

        $btn.prop("disabled", true);

        ajaxRequest({
            url: url,
            method: method,
            data: formData,
            isFormData: true,
        })
            .then((response) => {

                let data = response.Data || {};
                let value = data[valueField];
                let label = typeof labelField === "function" ? labelField(data) : data[labelField];

                targetSelectIds.forEach((selectId) => {
                    let $select = $("#" + selectId);
                    let option = new Option(label, value, true, true);

                    if (typeof extraOptionAttrs === "function") {
                        $.each(extraOptionAttrs(data) || {}, function (attr, val) {
                            option.setAttribute("data-" + attr, val);
                        });
                    }

                    $select.append(option).val(value).trigger("change");
                });

                successMessage(response.Message || "Created successfully");

                $(formId).trigger("reset");
                $modal.modal("hide");

                if (typeof onSuccess === "function") {
                    onSuccess(data);
                }

                $btn.prop("disabled", false);
            })
            .catch((err) => {
                errorMessage(err.Message || "Save failed");
                $btn.prop("disabled", false);
            });
    });
}

// ==============================
// NESTED QUICK-ADD MODAL HELPER
// ==============================
//
// A quick-add modal can itself contain a master-data dropdown that needs its
// own quick-add button (e.g. Sub Category's Category field, Designation's
// Department field). Bootstrap modals don't stack cleanly by default, so the
// parent is hidden while the child is open and restored once the child closes.

function wireNestedQuickAdd(parentModalId, childModalId) {
    let $parent = $(parentModalId);
    let $child = $(childModalId);

    $child.off("show.bs.modal.nestedQuickAdd").on("show.bs.modal.nestedQuickAdd", function () {
        $parent.modal("hide");
    });

    $child.off("hidden.bs.modal.nestedQuickAdd").on("hidden.bs.modal.nestedQuickAdd", function () {
        $parent.modal("show");
    });
}
