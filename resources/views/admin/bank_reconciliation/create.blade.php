@php
    use App\Enums\RoleNames;
@endphp
@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">
            <a href="{{ route('bank-reconciliation.index') }}" class="text-muted"><i class="fa fa-arrow-left"></i></a>
            New Bank Reconciliation
        </h4>
        <div class="card">
            <div class="card-body">
                <form id="bank_reconciliation_form">
                    @csrf
                    <div class="row g-3">
                        @if (RoleNames::SUPERADMIN == getRoleName())
                            <div class="col-md-4">
                                <label class="form-label">Business <span class="text-danger">*</span></label>
                                <select name="business_id" id="business_id" class="form-select" required>
                                    <option value="">--Select Business--</option>
                                    @foreach ($business as $item)
                                        <option value="{{ $item->business_id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                        @else
                            <input type="hidden" name="business_id" value="{{ Auth::user()->business_id }}">
                        @endif
                        <div class="col-md-4">
                            <label class="form-label">Bank / Cash Account <span class="text-danger">*</span></label>
                            <select name="account_id" id="account_id" class="form-select" required>
                                <option value="">--Select Account--</option>
                                @foreach ($accounts as $item)
                                    <option value="{{ $item->account_id }}">{{ $item->code }} - {{ $item->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted">Only Cash &amp; Cash Equivalent accounts are listed.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Branch</label>
                            <select name="branch_id" id="branch_id" class="form-select">
                                <option value="">--All / Default--</option>
                                @foreach ($branches as $item)
                                    <option value="{{ $item->branch_id }}" @selected($item->branch_id == Auth::user()->branch_id)>
                                        {{ $item->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Period From <span class="text-danger">*</span></label>
                            <input type="date" name="period_from" id="period_from" class="form-control" required
                                value="{{ now()->startOfMonth()->toDateString() }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Period To <span class="text-danger">*</span></label>
                            <input type="date" name="period_to" id="period_to" class="form-control" required
                                value="{{ now()->toDateString() }}">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Statement Opening Balance</label>
                            <input type="number" step="0.01" name="statement_opening_balance" id="statement_opening_balance"
                                class="form-control" placeholder="Auto from last completed">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Statement Closing Balance <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" name="statement_closing_balance" id="statement_closing_balance"
                                class="form-control" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Notes</label>
                            <textarea name="notes" id="notes" class="form-control" rows="2"></textarea>
                        </div>
                    </div>
                    <div class="mt-4 d-flex gap-2">
                        <button type="submit" class="btn btn-primary" id="btnSave">
                            <i class="fa fa-check"></i> Start Reconciliation
                        </button>
                        <a href="{{ route('bank-reconciliation.index') }}" class="btn btn-outline-secondary">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $('#bank_reconciliation_form').on('submit', function(e) {
            e.preventDefault();
            let $btn = $('#btnSave').prop('disabled', true);
            ajaxRequest({
                url: url_local + '/admin/bank-reconciliation',
                method: 'POST',
                data: $(this).serialize()
            }).then(function(res) {
                successMessage(res.Message);
                window.location.href = res.Data.redirect;
            }).catch(function(err) {
                errorMessage(err.Message || 'Failed');
                $btn.prop('disabled', false);
            });
        });
    </script>
@endsection
