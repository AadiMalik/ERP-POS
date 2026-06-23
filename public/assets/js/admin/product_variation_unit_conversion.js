$("#createNewProductVariationUnitConversion").click(function () {
    $("#product_variation_unit_conversion_form")[0].reset();
    $("#product_variation_unit_conversion_id").val('');
    $("#business_id").val('').trigger('change.select2');
    $("#product_id").val('').trigger('change.select2');
    $("#product_variation_id").val('').trigger('change.select2');
    $("#from_unit_id").val('').trigger('change.select2');
    $("#to_unit_id").val('').trigger('change.select2');
    $("#saveBtn").show();
    $("#modelHeading").html("Create New Unit Conversion");
    $("#ajaxModel").modal("show");
    enableForm();
});

editRecord({
    buttonClass: "#editProductVariationUnitConversion",
    url: url_local + "/admin/product-variation-unit-conversion",
    onSuccess: function (response) {
        let data = response.Data;
        $("#product_variation_unit_conversion_id").val(data.product_variation_unit_conversion_id);
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
        $("#from_unit_id").val(data.from_unit_id).trigger('change');
        $("#to_unit_id").val(data.to_unit_id).trigger('change');
        $("#conversion_factor").val(data.conversion_factor);
        $("#modelHeading").html("Edit Unit Conversion");
        $("#saveBtn").show();
        enableForm();
        $("#ajaxModel").modal("show");
    }
});

saveRecord({
    formId: "#product_variation_unit_conversion_form",
    url: url_local + "/admin/product-variation-unit-conversion",
    modalId: "#ajaxModel",
    tableCallback: function () {
        initDataTableproduct_variation_unit_conversion_table();
    },
    beforeSubmit: function () {
        if ($("#conversion_factor").val() == "") {
            errorMessage("Please enter conversion factor");
            return false;
        }
        return true;
    }
});

updateStatus({
    buttonClass: ".statusProductVariationUnitConversion",
    url: url_local + "/admin/product-variation-unit-conversion/change-status",
    tableCallback: function () {
        initDataTableproduct_variation_unit_conversion_table();
    }
});


deleteRecord({
    buttonClass: "#deleteProductVariationUnitConversion",
    url: url_local + "/admin/product-variation-unit-conversion",

    tableCallback: function () {
        initDataTableproduct_variation_unit_conversion_table();
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

    $("#product_variation_unit_conversion_form")
        .find("input, select, textarea")
        .prop("disabled", true);

    $("#saveBtn").hide();
}

function enableForm() {

    $("#product_variation_unit_conversion_form")
        .find("input, select, textarea")
        .prop("disabled", false);

    $("#saveBtn").show();
}