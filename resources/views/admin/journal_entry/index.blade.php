@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            Journal Entries
        </h4>
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
                        'importExportModule' => 'journal-entry',
                        'importExportLabel' => 'Journal Entries',
                        'importExportRefreshFn' => 'initDataTablejournal_entry_table',
                        'importExportExportParamsSelector' => '#business_id',
                    ])
                    <a href="{{ url('admin/journal-entry/create') }}" class="btn btn-primary rounded-pill">
                        <i class="fa fa-plus"></i>
                        Add New
                    </a>
                </div>
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
                                @if (RoleNames::SUPERADMIN == getRoleName())
                                    @foreach ($branches as $item)
                                        <option value="{{ $item->branch_id }}">{{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}
                                        </option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Journal</label>
                            <select id="journal_id" class="form-select">
                                <option value="">--All journals--</option>
                                @foreach ($journals as $item)
                                    <option value="{{ $item->journal_id }}">{{ $item->name ?? '' }}
                                    </option>
                                @endforeach
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
                    <table id="journal_entry_table" class="table datatables">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Entry No.</th>
                                <th>Reference No.</th>
                                <th>Journal</th>
                                <th>Debit</th>
                                <th>Credit</th>
                                <th>Status</th>
                                <th>Branch</th>
                                <th>Business</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
        @include('admin.partials.import-export-modal')
    </div>
@endsection
@section('js')
    @include('admin.partials.datatable', [
        'columns' => "
                {data:'entry_date',name:'entry_date',sortable:false},
                {data:'entry_no',name:'entry_no'},
                {data:'reference_no',name:'reference_no'},
                {data:'journal',name:'journal',sortable:false},
                {data:'total_debit',name:'total_debit',sortable:false},
                {data:'total_credit',name:'total_credit',sortable:false},
                {data:'status',name:'status',sortable:false},
                {data:'branch',name:'branch',sortable:false},
                {data:'business',name:'business',sortable:false},
                {data:'action',name:'action',sortable:false}",
        'route' => 'journal-entry/data',
        'buttons' => false,
        'pageLength' => 10,
        'class' => 'journal_entry_table',
        'variable' => 'journal_entry_table',
        'datefilter' => true,
        'params' =>
            "business_id:$('#business_id').val(),branch_id:$('#branch_id').val(),journal_id:$('#journal_id').val()",
    ])

    <script>
        $(document).ready(function() {
            $('#business_id').select2();
            $('#branch_id').select2();
            $('#journal_id').select2();
        });
        $('#business_id').change(function() {
            let business_id = $(this).val();
            if (!business_id) {
                $('#branch_id').html('<option value="">--Select Branch--</option>');
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
                                      ${item.code} ${item.name}
                                    </option>
                                    `;
                    });
                    $('#branch_id').html(options);
                })
                .catch((err) => {
                    errorMessage(err.Message);
                });
        });
        $('#search_btn').click(function() {
            initDataTablejournal_entry_table();
        });
        //status
        updateStatus({
            buttonClass: ".statusJournalEntry",
            url: url_local + "/admin/journal-entry/change-status",
            tableCallback: function() {
                initDataTablejournal_entry_table();
            }
        });
        //delete
        deleteRecord({
            buttonClass: "#deleteJournalEntry",
            url: url_local + "/admin/journal-entry",

            tableCallback: function() {
                initDataTablejournal_entry_table();
            }
        });
    </script>
@endsection
