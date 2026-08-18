@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Submit Resignation</h4>

    <div class="card">
        <form action="{{ url('admin/ess/exit') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">Notice Period (days)</label>
                        <input type="number" min="0" class="form-control" name="notice_period_days" value="{{ old('notice_period_days', 30) }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Preferred Last Working Date</label>
                        <input type="date" class="form-control" name="last_working_date" value="{{ old('last_working_date') }}">
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">Reason</label>
                        <textarea class="form-control" name="reason" rows="3">{{ old('reason') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Submit Resignation</button>
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
