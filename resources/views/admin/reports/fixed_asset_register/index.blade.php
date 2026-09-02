@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">Fixed Asset Register</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i> Filters
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.fixed-asset-register.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary"><i class="fa fa-print"></i> Print</a>
                    @endcanAccess
                    @canAccess('reports.fixed-asset-register.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger"><i class="fa fa-file-pdf"></i> PDF</a>
                    @endcanAccess
                    @canAccess('reports.fixed-asset-register.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success"><i class="fa fa-file-excel"></i> Excel</a>
                    @endcanAccess
                    @canAccess('reports.fixed-asset-register.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success"><i class="fa fa-file-text"></i> CSV</a>
                    @endcanAccess
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                        <div class="col-md-3">
                            <label class="form-label">Business</label>
                            <select id="business_id" class="form-select">
                                <option value="">--All Businesses--</option>
                                @foreach ($business as $item)
                                <option value="{{ $item->business_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
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
                                <option value="{{ $item->branch_id }}">{{ $item->name ?? '' }}</option>
                                @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Category</label>
                            <select id="fixed_asset_category_id" class="form-select">
                                <option value="">--All Categories--</option>
                                @foreach ($categories as $item)
                                <option value="{{ $item->fixed_asset_category_id }}">{{ $item->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Purchase Date</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">Search</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">Reset</button>
                        </div>
                    </div>
                </div>
                <div class="table-responsive p-4">
                    <table id="fixed_asset_register_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Code</th>
                                <th>Name</th>
                                <th>Category</th>
                                <th>Branch</th>
                                <th>Purchase Date</th>
                                <th class="text-end">Purchase Cost</th>
                                <th class="text-end">Current Value</th>
                                <th class="text-end">Accum. Dep.</th>
                                <th class="text-end">Residual</th>
                                <th>Frequency</th>
                                <th>Status</th>
                                <th>Next Dep. Date</th>
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
            {data:'asset_code',name:'asset_code',sortable:false},
            {data:'name',name:'name',sortable:false},
            {data:'category',name:'category',sortable:false},
            {data:'branch',name:'branch',sortable:false},
            {data:'purchase_date',name:'purchase_date',sortable:false},
            {data:'purchase_cost',name:'purchase_cost',sortable:false,className:'text-end'},
            {data:'current_book_value',name:'current_book_value',sortable:false,className:'text-end'},
            {data:'accumulated_depreciation',name:'accumulated_depreciation',sortable:false,className:'text-end'},
            {data:'residual_value',name:'residual_value',sortable:false,className:'text-end'},
            {data:'depreciation_frequency',name:'depreciation_frequency',sortable:false},
            {data:'depreciation_status',name:'depreciation_status',sortable:false},
            {data:'next_depreciation_date',name:'next_depreciation_date',sortable:false}",
        'route' => 'fixed-asset-register/data',
        'buttons' => false,
        'pageLength' => 50,
        'notordering' => true,
        'class' => 'fixed_asset_register_table',
        'variable' => 'fixed_asset_register_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),fixed_asset_category_id:$('#fixed_asset_category_id').val()",
    ])
    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                branch_id: $('#branch_id').val() || '',
                fixed_asset_category_id: $('#fixed_asset_category_id').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }
        function buildReportUrl(path) {
            return url_local + path + '?' + $.param(currentReportParams());
        }
        $(document).ready(function() {
            $('#business_id, #branch_id, #fixed_asset_category_id').select2();
        });
        $('#search_btn').click(function() { initDataTablefixed_asset_register_table(); });
        $('#btn_print').click(function() { window.open(buildReportUrl('/admin/reports/fixed-asset-register/print'), '_blank'); });
        $('#btn_pdf').click(function() { window.open(buildReportUrl('/admin/reports/fixed-asset-register/pdf'), '_blank'); });
        $('#btn_excel').click(function() { window.location.href = buildReportUrl('/admin/reports/fixed-asset-register/export'); });
        $('#btn_csv').click(function() { window.location.href = buildReportUrl('/admin/reports/fixed-asset-register/export-csv'); });
    </script>
@endsection
