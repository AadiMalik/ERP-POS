@php
use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('sub_categories.title') }}</h4>

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
                    'importExportModule' => 'sub-category',
                    'importExportLabel' => __('sub_categories.title'),
                    'importExportRefreshFn' => 'initDataTablesub_category_table',
                    'importExportExportParamsSelector' => '#filter_business_id',
                ])
                <a href="javascript:void(0)" id="createNewSubCategory" class="btn rounded-pill btn-primary">
                    <i class="icon-base fa fa-plus mr-5"></i>{{ __('common.add_new') }}</a>
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
                        <label class="form-label">{{ __('common.category') }}</label>
                        <select id="filter_category_id" class="form-select">
                            <option value="">{{ __('common.all_categories') }}</option>
                            @foreach ($categories as $item)
                            <option value="{{ $item->category_id }}">
                                {{ $item->name ?? '' }}
                            </option>
                            @endforeach
                        </select>
                    </div>
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
                <table id="sub_category_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('common.logo') }}</th>
                            <th>{{ __('common.category') }}</th>
                            <th>{{ __('common.business') }}</th>
                            <th>{{ __('common.status') }}</th>
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
    @include('admin/sub_category/model/create')
    @include('admin.category.model.quick-create', ['business' => $business ?? []])
    @include('admin.partials.import-export-modal')
</div>
<!-- ========== table components end ========== -->
@endsection
@section('js')
@php
    $__i18nSubCategories = [
        'create_title' => __('sub_categories.create_title'),
        'edit_title' => __('sub_categories.edit_title'),
        'view_title' => __('sub_categories.view_title'),
        'select_category' => __('common.select_category'),
        'all_categories' => __('common.all_categories'),
        'please_enter_name' => __('common.please_enter_name'),
    ];
@endphp
<script>
    window.i18n_sub_categories = @json($__i18nSubCategories);
</script>
<script src="{{ asset('public/assets/js/admin/sub_category.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'name' , name: 'name'},
{data: 'logo' , name: 'logo', 'sortable': false , searchable: false},
{data: 'category' , name: 'category', 'sortable': false , searchable: false},
{data: 'business' , name: 'business', 'sortable': false , searchable: false},
{data: 'status' , name: 'status', 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'sub-category/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'sub_category_table',
'variable' => 'sub_category_table',
'datefilter' => true,
'params' => "business_id:$('#filter_business_id').val(),category_id:$('#filter_category_id').val()",
])

<script>
    $(document).ready(function() {
        $('#business_id').select2({
            dropdownParent: $('#ajaxModel')
        });
        $('#category_id').select2({
            dropdownParent: $('#ajaxModel')
        });
        $('#filter_business_id').select2();
        $('#filter_category_id').select2();
    });
    $('#search_btn').click(function() {
        initDataTablesub_category_table();
    });

    wireNestedQuickAdd('#ajaxModel', '#quickAddCategoryModal');

    initQuickAdd({
        modalId: '#quickAddCategoryModal',
        formId: '#quickAddCategoryForm',
        url: url_local + '/admin/category',
        valueField: 'category_id',
        labelField: 'name',
        targetSelectIds: ['category_id'],
    });
    $('#business_id').change(function() {
        let business_id = $(this).val();
        if (!business_id) {
            $('#category_id').html('<option value="">' + (window.i18n_sub_categories?.select_category || '--Select Category--') + '</option>');
            return;
        }
        ajaxRequest({
                url: url_local + '/admin/category/by-business/' + business_id,
                data: {}
            })
            .then((response) => {
                let data = response.Data;
                let options = '<option value="">' + (window.i18n_sub_categories?.select_category || '--Select Category--') + '</option>';
                $.each(data, function(index, item) {
                    options += `<option value="${item.category_id}">
                                        ${item.name}
                                    </option>
                                    `;
                });
                $('#category_id').html(options);
            })
            .catch((err) => {
                errorMessage(err.Message);
            });
    });
    $('#filter_business_id').change(function() {
        let business_id = $(this).val();
        if (!business_id) {
            $('#filter_category_id').html('<option value="">' + (window.i18n_sub_categories?.all_categories || '--All Categories--') + '</option>');
            return;
        }
        ajaxRequest({
                url: url_local + '/admin/category/by-business/' + business_id,
                data: {}
            })
            .then((response) => {
                let data = response.Data;
                let options = '<option value="">' + (window.i18n_sub_categories?.all_categories || '--All Categories--') + '</option>';
                $.each(data, function(index, item) {
                    options += `<option value="${item.category_id}">
                                        ${item.name}
                                    </option>
                                    `;
                });
                $('#filter_category_id').html(options);
            })
            .catch((err) => {
                errorMessage(err.Message);
            });
    });
</script>
@endsection
