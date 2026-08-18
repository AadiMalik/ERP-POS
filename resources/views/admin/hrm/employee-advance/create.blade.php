@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">New Advance Request</h4>

    <div class="card">
        <form action="{{ url('admin/employee-advance') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">Employee <span class="text-danger">*</span></label>
                        <select name="employee_id" class="form-select select2" required>
                            <option value="">-- Select Employee --</option>
                            @foreach ($employees as $item)
                            <option value="{{ $item->employee_id }}" {{ old('employee_id') == $item->employee_id ? 'selected' : '' }}>
                                {{ $item->user->name ?? '-' }} ({{ $item->employee_code }})
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="1" class="form-control" name="amount" value="{{ old('amount') }}" required>
                    </div>
                    <div class="col-md-3">
                        <label class="fw-semibold">Installments <span class="text-danger">*</span></label>
                        <input type="number" min="1" class="form-control" name="installments_count" value="{{ old('installments_count', 1) }}" required>
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">Reason</label>
                        <textarea class="form-control" name="reason" rows="2">{{ old('reason') }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Submit Request</button>
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
<script>
    $(document).ready(function() {
        $('.select2').select2();
    });
</script>
@endsection
