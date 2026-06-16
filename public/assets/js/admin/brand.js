$("#logo").on("change", function () {
      let file = this.files[0];
      if (file) {
            let reader = new FileReader();
            reader.onload = function (e) {
                  $("#logo_preview")
                        .attr("src", e.target.result)
                        .show();
            };
            reader.readAsDataURL(file);
      }
});

$("#createNewBrand").click(function () {
      $("#brand_form")[0].reset();
      $("#brand_id").val('');
      $("#business_id").val('').trigger('change.select2');
      $("#logo_preview").hide();
      $("#status").prop("checked", true);
      $("#logo").prop("required", true);
      $("#saveBtn").show();
      $("#modelHeading").html("Create New Brand");
      $("#ajaxModel").modal("show");
      enableForm();
});

editRecord({
      buttonClass: "#editBrand",
      url: url_local + "/admin/brands",
      onSuccess: function (response) {
            let data = response.Data;
            $("#brand_id").val(data.brand_id);
            $("#business_id").val(data.business_id).trigger('change.select2');
            $("#name").val(data.name);
            if (data.logo_url) {
                  $("#logo_preview")
                        .attr("src", data.logo_url)
                        .show();
            } else {
                  $("#logo_preview").hide();
            }
            $("#logo").prop("required", false);
            $("#modelHeading").html("Edit Brand");
            $("#saveBtn").show();
            enableForm();
            $("#ajaxModel").modal("show");
      }
});

viewRecord({
      buttonClass: "#viewBrand",
      url: url_local + "/admin/brands",

      onSuccess: function (response) {

            let data = response.Data;

            $("#brand_id").val(data.brand_id);
            $("#business_id").val(data.business_id);
            $("#name").val(data.name);
            if (data.logo_url) {
                  $("#logo_preview")
                        .attr("src", data.logo_url)
                        .show();
            } else {
                  $("#logo_preview").hide();
            }
            $("#modelHeading").html("View Brand");
            disableForm();
            $("#saveBtn").hide();
            $("#ajaxModel").modal("show");
      }
});

saveRecord({
      formId: "#brand_form",
      url: url_local + "/admin/brands",
      modalId: "#ajaxModel",
      tableCallback: function () {
            initDataTablebrand_table();
      },
      beforeSubmit: function () {
            if ($("#name").val() == "") {
                  errorMessage("Please Enter Brand Name");
                  return false;
            }
            return true;
      }
});

updateStatus({
      buttonClass: ".statusBrand",
      url: url_local + "/admin/brands/change-status",
      tableCallback: function () {
            initDataTablebrand_table();
      }
});


deleteRecord({
      buttonClass: "#deleteBrand",
      url: url_local + "/admin/brands",

      tableCallback: function () {
            initDataTablebrand_table();
      }
});

function disableForm() {

      $("#brand_form")
            .find("input, select, textarea")
            .prop("disabled", true);

      $("#saveBtn").hide();
}

function enableForm() {

      $("#brand_form")
            .find("input, select, textarea")
            .prop("disabled", false);

      $("#saveBtn").show();
}