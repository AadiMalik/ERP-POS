
$(document).ready(function () {
      if (journal_entry_id == null) {
            Clear();
      }
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

      var journal_entry_id = "";
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
                              return $.ajax({
                                    type: "GET",
                                    url:
                                          url_local +
                                          "/journal-entry/detail/" +
                                          journal_entry_id,
                                    data: filter,
                                    dataType: "JSON",
                                    success: function (response) {
                                          items = response;
                                          var total_debit = 0;
                                          var total_credit = 0;
                                          var i = 0;
                                          for (i = 0; i < response.length; i++) {
                                                var debit = response[i].debit;
                                                var credit = response[i].credit;
                                                if (!isNaN(debit)) {
                                                      total_debit += parseFloat(debit);
                                                }
                                                if (!isNaN(credit)) {
                                                      total_credit += parseFloat(credit);
                                                }
                                          }
                                          $("#total_debit").val(total_debit);
                                          $("#total_credit").val(total_credit);

                                          var bal = total_debit * 1 - total_credit * 1;
                                          $("#difference").empty();
                                          var bal_value = "";
                                          if (bal > 0) {
                                                bal_value = bal + " Dr";
                                                $("#difference").val(bal_value);
                                          } else if (bal < 0) {
                                                bal_value = bal * -1 + " Cr";
                                                $("#difference").val(bal_value);
                                          } else {
                                                $("#difference").val(0);
                                          }
                                    },
                              });
                        },
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
            $("#btnSave").click(function () {
                  obj = {};
                  obj.item = [];
                  obj.item = $("#jsGrid").jsGrid("option", "data");
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
                  journal_entry_id = $("#journal_entry_id").val();
                  var journal_id = $("#journal_id").find(':selected').val();
                  var entry_no = $("#entry_no").val();
                  var entry_date = $("#entry_date").val();
                  var reference_no = $("#reference_no").val();
                  var description = $("#description").val();
                  if (obj.item.length > 0) {
                        obj = JSON.stringify(obj);
                        $.ajax({
                              data: {
                                    journal_entry_id: journal_entry_id,
                                    journal_id: journal_id,
                                    entry_no: entry_no,
                                    entry_date: entry_date,
                                    reference_no: reference_no,
                                    description: description,
                                    items: obj,
                              },
                              headers: {
                                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr(
                                          "content"
                                    ),
                              },
                              type: "post",
                              url: url_local + "/journal-entry/store",
                              success: function (data) {
                                    if (data != null) {
                                          successMessage("Journal Entry Save Successfully!");
                                          window.location.href =
                                                url_local + "/journal-entry";
                                          setTimeout(function () {
                                                $("#deleted_div").hide("fade");
                                          }, 1000);
                                    }
                              },
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