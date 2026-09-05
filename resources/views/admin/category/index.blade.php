@php
use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('categories.title') }}</h4>

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
                    'importExportModule' => 'category',
                    'importExportLabel' => __('categories.title'),
                    'importExportRefreshFn' => 'initDataTablecategory_table',
                    'importExportExportParamsSelector' => '#filter_business_id',
                ])
                <a href="javascript:void(0)" id="createNewCategory" class="btn rounded-pill btn-primary">
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
                <table id="category_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('categories.col_name') }}</th>
                            <th>{{ __('categories.col_logo') }}</th>
                            <th>{{ __('categories.col_business') }}</th>
                            <th>{{ __('categories.col_status') }}</th>
                            <th>{{ __('categories.col_action') }}</th>
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
    @include('admin/category/model/create')
    @include('admin.partials.import-export-modal')
</div>
<!-- ========== table components end ========== -->
@endsection
@section('js')
@php
    $__i18nCategories = [
        'create_title' => __('categories.create_title'),
        'edit_title' => __('categories.edit_title'),
        'view_title' => __('categories.view_title'),
        'please_enter_name' => __('common.please_enter_name'),
    ];
@endphp
<script>
    window.i18n_categories = @json($__i18nCategories);
</script>
<script src="{{ asset('public/assets/js/admin/category.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'name' , name: 'name'},
{data: 'logo' , name: 'logo', 'sortable': false , searchable: false},
{data: 'business' , name: 'business', 'sortable': false , searchable: false},
{data: 'status' , name: 'status', 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'category/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'category_table',
'variable' => 'category_table',
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
        initDataTablecategory_table();
    });
</script>
@endsection
