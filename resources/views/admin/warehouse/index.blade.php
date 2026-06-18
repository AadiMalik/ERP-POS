@php
use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">
        Warehouses
    </h4>
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    Filters
                </button>

            </div>
            <a href="{{ url('admin/warehouse/create') }}" class="btn btn-primary rounded-pill">
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
                        <label class="form-label">Branch</label>
                        <select id="branch_id" class="form-select">
                            <option value="">--All Branches--</option>
                            @if (RoleNames::SUPERADMIN != getRoleName())
                            @foreach ($branches as $item)
                            <option value="{{ $item->branch_id }}">{{ isset($item->code) ? $item->code : '' }}
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
            <div class="table-responsive p-4">
                <table id="warehouse_table" class="table datatables">
                    <thead>
                        <tr>
                            <th>Code</th>
                            <th>Name</th>
                            <th>Phone</th>
                            <th>Address</th>
                            <th>Branch</th>
                            <th>Business</th>
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
{data:'phone',name:'phone'},
{data:'address',name:'address'},
{data:'branch',name:'branch',sortable:false},
{data:'business',name:'business',sortable:false},
{data:'status',name:'status'},
{data:'action',name:'action',sortable:false}",
'route' => 'warehouse/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'warehouse_table',
'variable' => 'warehouse_table',
'datefilter' => true,
'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val()",
])

<script>
    $(document).ready(function() {
        $('#business_id').select2();
        $('#branch_id').select2();
    });
    $('#search_btn').click(function() {
        initDataTablewarehouse_table();
    });
    $('#business_id').change(function() {
        let business_id = $(this).val();
        if (!business_id) {
            $('#branch_id').html('<option value="">--All Branches--</option>');
            return;
        }
        ajaxRequest({
                url: url_local + '/admin/branch/by-business/' + business_id,
                data: {}
            })
            .then((response) => {
                let data = response.Data;
                let options = '<option value="">--Select Branch--</option>';
                $.each(data, function(index, item) {
                    options += `<option value="${item.branch_id}">
                                        ${item.name}
                                    </option>
                                    `;
                });
                $('#branch_id').html(options);
            })
            .catch((err) => {
                errorMessage(err.Message);
            });
    });
    //status
    updateStatus({
            buttonClass: ".statusWarehouse",
            url: url_local + "/admin/warehouse/change-status",
            tableCallback: function() {
                initDataTablewarehouse_table();
            }
        });
    //delete
    deleteRecord({
        buttonClass: "#deleteWarehouse",
        url: url_local + "/admin/warehouse",

        tableCallback: function() {
            initDataTablewarehouse_table();
        }
    });
</script>
@endsection