@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ __('hrm_ess.submit_resignation') }}</h4>

    <div class="card">
        <form action="{{ url('admin/ess/exit') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('hrm_ess.notice_period_days') }}</label>
                        <input type="number" min="0" class="form-control" name="notice_period_days" value="{{ old('notice_period_days', 30) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">{{ __('hrm_ess.preferred_last_working_date') }}</label>
                        <input type="date" class="form-control" name="last_working_date" value="{{ old('last_working_date') }}">
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
                    <button class="btn btn-primary px-4">{{ __('hrm_ess.submit_resignation') }}</button>
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
