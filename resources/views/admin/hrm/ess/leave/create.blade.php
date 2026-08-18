@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Apply for Leave</h4>

    <div class="card">
        <form action="{{ url('admin/ess/leave') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">Leave Type <span class="text-danger">*</span></label>
                        <select name="leave_type_id" class="form-select" required>
                            <option value="">-- Select --</option>
                            @foreach ($leave_types as $item)
                            <option value="{{ $item->leave_type_id }}">{{ $item->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Attachment</label>
                        <input type="file" class="form-control" name="attachment">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="start_date" value="{{ old('start_date') }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">End Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="end_date" value="{{ old('end_date') }}" required>
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
                    <button class="btn btn-primary px-4">Submit</button>
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
@if (session('error'))
<script>
    errorMessage("{{ session('error') }}");
</script>
@endif
@endsection
