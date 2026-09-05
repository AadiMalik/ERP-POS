@php
use App\Enums\RoleNames;
@endphp

@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('website_cms.faq') }}</h4>

    <div class="card">
        <div class="card-header d-flex justify-content-between">
            @if (RoleNames::SUPERADMIN == getRoleName())
            <div class="col-md-3">
                <select id="filter_business_id" class="form-select">
                    <option value="">{{ __('common.all_businesses') }}</option>
                    @foreach ($business as $item)
                    <option value="{{ $item->business_id }}">{{ $item->name ?? '' }}</option>
                    @endforeach
                </select>
            </div>
            @else
            <div></div>
            @endif
            <a href="javascript:void(0)" id="createNewWebsiteFaq" class="btn rounded-pill btn-primary">
                <i class="icon-base fa fa-plus mr-5"></i> {{ __('common.add_new') }}</a>
        </div>
        <div class="card-body">
            <div class="table-responsive text-nowrap p-4">
                <table id="website_faq_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>{{ __('website_cms.col_question') }}</th>
                            <th>{{ __('common.sort_order') }}</th>
                            <th>{{ __('common.status') }}</th>
                            <th>{{ __('common.action') }}</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    @include('admin.website_faq.model.create')
</div>
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/website_faq.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'question' , name: 'question'},
{data: 'sort_order' , name: 'sort_order'},
{data: 'status' , name: 'status', 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'website-faq/data',
'buttons' => false,
'pageLength' => 25,
'class' => 'website_faq_table',
'variable' => 'website_faq_table',
'params' => "business_id:$('#filter_business_id').val()",
])
<script>
    $(document).ready(function() { $('#filter_business_id').select2().on('change', function() { initDataTablewebsite_faq_table(); }); $('#business_id').select2({dropdownParent: $('#ajaxModel')}); });
</script>
@endsection
