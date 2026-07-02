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

$("body").on("click", "#editParentAccount", function (event) {
      event.preventDefault();
      event.stopImmediatePropagation();
      var account_id = $(this).data("id");
      $.ajax({
            url: url_local + "/admin/account/edit/" + account_id,
            type: "GET",
            headers: {
                  "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: async function (response) {

                  let data = response.Data;

                  $("#parent_account_id").val(data.account_id);

                  $("#parent_business_id")
                        .val(data.business_id)
                        .trigger("change");

                  // wait for account type
                  await loadParentAccountTypes(data.business_id);

                  $("#parent_account_type_id")
                        .val(data.account_type_id)
                        .trigger("change");

                  // wait for sub types
                  await loadParentSubTypes(data.account_type_id);

                  $("#parent_account_sub_type_id")
                        .val(data.account_sub_type_id)
                        .trigger("change");

                  $("#parent_code").val(data.code);
                  $("#parent_name").val(data.name);
                  $("#parent_description").val(data.description);

                  $("#modelHeading").html("Edit Parent Account");
                  $("#parentAccountModal").modal("show");

            },
            error: function (xhr, ajaxOptions, thrownError) {
                  errorMessage(thrownError);
            },
      });
});

$("body").on("click", "#editChildAccount", function (event) {
      event.preventDefault();
      event.stopImmediatePropagation();
      var account_id = $(this).data("id");
      $.ajax({
            url: url_local + "/admin/account/edit/" + account_id,
            type: "GET",
            headers: {
                  "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: async function (response) {

                  let data = response.Data;

                  $("#child_account_id").val(data.account_id);

                  $("#child_business_id")
                        .val(data.business_id)
                        .trigger("change");

                  await loadChildAccountTypes(data.business_id);

                  $("#child_account_type_id")
                        .val(data.account_type_id)
                        .trigger("change");

                  await loadChildSubTypes(data.account_type_id);

                  $("#child_account_sub_type_id")
                        .val(data.account_sub_type_id)
                        .trigger("change");

                  await loadParentAccounts(data.account_sub_type_id);

                  $("#child_parent_account_id")
                        .val(data.parent_account_id)
                        .trigger("change");

                  $("#child_code").val(data.code);
                  $("#child_name").val(data.name);
                  $("#child_description").val(data.description);

                  $("#modelHeading").html("Edit Child Account");
                  $("#childAccountModal").modal("show");

            },
            error: function (xhr, ajaxOptions, thrownError) {
                  errorMessage(thrownError);
            },
      });
});

saveRecord({
      formId: "#parent_account_form",
      url: url_local + "/admin/account/save-parent",
      modalId: "#parentAccountModal",
      tableCallback: function () {
            window.location.reload();
      },
      beforeSubmit: function () {
            if ($("#parent_account_type_id").val() == "") {
                  errorMessage("Please select account type");
                  return false;
            }
            if ($("#parent_account_sub_type_id").val() == "") {
                  errorMessage("Please select account sub type");
                  return false;
            }
            if ($("#parent_code").val() == "") {
                  errorMessage("Please enter code");
                  return false;
            }
            if ($("#parent_name").val() == "") {
                  errorMessage("Please enter name");
                  return false;
            }
            return true;
      }
});

saveRecord({
      formId: "#child_account_form",
      url: url_local + "/admin/account/save-child",
      modalId: "#childAccountModal",
      tableCallback: function () {
            window.location.reload();
      },
      beforeSubmit: function () {
            if ($("#child_account_type_id").val() == "") {
                  errorMessage("Please select account type");
                  return false;
            }
            if ($("#child_account_sub_type_id").val() == "") {
                  errorMessage("Please select account sub type");
                  return false;
            }
            if ($("#child_parent_account_id").val() == "") {
                  errorMessage("Please select parent account");
                  return false;
            }
            if ($("#child_code").val() == "") {
                  errorMessage("Please enter code");
                  return false;
            }
            if ($("#child_name").val() == "") {
                  errorMessage("Please enter name");
                  return false;
            }
            return true;
      }
});

updateStatus({
      buttonClass: ".statusAccount",
      url: url_local + "/admin/account/change-status",
      tableCallback: function () {
            window.location.reload();
      }
});


deleteRecord({
      buttonClass: "#deleteParentAccount",
      url: url_local + "/admin/account/delete",

      tableCallback: function () {
            window.location.reload();
      }
});

deleteRecord({
      buttonClass: "#deleteChildAccount",
      url: url_local + "/admin/account/delete",

      tableCallback: function () {
            window.location.reload();
      }
});

// parent account

$('#parent_business_id').change(function () {
      let business_id = $(this).val();
      loadParentAccountTypes(business_id);
});

$('#parent_account_type_id').change(function () {
      let account_type_id = $(this).val();
      loadParentSubTypes(account_type_id);
});

// child account

$('#child_business_id').change(function () {
      let business_id = $(this).val();
      loadChildAccountTypes(business_id);
});

$('#child_account_type_id').change(function () {
      let account_type_id = $(this).val();
      loadChildSubTypes(account_type_id)
});

$('#child_account_sub_type_id').change(function () {
      let account_sub_type_id = $(this).val();
      loadParentAccounts(account_sub_type_id);
});

// helper function
function loadParentAccountTypes(business_id) {

      return $.get(
            url_local + "/admin/account-type/by-business/" + business_id
      ).then(function (response) {

            let options = '<option value="">--Select Account Type--</option>';

            $.each(response.Data, function (i, item) {

                  options +=
                        `<option value="${item.account_type_id}">
                ${item.code} ${item.name}
            </option>`;

            });

            $("#parent_account_type_id").html(options);

      });

}

function loadParentSubTypes(account_type_id) {

      return $.get(
            url_local + "/admin/account-sub-type/by-account-type/" + account_type_id
      ).then(function (response) {

            let options = '<option value="">--Select Account Sub Type--</option>';

            $.each(response.Data, function (i, item) {

                  options +=
                        `<option value="${item.account_sub_type_id}">
                ${item.code} ${item.name}
            </option>`;

            });

            $("#parent_account_sub_type_id").html(options);

      });

}


// child helper
function loadChildAccountTypes(business_id) {

      return $.get(
            url_local + "/admin/account-type/by-business/" + business_id
      ).then(function (response) {

            let options = '<option value="">--Select Account Type--</option>';

            $.each(response.Data, function (i, item) {

                  options +=
                        `<option value="${item.account_type_id}">
                ${item.code} ${item.name}
            </option>`;

            });

            $("#child_account_type_id").html(options);

      });

}

function loadChildSubTypes(account_type_id) {

      return $.get(
            url_local + "/admin/account-sub-type/by-account-type/" + account_type_id
      ).then(function (response) {

            let options = '<option value="">--Select Account Sub Type--</option>';

            $.each(response.Data, function (i, item) {

                  options +=
                        `<option value="${item.account_sub_type_id}">
                ${item.code} ${item.name}
            </option>`;

            });

            $("#child_account_sub_type_id").html(options);

      });

}

function loadParentAccounts(account_sub_type_id) {

      return $.get(
            url_local + "/admin/account/parent-by-sub-type/" + account_sub_type_id
      ).then(function (response) {

            let options = '<option value="">--Select Parent Account--</option>';

            $.each(response.Data, function (i, item) {

                  options +=
                        `<option value="${item.account_id}">
                ${item.code} ${item.name}
            </option>`;

            });

            $("#child_parent_account_id").html(options);

      });

}