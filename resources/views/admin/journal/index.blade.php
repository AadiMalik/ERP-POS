@php
use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('journals.title') }}</h4>

    <!-- Basic Bootstrap Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    Filters
                </button>

            </div>
            <a href="javascript:void(0)" id="createNewJournal" class="btn rounded-pill btn-primary">
                <i class="icon-base fa fa-plus mr-5"></i>{{ __('common.add_new') }}</a>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
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
                <table id="journal_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('common.name') }}</th>
                            <th>{{ __('journals.short') }}</th>
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
    @include('admin/journal/model/create')
</div>
<!-- ========== table components end ========== -->
@endsection
@section('js')
@php
    $__i18nJournals = [
        'create_new' => __('journals.create_new'),
        'edit_heading' => __('journals.edit_heading'),
        'please_enter_name' => __('journals.please_enter_name'),
        'please_enter_short' => __('journals.please_enter_short'),
    ];
@endphp
<script>window.i18n_journals = @json($__i18nJournals);</script>
<script src="{{ asset('public/assets/js/admin/journal.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'name' , name: 'name'},
{data: 'short' , name: 'short'},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'journal/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'journal_table',
'variable' => 'journal_table',
'datefilter' => true,
])
@endsection