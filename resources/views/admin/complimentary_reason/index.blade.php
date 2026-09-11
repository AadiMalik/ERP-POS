@php
    use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('complimentary.title_reasons') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                @canAccess('complimentary-reason.create')
                <button type="button" class="btn btn-primary rounded-pill" id="addComplimentaryReasonBtn">
                    <i class="fa fa-plus"></i>
                    {{ __('common.add_new') }}
                </button>
                @endcanAccess
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
                    <table id="complimentary_reason_table" class="table display datatables" style="width:100%">
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
        @include('admin.complimentary_reason.model.create')
    </div>
@endsection
@section('js')
    @php
        $__i18nComplimentaryReasons = [
            'add_heading' => __('complimentary.add_reason_heading'),
            'edit_heading' => __('complimentary.edit_reason_heading'),
            'save' => __('common.save'),
            'update' => __('common.update'),
        ];
    @endphp
    <script>
        window.i18n_complimentary_reasons = @json($__i18nComplimentaryReasons);
    </script>
    @include('admin.partials.datatable', [
        'columns' => "
                    {data: 'name' , name: 'name'},
                    {data: 'status' , name: 'status', 'sortable': false , searchable: false},
                    {data: 'business' , name: 'business', 'sortable': false , searchable: false},
                    {data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
        'route' => 'complimentary-reason/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'complimentary_reason_table',
        'variable' => 'complimentary_reason_table',
        'datefilter' => true,
        'params' => "business_id:$('#filter_business_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2({ dropdownParent: $('#ajaxModel') });
            $('#filter_business_id').select2();
        });
        $('#search_btn').click(function() {
            initDataTablecomplimentary_reason_table();
        });

        $('#addComplimentaryReasonBtn').on('click', function() {
            $('#complimentary_reason_form')[0].reset();
            $('#complimentary_reason_id').val('');
            $('#modelHeading').html(window.i18n_complimentary_reasons.add_heading);
            $('#saveBtn').show().text(window.i18n_complimentary_reasons.save);
            $('#ajaxModel').modal('show');
        });

        editRecord({
            buttonClass: "#editComplimentaryReason",
            url: url_local + "/admin/complimentary-reason",
            onSuccess: function(response) {
                let data = response.Data;
                $("#complimentary_reason_id").val(data.complimentary_reason_id);
                $("#business_id").val(data.business_id).trigger('change.select2');
                $("#name").val(data.name);
                $("#status").val(data.status).trigger('change.select2');

                $("#modelHeading").html(window.i18n_complimentary_reasons.edit_heading);
                $("#saveBtn").show().text(window.i18n_complimentary_reasons.update);
                $("#ajaxModel").modal("show");
            }
        });

        saveForm({
            formId: "#complimentary_reason_form",
            url: url_local + "/admin/complimentary-reason",
            tableFunction: "initDataTablecomplimentary_reason_table"
        });

        deleteRecord({
            buttonClass: "#deleteComplimentaryReason",
            url: url_local + "/admin/complimentary-reason",
            tableFunction: "initDataTablecomplimentary_reason_table"
        });
    </script>
@endsection
