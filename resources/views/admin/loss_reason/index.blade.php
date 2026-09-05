@php
    use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('loss_reasons.title') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        Filters
                    </button>
                </div>
                <button type="button" class="btn btn-primary rounded-pill" id="addLossReasonBtn">
                    <i class="fa fa-plus"></i>
                    Add New
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
                    <table id="loss_reason_table" class="table display datatables" style="width:100%">
                        <thead>
                            <tr>
                                <th>{{ __('common.name') }}</th>
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
        @include('admin.loss_reason.model.create')
    </div>
@endsection
@section('js')
    @php
        $__i18nLossReasons = [
            'add_heading' => __('loss_reasons.add_heading'),
            'edit_heading' => __('loss_reasons.edit_heading'),
            'save' => __('common.save'),
            'update' => __('common.update'),
            'please_enter_name' => __('common.please_enter_name'),
        ];
    @endphp
    <script>
        window.i18n_loss_reasons = @json($__i18nLossReasons);
    </script>
    @include('admin.partials.datatable', [
        'columns' => "
                    {data: 'name' , name: 'name'},
                    {data: 'status' , name: 'status', 'sortable': false , searchable: false},
                    {data: 'business' , name: 'business', 'sortable': false , searchable: false},
                    {data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
        'route' => 'loss-reason/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'loss_reason_table',
        'variable' => 'loss_reason_table',
        'datefilter' => true,
        'params' => "business_id:$('#filter_business_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2({ dropdownParent: $('#ajaxModel') });
            $('#filter_business_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTableloss_reason_table();
        });

        $('#addLossReasonBtn').on('click', function() {
            $('#loss_reason_form')[0].reset();
            $('#loss_reason_id').val('');
            $('#modelHeading').html(window.i18n_loss_reasons.add_heading);
            $('#saveBtn').show().text(window.i18n_loss_reasons.save);
            $('#ajaxModel').modal('show');
        });

        editRecord({
            buttonClass: "#editLossReason",
            url: url_local + "/admin/loss-reason",
            onSuccess: function(response) {
                let data = response.Data;
                $("#loss_reason_id").val(data.loss_reason_id);
                $("#business_id").val(data.business_id).trigger('change.select2');
                $("#name").val(data.name);
                $("#status").val(data.status).trigger('change.select2');

                $("#modelHeading").html(window.i18n_loss_reasons.edit_heading);
                $("#saveBtn").show().text(window.i18n_loss_reasons.update);
                $("#ajaxModel").modal("show");
            }
        });

        saveRecord({
            formId: "#loss_reason_form",
            url: url_local + "/admin/loss-reason",
            modalId: "#ajaxModel",
            tableCallback: function() {
                initDataTableloss_reason_table();
            },
            beforeSubmit: function() {
                if ($("#name").val() == "") {
                    errorMessage(window.i18n_loss_reasons.please_enter_name);
                    return false;
                }
                return true;
            }
        });

        deleteRecord({
            buttonClass: "#deleteLossReason",
            url: url_local + "/admin/loss-reason",
            tableCallback: function() {
                initDataTableloss_reason_table();
            }
        });
    </script>
@endsection
