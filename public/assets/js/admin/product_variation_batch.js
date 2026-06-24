$("#createNewProductVariationBatch").click(function () {
    $("#product_variation_batch_form")[0].reset();
    $("#product_variation_batch_id").val('');
    $("#business_id").val('').trigger('change.select2');
    $("#product_id").val('').trigger('change.select2');
    $("#product_variation_id").val('').trigger('change.select2');
    $("#warehouse_id").val('').trigger('change.select2');
    $("#avg_price").val('');
    $("#quantity").val('');
    $("#manufacturing_date").val('');
    $("#expiry_date").val('');
    $("#saveBtn").show();
    $("#modelHeading").html("Create New Batch");
    $("#ajaxModel").modal("show");
    enableForm();
});

editRecord({
    buttonClass: "#editProductVariationBatch",
    url: url_local + "/admin/product-variation-batch",
    onSuccess: function (response) {
        let data = response.Data;
        $("#product_variation_batch_id").val(data.product_variation_batch_id);
        $("#business_id").val(data.business_id).trigger('change.select2');;

        // business change trigger mat karo
        loadProducts(data.business_id, function () {

            $("#product_id").val(data.product_id);

            loadProductVariations(data.product_id, function () {

                $("#product_variation_id")
                    .val(data.product_variation_id)
                    .trigger('change');

            });

        });
        $("#warehouse_id").val(data.warehouse_id).trigger('change');
        $("#avg_price").val(data.avg_price);
        $("#quantity").val(data.quantity);
        $("#manufacturing_date").val(data.manufacturing_date);
        $("#expiry_date").val(data.expiry_date);
        $("#modelHeading").html("Edit Batch");
        $("#saveBtn").show();
        enableForm();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#product_variation_batch_form",
    url: url_local + "/admin/product-variation-batch",
    modalId: "#ajaxModel",
    tableCallback: function () {
        initDataTableproduct_variation_batch_table();
    },
    beforeSubmit: function () {
        if ($("#avg_price").val() == "") {
            errorMessage("Please enter average cost");
            return false;
        }
        if ($("#quantity").val() == "") {
            errorMessage("Please enter quantity");
            return false;
        }
        if ($("#manufacturing_date").val() == "") {
            errorMessage("Please enter manufacturing date");
            return false;
        }
        if ($("#expiry_date").val() == "") {
            errorMessage("Please enter expiry date");
            return false;
        }
        return true;
    }
});

updateStatus({
    buttonClass: ".statusProductVariationBatch",
    url: url_local + "/admin/product-variation-batch/change-status",
    tableCallback: function () {
        initDataTableproduct_variation_batch_table();
    }
});


deleteRecord({
    buttonClass: "#deleteProductVariationBatch",
    url: url_local + "/admin/product-variation-batch",

    tableCallback: function () {
        initDataTableproduct_variation_batch_table();
    }
});

function loadProducts(business_id, callback) {

    ajaxRequest({
        url: url_local + '/admin/product/by-business/' + business_id,
        data: {}
    })
        .then((response) => {
            let data = response.Data;
            let options = '<option value="">--Select Product--</option>';
            $.each(data, function (index, item) {
                options += `<option value="${item.product_id}">
                                    ${item.name}
                                </option>
                                `;
            });
            $('#product_id').html(options);
            if (callback) callback();
        })
        .catch((err) => {
            errorMessage(err.Message);
        });

}

function loadProductVariations(product_id, callback) {

    ajaxRequest({
        url: url_local + '/admin/product/variation-by-product/' + product_id,
        data: {}
    })
        .then((response) => {
            let data = response.Data;
            let options = '<option value="">--Select Variation--</option>';
            $.each(data, function (index, item) {
                options += `<option value="${item.product_variation_id}">
                                    ${item.name}
                                </option>
                                `;
            });
            $('#product_variation_id').html(options);
            if (callback) callback();
        })
        .catch((err) => {
            errorMessage(err.Message);
        });

}

function disableForm() {

    $("#product_variation_batch_form")
        .find("input, select, textarea")
        .prop("disabled", true);

    $("#saveBtn").hide();
}

function enableForm() {

    $("#product_variation_batch_form")
        .find("input, select, textarea")
        .prop("disabled", false);

    $("#saveBtn").show();
}