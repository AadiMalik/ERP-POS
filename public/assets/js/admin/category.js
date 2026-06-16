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

$("#createNewCategory").click(function () {
      $("#category_form")[0].reset();
      $("#category_id").val('');
      $("#business_id").val('').trigger('change.select2');
      $("#logo_preview").hide();
      $("#status").prop("checked", true);
      $("#logo").prop("required", false);
      $("#saveBtn").show();
      $("#modelHeading").html("Create New Category");
      $("#ajaxModel").modal("show");
      enableForm();
});

editRecord({
      buttonClass: "#editCategory",
      url: url_local + "/admin/category",
      onSuccess: function (response) {
            let data = response.Data;
            $("#category_id").val(data.category_id);
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
            $("#modelHeading").html("Edit Category");
            $("#saveBtn").show();
            enableForm();
            $("#ajaxModel").modal("show");
      }
});

viewRecord({
      buttonClass: "#viewCategory",
      url: url_local + "/admin/category",

      onSuccess: function (response) {

            let data = response.Data;

            $("#category_id").val(data.category_id);
            $("#business_id").val(data.business_id);
            $("#name").val(data.name);
            if (data.logo_url) {
                  $("#logo_preview")
                        .attr("src", data.logo_url)
                        .show();
            } else {
                  $("#logo_preview").hide();
            }
            $("#modelHeading").html("View Category");
            disableForm();
            $("#saveBtn").hide();
            $("#ajaxModel").modal("show");
      }
});

saveRecord({
      formId: "#category_form",
      url: url_local + "/admin/category",
      modalId: "#ajaxModel",
      tableCallback: function () {
            initDataTablecategory_table();
      },
      beforeSubmit: function () {
            if ($("#name").val() == "") {
                  errorMessage("Please Enter Name");
                  return false;
            }
            return true;
      }
});

updateStatus({
      buttonClass: ".statusCategory",
      url: url_local + "/admin/category/change-status",
      tableCallback: function () {
            initDataTablecategory_table();
      }
});


deleteRecord({
      buttonClass: "#deleteCategory",
      url: url_local + "/admin/category",

      tableCallback: function () {
            initDataTablecategory_table();
      }
});

function disableForm() {

      $("#category_form")
            .find("input, select, textarea")
            .prop("disabled", true);

      $("#saveBtn").hide();
}

function enableForm() {

      $("#category_form")
            .find("input, select, textarea")
            .prop("disabled", false);

      $("#saveBtn").show();
}