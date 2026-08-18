@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Generate Payroll</h4>

    <div class="card">
        <form action="{{ url('admin/payroll') }}" method="POST">
            @csrf
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">Month <span class="text-danger">*</span></label>
                        <select name="month" class="form-select" required>
                            @foreach (range(1, 12) as $m)
                            <option value="{{ $m }}" {{ now()->month == $m ? 'selected' : '' }}>{{ date('F', mktime(0, 0, 0, $m, 1)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Year <span class="text-danger">*</span></label>
                        <select name="year" class="form-select" required>
                            @foreach (range(now()->year, now()->year - 3) as $y)
                            <option value="{{ $y }}" {{ now()->year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <small class="form-text text-muted d-block mt-3">
                    Generating is safe to re-run while the payroll is still a draft - it will recompute every payslip
                    from the latest attendance, leave, salary structure, deduction and advance data. Finalize the run
                    once the numbers look correct.
                </small>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Generate</button>
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
@if(session('error'))
<script>
    errorMessage("{{ session('error') }}");
</script>
@endif
@endsection
