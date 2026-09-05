@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_ess.request_salary_advance') }}</h4>

    <div class="card">
        <form action="{{ url('admin/ess/advance') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('common.amount') }} <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" class="form-control" name="amount" value="{{ old('amount') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('hrm_ess.preferred_installments') }} <span class="text-danger">*</span></label>
                        <input type="number" min="1" class="form-control" name="installments_count" value="{{ old('installments_count', 1) }}" required>
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">{{ __('common.reason') }}</label>
                        <textarea class="form-control" name="reason" rows="3">{{ old('reason') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">{{ __('hrm_ess.submit_request') }}</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
@if ($errors->any())
<script>
    errorMessage("{{ $errors->first() }}");
</script>
@endif
@endsection
