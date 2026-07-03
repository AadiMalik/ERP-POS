
$(document).ready(function () {

      $("#debit").on("change", function () {
            $("#credit").val("0.000"); //Credit will be 0 keypress on Debit;
      });
      $("#credit").on("change", function () {
            $("#debit").val("0.000"); //Debit will be 0 keypress on Credit;
      });
      $("#cheque_no").on("keypress", function () {
            $("#cheque_date_dev").css("display", "block");
      });
      $("#account_id").change(function () {
            accountInfo = $("#account_id  :selected").text(); //Account Code Rendering
            accountInfo = accountInfo.replace(/^\s+|\s+$/g, "");
            accounts = accountInfo.split(" -- ");
            $("#accountCode").val(accounts[0]);
      });
      $(document).on("keypress", function (e) {
            if (e.which == 13) {
                  $("#btnAddRow").trigger("click");
            }
      });
      $("#jsGrid").show("2000");

      var obj = "";
      var items = [];

      $("#btnAddRow").off("click").on("click", function () {

            let account_id = $("#account_id").select2("val");
            let account_name = $("#account_id option:selected").text();

            let debit = parseFloat($("#debit").val() || 0).toFixed(3);
            let credit = parseFloat($("#credit").val() || 0).toFixed(3);

            let description = $("#line_description").val();
            let cheque_date = $("#cheque_date").val();
            let cheque_no = $("#cheque_no").val();
            let bill_no = $("#bill_no").val();
            let reference_no = $("#reference_no").val();
            let tbl_id = $("#tbl_id").val();
            let tbl_index = $("#tbl_index").val();

            if (account_id == "" || account_id == null) {
                  errorMessage("Please Select Account!");
                  return;
            }

            if (parseFloat(debit) == 0 && parseFloat(credit) == 0) {
                  errorMessage("Either Debit or Credit must be greater than zero.");
                  $("#debit").focus();
                  return;
            }

            let obj = {
                  account_id: account_id,
                  account_name: account_name,
                  debit: debit,
                  credit: credit,
                  description: description,
                  reference_no: reference_no,
                  cheque_date: cheque_date,
                  cheque_no: cheque_no,
                  bill_no: bill_no,
                  tbl_id: tbl_id,
                  tbl_index: tbl_index
            };

            if (tbl_id) {

                  Object.assign(editedItem, obj);

                  $("#jsGrid")
                        .jsGrid("updateItem", editedItem)
                        .done(function () {
                              updateTotals();
                              Clear();
                        });

            } else {

                  $("#jsGrid")
                        .jsGrid("insertItem", obj)
                        .done(function () {
                              updateTotals();
                              Clear();
                        });
            }

      });
      function updateTotals() {

            let totalDebit = 0;
            let totalCredit = 0;

            let data = $("#jsGrid").jsGrid("option", "data");

            $.each(data, function (_, row) {
                  totalDebit += parseFloat(row.debit || 0);
                  totalCredit += parseFloat(row.credit || 0);
            });

            $("#total_debit").val(totalDebit.toFixed(3));
            $("#total_credit").val(totalCredit.toFixed(3));

            let diff = totalDebit - totalCredit;

            let isBalanced = diff === 0;

            $("#difference")
                  .val(
                        isBalanced
                              ? "0.000"
                              : (diff > 0
                                    ? diff.toFixed(3) + " Dr"
                                    : Math.abs(diff).toFixed(3) + " Cr")
                  )
                  .toggleClass("difference-success", isBalanced)
                  .toggleClass("difference-danger", !isBalanced);
      }
      $(function () {
            $("#jsGrid").jsGrid({
                  height: "300px",
                  width: "100%",
                  editing: false,
                  autoload: true,
                  pageSize: 15,
                  pageButtonCount: 5,
                  insertRowLocation: "top",
                  confirmDeleting: false,
                  rowDoubleClick: function (items) {
                        var getData = items.item;
                        editedItems = items;
                        (index = items.itemIndex), (editedItem = getData);
                        editDetails(getData, index);
                  },
                  onItemDeleting: function (args) {
                        setTimeout(updateTotals, 0);
                  },
                  controller: {
                        loadData: function (filter) {

                              var journal_entry_id = $("#journal_entry_id").val();

                              return $.ajax({
                                    type: "GET",
                                    url: url_local + "/admin/journal-entry/detail",
                                    data: {
                                          journal_entry_id: journal_entry_id
                                    }
                              }).then(function (response) {

                                    console.log(response);

                                    var items = response.Data || [];

                                    var total_debit = 0;
                                    var total_credit = 0;

                                    $.each(items, function (i, item) {

                                          total_debit += parseFloat(item.debit) || 0;
                                          total_credit += parseFloat(item.credit) || 0;

                                    });

                                    $("#total_debit").val(total_debit);
                                    $("#total_credit").val(total_credit);

                                    var bal = total_debit - total_credit;

                                    if (bal > 0)
                                          $("#difference").val(bal + " Dr");
                                    else if (bal < 0)
                                          $("#difference").val(Math.abs(bal) + " Cr");
                                    else
                                          $("#difference").val(0);

                                    // IMPORTANT
                                    return items;
                              });
                        }
                  },
                  fields: [
                        {
                              name: "bill_no",
                              title: "Bill No",
                              type: "text",
                              width: 20
                        },
                        {
                              name: "account_name",
                              title: "Account",
                              type: "text",
                              width: 30
                        },
                        {
                              name: "debit",
                              title: "Debit",
                              type: "number",
                              width: 20,
                              align: "right"
                        },
                        {
                              name: "credit",
                              title: "Credit",
                              type: "number",
                              width: 20,
                              align: "right"
                        },
                        {
                              name: "description",
                              title: "Description",
                              type: "text",
                              width: 45
                        },
                        {
                              name: "cheque_no",
                              title: "Cheque No",
                              type: "text",
                              width: 30
                        },
                        {
                              name: "cheque_date",
                              title: "Cheque Date",
                              type: "text",
                              width: 30
                        },
                        {
                              name: "account_id",
                              visible: false
                        },
                        {
                              type: "control",
                              editButton: false,
                              modeSwitchButton: false
                        }
                  ]
            });
            $("#btnSave").off("click").on("click", function () {
                  obj = {};
                  obj = $("#jsGrid").jsGrid("option", "data");
                  updateTotals();
                  let total_debit = parseFloat($("#total_debit").val());
                  let total_credit = parseFloat($("#total_credit").val());
                  if (
                        $("#journal_id").val() == "" ||
                        $("#journal_id").val() == null
                  ) {
                        errorMessage("Please Select Journal!");
                        $("#journal_id").focus();
                        return;
                  }
                  if (total_credit == "NaN" || total_debit == "NaN") {
                        errorMessage("Please Add a Journal Entries!");
                        return;
                  }
                  if ($("#entry_no").val() == "") {
                        errorMessage("Entry no filed required!");
                        $("#entry_no").focus();
                        return;
                  }
                  if ($("#entry_date").val() == "") {
                        errorMessage("Entry date filed required!");
                        $("#entry_date").focus();
                        return;
                  }
                  if ($("#reference_no").val() == "") {
                        errorMessage("Reference no filed required!");
                        $("#reference_no").focus();
                        return;
                  }
                  if ($("#description").val() == "") {
                        errorMessage("description filed required!");
                        $("#description").focus();
                        return;
                  }
                  if (total_credit != total_debit) {
                        errorMessage("Undifference Debit & Credit");
                        return;
                  }
                  var journal_entry_id = $("#journal_entry_id").val();
                  var journal_id = $("#journal_id").find(':selected').val();
                  var business_id = $("#business_id").find(':selected').val();
                  var branch_id = $("#branch_id").find(':selected').val();
                  var entry_no = $("#entry_no").val();
                  var entry_date = $("#entry_date").val();
                  var reference_no = $("#reference_no").val();
                  var description = $("#description").val();
                  if (obj.length > 0) {

                        obj = JSON.stringify(obj);

                        $("#btnSave").prop("disabled", true);

                        $.ajax({
                              url: url_local + "/admin/journal-entry",
                              type: "POST",
                              headers: {
                                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
                              },
                              data: {
                                    journal_entry_id: journal_entry_id,
                                    journal_id: journal_id,
                                    business_id: business_id,
                                    branch_id: branch_id,
                                    entry_no: entry_no,
                                    entry_date: entry_date,
                                    reference_no: reference_no,
                                    description: description,
                                    details: obj,
                              },
                              success: function (response) {
                                    successMessage("Journal Entry Saved Successfully!");
                                    setTimeout(function () {
                                          window.location.href = url_local + "/admin/journal-entry";
                                    }, 1000);
                              },
                              error: function (xhr) {
                                    $("#btnSave").prop("disabled", false);
                                    if (xhr.responseJSON && xhr.responseJSON.Message) {
                                          errorMessage(xhr.responseJSON.Message);
                                    } else {
                                          errorMessage("Something went wrong.");
                                    }
                              }
                        });
                  }
            });

            var editDetails = function (item, index) {
                  $("#line_description").val(item.description);
                  $("#debit").val(item.debit);
                  $("#credit").val(item.credit);
                  $("#cheque_no").val(item.cheque_no);
                  $("#bill_no").val(item.bill_no);
                  $("#cheque_date").val(item.cheque_date);
                  $("#account_id").val(item.account_id).trigger("change");
                  $("#tbl_id").val(item.tbl_id);
                  $("#tbl_index").val(index);
            };
      });

      function Clear() {
            $("#account_id").val("").trigger("change");
            $("#cheque_no").val("");
            $("#bill_no").val("");
            $("#cheque_date").val("");
            $("#line_description").val("");
            $("#debit").val(0.000);
            $("#credit").val(0.000);
      }

});

$("#journal_id").on("change", function () {
      let journal_id = $(this).val();
      $.ajax({
            url: url_local + '/admin/journal-entry/entry-no',
            type: "GET",
            data: { journal_id: journal_id },
            success: function (response) {
                  $('#entry_no').val(response.Data);
            },
            error: function (xhr) {
                  $('#entry_no').val('');
                  console.log(xhr.responseText);
            }
      });
});

