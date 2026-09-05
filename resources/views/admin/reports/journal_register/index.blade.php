@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">{{ __('reports.journal_register') }}</h4>
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
                <div>
                    <button type="button" id="toggleFilter" class="btn btn-outline-primary">
                        <i class="fa fa-filter"></i>
                        {{ __('common.filters') }}
                    </button>
                </div>
                <div class="d-flex gap-2">
                    @canAccess('reports.journal-register.print')
                    <a href="javascript:void(0);" id="btn_print" class="btn btn-outline-secondary">
                        <i class="fa fa-print"></i> {{ __('common.print') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.journal-register.pdf')
                    <a href="javascript:void(0);" id="btn_pdf" class="btn btn-outline-danger">
                        <i class="fa fa-file-pdf"></i> {{ __('common.pdf') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.journal-register.export')
                    <a href="javascript:void(0);" id="btn_excel" class="btn btn-outline-success">
                        <i class="fa fa-file-excel"></i> {{ __('common.excel') }}
                    </a>
                    @endcanAccess
                    @canAccess('reports.journal-register.export-csv')
                    <a href="javascript:void(0);" id="btn_csv" class="btn btn-outline-success">
                        <i class="fa fa-file-text"></i> {{ __('common.csv') }}
                    </a>
                    @endcanAccess
                </div>
            </div>
            <div class="card-body">
                <div id="filterSection" class="card-body border-bottom">
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-3">
                                <label class="form-label">{{ __('common.business') }}</label>
                                <select id="business_id" class="form-select">
                                    <option value="">{{ __('common.all_businesses') }}</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->code ?? '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-3">
                            <label class="form-label">Journal Type</label>
                            <select id="journal_id" class="form-select">
                                <option value="">--All Journal Types--</option>
                                @foreach ($journals as $item)
                                    <option value="{{ $item->journal_id }}">{{ $item->name }} ({{ $item->short }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('reports.reference_type') }}</label>
                            <select id="source_type" class="form-select">
                                <option value="">{{ __('common.all_types') }}</option>
                                @foreach ($source_types as $item)
                                    <option value="{{ $item }}">{{ $item }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.status') }}</label>
                            <select id="status" class="form-select">
                                <option value="posted">Posted</option>
                                <option value="pending">Pending</option>
                                <option value="all">All</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">{{ __('common.date') }}</label>
                            @include('admin.partials.date_filter')
                        </div>
                        <div class="col-md-3 d-flex align-items-end gap-2">
                            <button type="button" id="search_btn" class="btn btn-primary">{{ __('common.search') }}</button>
                            <button type="button" id="reset_filter" class="btn btn-outline-secondary">{{ __('common.reset') }}</button>
                        </div>
                    </div>
                </div>

                <div class="row g-3 p-4 pb-0">
                    <div class="col-md-6">
                        <div class="alert alert-info mb-0">
                            <strong>Total Debit:</strong> <span id="total_debit_display">-</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="alert alert-warning mb-0">
                            <strong>Total Credit:</strong> <span id="total_credit_display">-</span>
                        </div>
                    </div>
                </div>

                <div class="table-responsive p-4">
                    <table id="journal_register_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>{{ __('common.date') }}</th>
                                <th>{{ __('reports.col_jv_number') }}</th>
                                <th>{{ __('reports.col_journal_type') }}</th>
                                <th>Reference Type</th>
                                <th>Reference Number</th>
                                <th>{{ __('reports.col_narration') }}</th>
                                <th class="text-end">Total Debit</th>
                                <th class="text-end">Total Credit</th>
                                <th>{{ __('common.status') }}</th>
                                <th>Posted By</th>
                                <th>{{ __('reports.col_created_by') }}</th>
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
                        {data:'entry_date',name:'entry_date',sortable:false},
                        {data:'entry_no',name:'entry_no',sortable:false},
                        {data:'journal_type',name:'journal_type',sortable:false},
                        {data:'source_type',name:'source_type',sortable:false},
                        {data:'reference_no',name:'reference_no',sortable:false},
                        {data:'description',name:'description',sortable:false},
                        {data:'total_debit',name:'total_debit',sortable:false,className:'text-end'},
                        {data:'total_credit',name:'total_credit',sortable:false,className:'text-end'},
                        {data:'status',name:'status',sortable:false},
                        {data:'posted_by',name:'posted_by',sortable:false},
                        {data:'created_by',name:'created_by',sortable:false}",
        'route' => 'journal-register/data',
        'buttons' => false,
        'pageLength' => 25,
        'notordering' => true,
        'class' => 'journal_register_table',
        'variable' => 'journal_register_table',
        'datefilter' => true,
        'params' => "business_id:$('#business_id').val(),journal_id:$('#journal_id').val(),source_type:$('#source_type').val(),status:$('#status').val()",
    ])

    <script>
        function currentReportParams() {
            return {
                business_id: $('#business_id').val() || '',
                journal_id: $('#journal_id').val() || '',
                source_type: $('#source_type').val() || '',
                status: $('#status').val() || '',
                start_date: (typeof filterStartDate !== 'undefined') ? filterStartDate : '',
                end_date: (typeof filterEndDate !== 'undefined') ? filterEndDate : '',
            };
        }

        function buildReportUrl(path) {
            let query = $.param(currentReportParams());
            return url_local + path + '?' + query;
        }

        $(document).ready(function() {
            $('#business_id').select2();
            $('#journal_id').select2();
            $('#source_type').select2();
            $('#status').select2();
            refreshTotals();
        });

        $('#search_btn').click(function() {
            initDataTablejournal_register_table();
            refreshTotals();
        });

        function refreshTotals() {
            $.ajax({
                url: url_local + '/admin/reports/journal-register/data',
                type: 'POST',
                data: $.extend({
                    _token: $('meta[name="csrf-token"]').attr('content'),
                    draw: 1,
                    start: 0,
                    length: 1,
                }, currentReportParams()),
                success: function(response) {
                    $('#total_debit_display').text(response.total_debit ?? '-');
                    $('#total_credit_display').text(response.total_credit ?? '-');
                }
            });
        }

        $('#btn_print').click(function() {
            window.open(buildReportUrl('/admin/reports/journal-register/print'), '_blank');
        });
        $('#btn_pdf').click(function() {
            window.open(buildReportUrl('/admin/reports/journal-register/pdf'), '_blank');
        });
        $('#btn_excel').click(function() {
            window.location.href = buildReportUrl('/admin/reports/journal-register/export');
        });
        $('#btn_csv').click(function() {
            window.location.href = buildReportUrl('/admin/reports/journal-register/export-csv');
        });
    </script>
@endsection
