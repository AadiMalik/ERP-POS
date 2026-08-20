@extends('layouts.app')
@section('css')
@endsection
@section('content')
<!-- ========== table components start ========== -->
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4"> POS Vouchers</h4>

    <!-- Basic Bootstrap Table -->
    <div class="card">
        <div class="card-header d-flex justify-content-between">
            <div>
                <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                    <i class="fa fa-filter"></i>
                    Filters
                </button>

            </div>
            <div class="d-flex gap-2">
                @include('admin.partials.import-export-buttons', [
                    'importExportModule' => 'voucher',
                    'importExportLabel' => 'Vouchers',
                    'importExportRefreshFn' => 'initDataTablepos_voucher_table',
                ])
                <a href="javascript:void(0)" id="createNewVoucher" class="btn rounded-pill btn-primary">
                    <i class="icon-base fa fa-plus mr-5"></i>Add New</a>
            </div>
        </div>
        <div class="card-body">
            <div id="filterSection" class="card-body border-bottom" style="display:none;">
                <div class="row g-3">
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
                <table id="pos_voucher_table" class="table display datatables" style="width:100%">
                    <thead>
                        <tr>
                            <th>Name</th>
                            <th>Code</th>
                            <th>Type</th>
                            <th>Value</th>
                            <th>Usage</th>
                            <th>Valid From</th>
                            <th>Valid To</th>
                            <th>Status</th>
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
    @include('admin/voucher/model/create')
    @include('admin.partials.import-export-modal')
</div>
<!-- ========== table components end ========== -->
@endsection
@section('js')
<script src="{{ asset('public/assets/js/admin/voucher.js') }}"></script>
@include('admin.partials.datatable', [
'columns' => "
{data: 'name' , name: 'name'},
{data: 'code' , name: 'code'},
{data: 'type' , name: 'type', 'sortable': false , searchable: false},
{data: 'value' , name: 'value', 'sortable': false , searchable: false},
{data: 'usage' , name: 'usage', 'sortable': false , searchable: false},
{data: 'valid_from' , name: 'valid_from'},
{data: 'valid_to' , name: 'valid_to'},
{data: 'status' , name: 'status' , 'sortable': false , searchable: false},
{data: 'action' , name: 'action' , 'sortable': false , searchable: false},",
'route' => 'voucher/data',
'buttons' => false,
'pageLength' => 10,
'class' => 'pos_voucher_table',
'variable' => 'pos_voucher_table',
'datefilter' => true,
])

<script>
    $(document).ready(function() {
        $('#business_id').select2({
            dropdownParent: $('#ajaxModel')
        });
        $('.select2-multiple').select2({
            dropdownParent: $('#ajaxModel')
        });
    });
    $('#search_btn').click(function() {
        initDataTablepos_voucher_table();
    });
</script>
@endsection
