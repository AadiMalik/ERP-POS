$("#createParentAccount").click(function () {
      $("#parent_account_form")[0].reset();
      $("#parent_account_id").val('');
      $("#parent_business_id").val('').trigger('change.select2');
      $("#parent_account_type_id").val('').trigger('change.select2');
      $("#parent_account_sub_type_id").val('').trigger('change.select2');
      $("#parent_code").val('');
      $("#parent_name").val('');
      $("#parent_description").val('');
      $("#parentSaveBtn").show();
      $("#modelHeading").html("Create New Parent Account");
      $("#parentAccountModal").modal("show");
});

$("#createChildAccount").click(function () {
      $("#child_account_form")[0].reset();
      $("#child_account_id").val('');
      $("#child_business_id").val('').trigger('change.select2');
      $("#child_account_type_id").val('').trigger('change.select2');
      $("#child_account_sub_type_id").val('').trigger('change.select2');
      $("#child_parent_account_id").val('').trigger('change.select2');
      $("#child_code").val('');
      $("#child_name").val('');
      $("#child_description").val('');
      $("#childSaveBtn").show();
      $("#modelHeading").html("Create New Child Account");
      $("#childAccountModal").modal("show");
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

// parent account

$('#parent_business_id').change(function () {
      let business_id = $(this).val();
      if (!business_id) {
            $('#parent_account_type_id').html('<option value="">--Select Account Type--</option>');
            return;
      }
      ajaxRequest({
            url: url_local + '/admin/account-type/by-business/' + business_id,
            data: {}
      })
            .then((response) => {
                  let data = response.Data;
                  let options = '<option value="">--Select Account Type--</option>';
                  $.each(data, function (index, item) {
                        options += `<option value="${item.account_type_id}">
                                 ${item.code} ${item.name}
                              </option>
                              `;
                  });
                  $('#parent_account_type_id').html(options);
            })
            .catch((err) => {
                  errorMessage(err.Message);
            });
});

$('#parent_account_type_id').change(function () {
      let account_type_id = $(this).val();
      if (!account_type_id) {
            $('#parent_account_sub_type_id').html('<option value="">--Select Account Sub Type--</option>');
            return;
      }
      ajaxRequest({
            url: url_local + '/admin/account-sub-type/by-account-type/' + account_type_id,
            data: {}
      })
            .then((response) => {
                  let data = response.Data;
                  let options = '<option value="">--Select Account Sub Type--</option>';
                  $.each(data, function (index, item) {
                        options += `<option value="${item.account_sub_type_id}">
                                 ${item.code} ${item.name}
                              </option>
                              `;
                  });
                  $('#parent_account_sub_type_id').html(options);
            })
            .catch((err) => {
                  errorMessage(err.Message);
            });
});

// child account

$('#child_business_id').change(function () {
      let business_id = $(this).val();
      if (!business_id) {
            $('#child_account_type_id').html('<option value="">--Select Account Type--</option>');
            return;
      }
      ajaxRequest({
            url: url_local + '/admin/account-type/by-business/' + business_id,
            data: {}
      })
            .then((response) => {
                  let data = response.Data;
                  let options = '<option value="">--Select Account Type--</option>';
                  $.each(data, function (index, item) {
                        options += `<option value="${item.account_type_id}">
                                 ${item.code} ${item.name}
                              </option>
                              `;
                  });
                  $('#child_account_type_id').html(options);
            })
            .catch((err) => {
                  errorMessage(err.Message);
            });
});

$('#child_account_type_id').change(function () {
      let account_type_id = $(this).val();
      if (!account_type_id) {
            $('#child_account_sub_type_id').html('<option value="">--Select Account Sub Type--</option>');
            return;
      }
      ajaxRequest({
            url: url_local + '/admin/account-sub-type/by-account-type/' + account_type_id,
            data: {}
      })
            .then((response) => {
                  let data = response.Data;
                  let options = '<option value="">--Select Account Sub Type--</option>';
                  $.each(data, function (index, item) {
                        options += `<option value="${item.account_sub_type_id}">
                                 ${item.code} ${item.name}
                              </option>
                              `;
                  });
                  $('#child_account_sub_type_id').html(options);
            })
            .catch((err) => {
                  errorMessage(err.Message);
            });
});

$('#child_account_sub_type_id').change(function () {
      let account_sub_type_id = $(this).val();
      if (!account_sub_type_id) {
            $('#child_parent_account_id').html('<option value="">--Select Parent Account--</option>');
            return;
      }
      ajaxRequest({
            url: url_local + '/admin/account/parent-by-sub-type/' + account_sub_type_id,
            data: {}
      })
            .then((response) => {
                  let data = response.Data;
                  let options = '<option value="">--Select Parent Account--</option>';
                  $.each(data, function (index, item) {
                        options += `<option value="${item.account_id}">
                                 ${item.code} ${item.name}
                              </option>
                              `;
                  });
                  $('#child_parent_account_id').html(options);
            })
            .catch((err) => {
                  errorMessage(err.Message);
            });
});