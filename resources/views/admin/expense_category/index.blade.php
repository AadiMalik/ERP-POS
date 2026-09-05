@php
    use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('expense_categories.title') }}</h4>

        <!-- Basic Bootstrap Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @include('admin.partials.import-export-buttons', [
                        'importExportModule' => 'expense-category',
                        'importExportLabel' => __('expense_categories.title'),
                        'importExportRefreshFn' => 'initDataTableexpense_category_table',
                        'importExportExportParamsSelector' => '#filter_business_id',
                    ])
                    <button type="button" id="resetExpenseCategory" class="btn rounded-pill btn-info">
                        <i class="fa fa-refresh"></i>
                        {{ __('expense_categories.reset_to_defaults') }}
                    </button>
                    <button type="button" class="btn btn-primary rounded-pill" id="addExpenseCategoryBtn">
                        <i class="fa fa-plus"></i>
                        {{ __('common.add_new') }}
                    </button>
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">{{ __('common.business') }}</label>
                                <select id="filter_business_id" class="form-select">
                                    <option value="">{{ __('common.all_businesses') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.date') }}</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                {{ __('common.search') }}
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                {{ __('common.reset') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive text-nowrap p-4">
                    <table id="expense_category_table" class="table display datatables" style="width:100%">
                        <thead>
                            <tr>
                                <th>{{ __('common.code') }}</th>
                                <th>{{ __('common.name') }}</th>
                                <th>{{ __('expense_categories.expense_account') }}</th>
                                <th>{{ __('common.description') }}</th>
                                <th>{{ __('common.status') }}</th>
                                <th>{{ __('common.business') }}</th>
                                <th>{{ __('common.action') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        @include('admin.expense_category.model.create')
        @include('admin.partials.import-export-modal')
    </div>
    <!-- ========== table components end ========== -->
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                    {data: 'code' , name: 'code'},
                    {data: 'name' , name: 'name'},
                    {data: 'account' , name: 'account', 'sortable': false , searchable: false},
                    {data: 'description' , name: 'description'},
                    {data: 'status' , name: 'status', 'sortable': false , searchable: false},
                    {data: 'business' , name: 'business', 'sortable': false , searchable: false},
                    {data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
        'route' => 'expense-category/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'expense_category_table',
        'variable' => 'expense_category_table',
        'datefilter' => true,
        'params' => "business_id:$('#filter_business_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2({
                dropdownParent: $('#ajaxModel')
            });
            $('#account_id').select2({
                dropdownParent: $('#ajaxModel')
            });
            $('#filter_business_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTableexpense_category_table();
        });

        function toggleAccountField() {
            if ($('#use_default_account').is(':checked')) {
                $('#account_id_wrap').addClass('d-none');
            } else {
                $('#account_id_wrap').removeClass('d-none');
            }
        }

        $(document).on('change', '#use_default_account', toggleAccountField);

        $('#addExpenseCategoryBtn').on('click', function() {
            $('#expense_category_form')[0].reset();
            $('#expense_category_id').val('');
            $('#use_default_account').prop('checked', true);
            toggleAccountField();
            $('#modelHeading').html('Add Expense Category');
            $('#saveBtn').show().text('Save');
            $('#ajaxModel').modal('show');
        });

        editRecord({
            buttonClass: "#editExpenseCategory",
            url: url_local + "/admin/expense-category",
            onSuccess: function(response) {
                let data = response.Data;
                $("#expense_category_id").val(data.expense_category_id);
                $("#business_id").val(data.business_id).trigger('change.select2');
                $("#code").val(data.code);
                $("#name").val(data.name);
                $("#account_id").val(data.account_id).trigger('change.select2');
                $("#description").val(data.description);
                $("#status").val(data.status).trigger('change.select2');
                $('#use_default_account').prop('checked', !!data.use_default_account);
                toggleAccountField();

                $("#modelHeading").html("Edit Expense Category");
                $("#saveBtn").show().text('Update');
                $("#ajaxModel").modal("show");
            }
        });

        saveRecord({
            formId: "#expense_category_form",
            url: url_local + "/admin/expense-category",
            modalId: "#ajaxModel",
            tableCallback: function() {
                initDataTableexpense_category_table();
            },
            beforeSubmit: function() {
                if ($("#name").val() == "") {
                    errorMessage("Please Enter Name");
                    return false;
                }
                if (!$('#use_default_account').is(':checked') && !$('#account_id').val()) {
                    errorMessage('Please select an account, or enable "Use Business Default Expense Account".');
                    return false;
                }
                return true;
            }
        });

        deleteRecord({
            buttonClass: "#deleteExpenseCategory",
            url: url_local + "/admin/expense-category",

            tableCallback: function() {
                initDataTableexpense_category_table();
            }
        });

        $("#resetExpenseCategory").off('click').on("click", function() {
            let btn = $(this);
            btn.prop("disabled", true);
            $.ajax({
                url: "{{ url('admin/expense-category/reset') }}",
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                },
                beforeSend: function() {
                    $("#preloader").show();
                },
                success: function(response) {
                    $("#preloader").hide();
                    btn.prop("disabled", false);

                    if (response.Success === false) {
                        errorMessage(response.Message || "Unable to reset expense categories.");
                        return;
                    }

                    successMessage(response.Message || "Expense categories reset successfully!");
                    if (typeof expense_category_table !== "undefined") {
                        initDataTableexpense_category_table();
                    }
                },
                error: function(xhr) {
                    $("#preloader").hide();
                    errorMessage(
                        xhr.responseJSON?.Message ||
                        xhr.responseJSON?.message ||
                        "Something went wrong"
                    );
                    btn.prop("disabled", false);
                }
            });
        });
    </script>
@endsection
