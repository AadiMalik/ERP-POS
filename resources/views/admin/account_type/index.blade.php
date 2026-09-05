@php
    use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('css')
@endsection
@section('content')
    <!-- ========== table components start ========== -->
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('account_types.title') }}</h4>

        <!-- Basic Bootstrap Table -->
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>

                </div>
                <button type="button" id="resetAccountType" class="btn rounded-pill btn-info">
                    <i class="fa fa-refresh"></i>
                    {{ RoleNames::SUPERADMIN == getRoleName() ? __('account_types.reset_system_template') : __('account_types.reset_account_types') }}
                </button>
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
                                Search
                            </button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">
                                Reset
                            </button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive text-nowrap p-4">
                    <table id="account_type_table" class="table display datatables" style="width:100%">
                        <thead>
                            <tr>
                                <th>{{ __('common.code') }}</th>
                                <th>{{ __('common.name') }}</th>
                                <th>{{ __('common.description') }}</th>
                                <th>{{ __('common.business') }}</th>
                                <th>{{ __('common.action') }}</th>
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
        @include('admin/account_type/model/create')
    </div>
    <!-- ========== table components end ========== -->
@endsection
@section('js')
    @php
        $__i18nAccountTypes = [
            'edit_heading' => __('account_types.edit_heading'),
            'please_enter_code' => __('account_types.please_enter_code'),
            'reset_success' => __('account_types.reset_success'),
        ];
    @endphp
    <script>window.i18n_account_types = @json($__i18nAccountTypes);</script>
    @include('admin.partials.datatable', [
        'columns' => "
                    {data: 'code' , name: 'code'},
                    {data: 'name' , name: 'name'},
                    {data: 'description' , name: 'description'},
                    {data: 'business' , name: 'business', 'sortable': false , searchable: false},
                    {data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
        'route' => 'account-type/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'account_type_table',
        'variable' => 'account_type_table',
        'datefilter' => true,
        'params' => "business_id:$('#filter_business_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2({
                dropdownParent: $('#ajaxModel')
            });
            $('#filter_business_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTableaccount_type_table();
        });

        editRecord({
            buttonClass: "#editAccountType",
            url: url_local + "/admin/account-type",
            onSuccess: function(response) {
                let data = response.Data;
                $("#account_type_id").val(data.account_type_id);
                $("#business_id").val(data.business_id).trigger('change.select2');
                $("#code").val(data.code);
                $("#name").val(data.name);
                $("#description").val(data.description);

                $("#modelHeading").html(window.i18n_account_types.edit_heading);
                $("#saveBtn").show();
                $("#ajaxModel").modal("show");
            }
        });

        saveRecord({
            formId: "#account_type_form",
            url: url_local + "/admin/account-type",
            modalId: "#ajaxModel",
            tableCallback: function() {
                initDataTableaccount_type_table();
            },
            beforeSubmit: function() {
                if ($("#code").val() == "") {
                    errorMessage(window.i18n_account_types.please_enter_code);
                    return false;
                }
                return true;
            }
        });

        deleteRecord({
            buttonClass: "#deleteAccountType",
            url: url_local + "/admin/account-type",

            tableCallback: function() {
                initDataTableaccount_type_table();
            }
        });

        $("#resetAccountType").off('click').on("click", function() {
            let btn = $(this);
            btn.prop("disabled", true);
            $.ajax({
                url: "{{ url('admin/account-type/reset') }}",
                type: "POST",
                headers: {
                    "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content")
                },
                beforeSend: function() {
                    $("#preloader").show();
                },
                success: function(response) {
                    $("#preloader").hide();
                    successMessage(response.Message || window.i18n_account_types.reset_success);
                    if (typeof account_type_table !== "undefined") {
                        initDataTableaccount_type_table();
                    }
                    btn.prop("disabled", false);
                },
                error: function(xhr) {
                    $("#preloader").hide();
                    errorMessage(
                        xhr.responseJSON?.Message ||
                        xhr.responseJSON?.message ||
                        (window.i18n?.something_went_wrong || "Something went wrong")
                    );
                    btn.prop("disabled", false);
                }
            });
        });
    </script>
@endsection
