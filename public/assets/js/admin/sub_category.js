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

$("#createNewSubCategory").click(function () {
      $("#sub_category_form")[0].reset();
      $("#sub_category_id").val('');
      $("#category_id").val('').trigger('change.select2');
      $("#business_id").val('').trigger('change.select2');
      $("#logo_preview").hide();
      $("#status").prop("checked", true);
      $("#logo").prop("required", false);
      $("#saveBtn").show();
      $("#modelHeading").html("Create New Sub Category");
      $("#ajaxModel").modal("show");
      enableForm();
});

editRecord({
      buttonClass: "#editSubCategory",
      url: url_local + "/admin/sub-category",
      onSuccess: function (response) {
            let data = response.Data;
            $("#sub_category_id").val(data.sub_category_id);
            $("#business_id").val(data.business_id).trigger('change');
            loadCategories(data.business_id, function () {

                  $("#category_id")
                        .val(data.category_id)
                        .trigger("change");
            });
            $("#name").val(data.name);
            if (data.logo_url) {
                  $("#logo_preview")
                        .attr("src", data.logo_url)
                        .show();
            } else {
                  $("#logo_preview").hide();
            }
            $("#logo").prop("required", false);
            $("#modelHeading").html("Edit Sub Category");
            $("#saveBtn").show();
            enableForm();
            $("#ajaxModel").modal("show");
      }
});

viewRecord({
      buttonClass: "#viewSubCategory",
      url: url_local + "/admin/sub-category",

      onSuccess: function (response) {

            let data = response.Data;

            $("#sub_category_id").val(data.sub_category_id);
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
            $("#modelHeading").html("View Sub Category");
            disableForm();
            $("#saveBtn").hide();
            $("#ajaxModel").modal("show");
      }
});

saveRecord({
      formId: "#sub_category_form",
      url: url_local + "/admin/sub-category",
      modalId: "#ajaxModel",
      tableCallback: function () {
            initDataTablesub_category_table();
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
      buttonClass: ".statusSubCategory",
      url: url_local + "/admin/sub-category/change-status",
      tableCallback: function () {
            initDataTablesub_category_table();
      }
});


deleteRecord({
      buttonClass: "#deleteSubCategory",
      url: url_local + "/admin/sub-category",

      tableCallback: function () {
            initDataTablesub_category_table();
      }
});

function loadCategories(business_id, callback) {

      ajaxRequest({
            url: url_local + '/admin/category/by-business/' + business_id,
            data: {}
      })
            .then((response) => {
                  let data = response.Data;
                  let options = '<option value="">--Select Category--</option>';
                  $.each(data, function (index, item) {
                        options += `<option value="${item.category_id}">
                                    ${item.name}
                                </option>
                                `;
                  });
                  $('#category_id').html(options);
                  if (callback) callback();
            })
            .catch((err) => {
                  errorMessage(err.Message);
            });

}

function disableForm() {

      $("#sub_category_form")
            .find("input, select, textarea")
            .prop("disabled", true);

      $("#saveBtn").hide();
}

function enableForm() {

      $("#sub_category_form")
            .find("input, select, textarea")
            .prop("disabled", false);

      $("#saveBtn").show();
}