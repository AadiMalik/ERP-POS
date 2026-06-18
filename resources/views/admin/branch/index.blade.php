@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        Branches
    </h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    Filters
                </button>

            </div>
            <a href="{{ url('admin/branch/create') }}" class="btn btn-primary rounded-pill">
                <i class="fa fa-plus"></i>
                Add New
            </a>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-3">
                        <label class="form-label">Business</label>
                        <select id="business_id" class="form-select">
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
            <div class="table-responsive p-4">
                <table id="branch_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Email</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Status</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection
@section('js')
@include('admin.partials.datatable', [
'columns' => "
{data:'code',name:'code'},
{data:'name',name:'name'},
{data:'email',name:'email'},
{data:'phone',name:'phone'},
{data:'address',name:'address'},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'branch/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'branch_table',
'variable' => 'branch_table',
'datefilter' => true,
'params' => "business_id:$('#business_id').val()",
])

<script>
    $(document).ready(function() {
        $('#business_id').select2();
    });
    $('#search_btn').click(function() {
        initDataTablebranch_table();
    });
    //status
    updateStatus({
        buttonClass: ".statusBranch",
        url: url_local + "/admin/branch/change-status",
        tableCallback: function() {
            initDataTablebranch_table();
        }
    });
    //delete
    deleteRecord({
        buttonClass: "#deleteBranch",
        url: url_local + "/admin/branch",

        tableCallback: function() {
            initDataTablebranch_table();
        }
    });
</script>
@endsection