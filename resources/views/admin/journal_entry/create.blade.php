@php
    use App\Enums\RoleNames;
    use Carbon\Carbon;
@endphp
@extends('layouts.app')
@section('css')
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid-theme.min.css">
    <style>
        .jsgrid-header-row>.jsgrid-header-cell {
            background: #f8f9fa;
            font-weight: 600;
        }

        .total-box {
            background: #f8f9fa;
            border-radius: 6px;
            padding: 12px;
        }

        .difference-danger {
            color: #dc3545;
            font-weight: bold;
        }

        .difference-success {
            color: #198754;
            font-weight: bold;
        }

        .total-input {
            border: none;
            text-align: right;
            font-size: 16px;
            font-weight: bold;
        }
    </style>
@endsection

@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold mb-4">Journal Entries</h4>
        <div class="card">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">{{ isset($journal_entry) ? 'Edit' : 'Create' }} Journal Entry</h5>
                </div>
            </div>
            <form id="journal_entryForm">
                @csrf
                <input type="hidden" name="journal_entry_id" id="journal_entry_id"
                    value="{{ isset($journal_entry) ? $journal_entry->journal_entry_id : '' }}">
                <div class="card-body">
                    {{-- ================= MASTER ================= --}}
                    <div class="row">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Business<span class="text-danger">*</span></label>
                                <select class="form-select select2" id="business_id" name="business_id">
                                    <option value="">--Select Business--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}"
                                            {{ isset($journal_entry) ? ($journal_entry->business_id == $item->business_id ? 'selected' : '') : '' }}>
                                            {{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Branch</label>
                            <select class="form-select select2" id="branch_id" name="branch_id">
                                <option value="">--Select Branch--</option>
                                @if (RoleNames::SUPERADMIN != getRoleName())
                                    @foreach ($branches as $item)
                                        <option value="{{ $item->branch_id }}"
                                            {{ isset($journal_entry) ? ($journal_entry->branch_id == $item->branch_id ? 'selected' : '') : '' }}>
                                            {{ isset($item->code) ? $item->code : '' }}
                                            {{ $item->name ?? '' }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Journal<span class="text-danger">*</span></label>
                            <select class="form-select select2" id="journal_id" name="journal_id">
                                <option value="">--Select Journal--</option>
                                @foreach ($journals as $journal)
                                    <option value="{{ $journal->journal_id }}"
                                        {{ isset($journal_entry) ? ($journal_entry->journal_id == $journal->journal_id ? 'selected' : '') : '' }}>
                                        {{ $journal->name }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Entry No<span class="text-danger">*</span></label>
                            <input readonly class="form-control"
                                value="{{ isset($journal_entry) ? $journal_entry->entry_no : '' }}" id="entry_no"
                                name="entry_no">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Entry Date<span class="text-danger">*</span></label>
                            <input type="text" class="form-control datepicker" id="entry_date" name="entry_date"
                                value="{{ isset($journal_entry) ? localDate($journal_entry->entry_date) : localDate(date('Y-m-d')) }}">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label">Reference No<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="reference_no" name="reference_no"
                                value="{{ isset($journal_entry) ? $journal_entry->reference_no : '' }}">
                        </div>
                        <div class="col-md-12">
                            <label class="form-label">Description<span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="description" name="description"
                                value="{{ isset($journal_entry) ? $journal_entry->description : '' }}">
                        </div>
                    </div>
                    <hr>
                    {{-- ================= DETAILS ================= --}}
                    <h5 class="mb-3">Journal Entry Detail</h5>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label>Account<span class="text-danger">*</span></label>
                            <select id="account_id" name="account_id" class="form-select select2">
                                <option value="">--Select Account--</option>
                                @foreach ($accounts as $item)
                                    <option value="{{ $item->account_id }}" data-code="{{ $item->code }}">
                                        {{ $item->code }} - {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Bill No</label>
                            <input class="form-control" id="bill_no" name="bill_no">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label>Cheque No</label>
                            <input class="form-control" id="cheque_no" name="cheque_no">
                        </div>
                        <div class="col-md-4 mb-3" id="cheque_date_dev" style="display: none;">
                            <label>Cheque Date</label>
                            <input type="date" class="form-control" id="cheque_date" name="cheque_date">
                        </div>
                        <div class="col-md-8 mb-3">
                            <label>Description</label>
                            <input type="text" class="form-control" id="line_description" name="line_description">
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4"></div>
                        <div class="col-md-3 mb-3">
                            <label>Debit<span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="debit" name="debit">
                        </div>
                        <div class="col-md-3 mb-3">
                            <label>Credit<span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" id="credit" name="credit">
                        </div>
                        <div class="col-md-2 d-grid align-self-center">
                            <button type="button" id="btnAddRow" class="btn btn-primary">
                                Add Entry
                            </button>
                        </div>
                    </div>
                    <hr>
                    <div id="jsGrid"></div>
                    <hr>
                    <div class="row">
                        <div class="offset-md-8 col-md-4">
                            <div class="total-box">
                                <div class="d-flex justify-content-between">
                                    <strong>Total Debit</strong>
                                    <input type="text" disabled id="total_debit" class="total-input" value="0.00">
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <strong>Total Credit</strong>
                                    <input type="text" disabled id="total_credit" class="total-input" value="0.00">
                                </div>
                                <hr>
                                <div class="d-flex justify-content-between">
                                    <strong>Difference</strong>
                                    <input type="text" disabled id="difference" class="difference-success total-input"
                                        value="0.00">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="card-footer">
                    <div class="text-end">
                        <a href="{{ url('admin/journal-entry') }}" class="btn btn-outline-secondary">
                            Cancel
                        </a>
                        <button type="button" class="btn btn-primary" id="btnSave">
                            Save
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
@endsection
@section('js')
    <script src="https://cdnjs.cloudflare.com/ajax/libs/jsgrid/1.5.3/jsgrid.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#account_id').select2();
            $('#journal_id').select2();
            $('#business_id').select2();
            $('#branch_id').select2();

            
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
    </script>
    <script src="{{ asset('public/assets/js/admin/journal_entry.js') }}"></script>
@endsection
