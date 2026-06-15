@extends('layouts.app')
@section('css')
@endsection
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">Warehouse</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ isset($warehouse) ? 'Update' : 'New' }} Warehouse</h5>
        </div>

        <form action="{{ url('admin/warehouse') }}" method="POST" enctype="multipart/form-data">
            @csrf
            <div class="card-body">
                <input type="hidden" name="warehouse_id" value="{{ isset($warehouse) ? $warehouse->warehouse_id : '' }}">

                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="fw-semibold">Name <span
                                class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name"
                            value="{{ $warehouse->name ?? '' }}" required>
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Code</label>
                        <input type="text" class="form-control" name="code"
                            value="{{ $warehouse->code ?? '' }}">
                    </div>
                    <div class="col-md-6">
                        <label class="fw-semibold">Phone</label>
                        <input type="text" class="form-control" name="phone"
                            value="{{ $warehouse->phone ?? '' }}">
                    </div>
                    @if (!empty($business))
                    <div class="col-md-6">
                        <label class="fw-semibold">
                            Business <span class="text-danger">*</span>
                        </label>
                        <select class="form-select" name="business_id" id="business_id" required>
                            <option value="">-- Select Business --</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}"
                                {{ old('business_id', $warehouse->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                {{ $item->code }} {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif
                    <div class="col-md-6">
                        <label class="fw-semibold">Branch</label>
                        <select name="branch_id" id="branch_id" class="form-control">
                            <option value="">--Select Branch--</option>
                            @foreach ($branches as $item)
                            <option value="{{ $item->business_id }}"
                                {{ old('branch_id', $user->branch_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-12">
                        <label class="fw-semibold">Address</label>
                        <textarea class="form-control" name="address" rows="2">{{ $warehouse->address ?? '' }}</textarea>
                    </div>
                </div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary"
                        onclick="window.history.back()">Cancel</button>
                    <button class="btn btn-primary px-4">Save Warehouse</button>
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
    errorMessage(
        "{{ session('error') }}"
    );
</script>
@endif
<script>
    $(document).ready(function() {
        $('#business_id').select2();
    });
</script>
@endsection