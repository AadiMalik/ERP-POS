@php
    use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4"> Account Sub Types</h4>

        <!-- Basic Bootstrap Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                <button type="button" id="resetAccountSubType" class="btn rounded-pill btn-info">
                    <i class="fa fa-refresh"></i>
                    {{ RoleNames::SUPERADMIN == getRoleName() ? 'Reset System Template' : 'Reset Account Sub Types' }}
                </button>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom" style="display:none;">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">Business</label>
                                <select id="filter_business_id" class="form-select">
                                    <option value="">--All Businesses--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Account Type</label>
                            <select id="filter_account_type_id" class="form-select">
                                <option value="">--All Account Types--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($account_types as $item)
                                        <option value="{{ $item->account_type_id }}">
                                            {{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Date</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">
                                Search
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive text-nowrap p-4">
                    <table id="account_sub_type_table" class="table display datatables" style="width:100%">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Description</th>
                                <th>Account Type</th>
                                <th>Business</th>
                                <th>Action</th>
                            </tr>
                            <!-- end table row-->
                        </thead>
                        <tbody>
                        </tbody>
                    </table>
                    <!-- end table -->
                </div>
            </div>
        </div>
        @include('admin/account_sub_type/model/create')
    </div>
    <!-- ========== table components end ========== -->
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                                        {data: 'code' , name: 'code'},
                                        {data: 'name' , name: 'name'},
                                        {data: 'description' , name: 'description'},
                                        {data: 'accountType' , name: 'accountType', 'sortable': false , searchable: false},
                                        {data: 'business' , name: 'business', 'sortable': false , searchable: false},
                                        {data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
        'route' => 'account-sub-type/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'account_sub_type_table',
        'variable' => 'account_sub_type_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#filter_business_id').val(),account_type_id:$('#filter_account_type_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2({
                dropdownParent: $('#ajaxModel')
            });
            $('#account_type_id').select2({
                dropdownParent: $('#ajaxModel')
            });
            $('#filter_business_id').select2();
            $('#filter_account_type_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTableaccount_sub_type_table();
        });

        editRecord({
            buttonClass: "#editAccountSubType",
            url: url_local + "/admin/account-sub-type",
            onSuccess: function(response) {
                let data = response.Data;
                $("#account_sub_type_id").val(data.account_sub_type_id);
                $("#business_id").val(data.business_id).trigger('change.select2');
                $("#account_type_id").val(data.account_type_id).trigger('change.select2');
                $("#code").val(data.code);
                $("#name").val(data.name);
                $("#description").val(data.description);

                $("#modelHeading").html("Edit Account Sub Type");
                $("#saveBtn").show();
                $("#ajaxModel").modal("show");
            }
        });

        saveRecord({
            formId: "#account_sub_type_form",
            url: url_local + "/admin/account-sub-type",
            modalId: "#ajaxModel",
            tableCallback: function() {
                initDataTableaccount_sub_type_table();
            },
            beforeSubmit: function() {
                if ($("#code").val() == "") {
                    errorMessage("Please Enter Code");
                    return false;
                }
                return true;
            }
        });

        deleteRecord({
            buttonClass: "#deleteAccountSubType",
            url: url_local + "/admin/account-sub-type",

            tableCallback: function() {
                initDataTableaccount_sub_type_table();
            }
        });

        $('#business_id').change(function() {
            let business_id = $(this).val();
            if (!business_id) {
                $('#account_type_id').html('<option value="">--Select Sub Type--</option>');
                return;
            }
            ajaxRequest({
                    url: url_local + '/admin/account-type/by-business/' + business_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">--Select Sub Type--</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.account_type_id}">
                                       ${item.code} ${item.name}
                                    </option>
                                    `;
                    });
                    $('#account_type_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });

        $('#filter_business_id').change(function() {
            let business_id = $(this).val();
            if (!business_id) {
                $('#filter_account_type_id').html('<option value="">--All Sub Types--</option>');
                return;
            }
            ajaxRequest({
                    url: url_local + '/admin/account-type/by-business/' + business_id,
                    data: {}
                })
                .then((response) => {
                    let data = response.Data;
                    let options = '<option value="">--All Sub Types--</option>';
                    $.each(data, function(index, item) {
                        options += `<option value="${item.account_type_id}">
                                       ${item.code} ${item.name}
                                    </option>
                                    `;
                    });
                    $('#filter_account_type_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });

        $("#resetAccountSubType").off('click').on("click", function() {
            let btn = $(this);
            btn.prop("disabled", true);
            $.ajax({
                url: "{{ url('admin/account-sub-type/reset') }}",
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                },
                beforeSend: function() {
                    $("#preloader").show();
                },
                success: function(response) {
                    $("#preloader").hide();
                    successMessage(response.Message || "Account Sub Type reset successfully!");
                    if (typeof account_sub_type_table !== "undefined") {
                        initDataTableaccount_sub_type_table();
                    }
                    btn.prop("disabled", false);
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
