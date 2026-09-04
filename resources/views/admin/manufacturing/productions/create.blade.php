@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ isset($production) ? 'Edit' : 'New' }} Production</h4>
    <form id="productionForm" method="POST" action="{{ url('admin/production/store') }}">
        @csrf
        <input type="hidden" name="production_id" value="{{ $production->production_id ?? '' }}">
        <div class="card mb-4">
            <div class="card-body row g-3">
                <div class="col-md-6">
                    <label class="form-label">Manufacturing Plan <span class="text-danger">*</span></label>
                    <select name="manufacturing_plan_id" id="manufacturing_plan_id" class="form-select" required {{ isset($production) ? 'disabled' : '' }}>
                        <option value="">--Select--</option>
                        @if ($plan ?? $production->plan ?? null)
                        @php($p = $plan ?? $production->plan)
                        <option value="{{ $p->manufacturing_plan_id }}" data-remaining="{{ $p->remaining_quantity }}" selected>{{ $p->plan_no }} - {{ $p->productVariation->name ?? '' }} (Remaining: {{ $p->remaining_quantity }})</option>
                        @endif
                    </select>
                    @if (isset($production))
                    <input type="hidden" name="manufacturing_plan_id" value="{{ $production->manufacturing_plan_id }}">
                    @endif
                    <small class="text-muted">Only plans with materials already reserved (Approved, not yet fully produced) are listed.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Remaining Quantity</label>
                    <input type="text" id="remainingQtyDisplay" class="form-control" readonly value="{{ $p->remaining_quantity ?? '' }}">
                </div>
                <div class="col-md-6">
                    <label class="form-label">Finished-Goods Warehouse <span class="text-danger">*</span></label>
                    <select name="warehouse_id" class="form-select" required>
                        <option value="">--Select--</option>
                        @foreach ($warehouses as $item)
                        <option value="{{ $item->warehouse_id }}" {{ ($production->warehouse_id ?? null) == $item->warehouse_id ? 'selected' : '' }}>{{ $item->name }}{{ $item->branch ? ' (' . $item->branch->name . ')' : ' (Shared)' }}</option>
                        @endforeach
                    </select>
                    <small class="text-muted">Independent per production - different batches can go to different branches/warehouses.</small>
                </div>
                <div class="col-md-6">
                    <label class="form-label">Quantity to Produce <span class="text-danger">*</span></label>
                    <input type="number" step="0.0001" name="quantity" id="produceQty" class="form-control" value="{{ $production->quantity ?? '' }}" required>
                    <small class="text-muted">Cannot exceed the plan's remaining quantity.</small>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Manufacturing Date <span class="text-danger">*</span></label>
                    <input type="date" name="manufacturing_date" class="form-control" value="{{ $production->manufacturing_date ?? date('Y-m-d') }}" required>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Expiry Date (if applicable)</label>
                    <input type="date" name="expiry_date" class="form-control" value="{{ $production->expiry_date ?? '' }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Batch / Lot No.</label>
                    <input type="text" class="form-control" value="{{ $production->batch_no ?? 'Auto-generated on save' }}" readonly disabled>
                </div>
                <div class="col-md-4">
                    <label class="form-label">Labor Cost</label>
                    <input type="number" step="0.01" name="labor_cost" class="form-control" value="{{ $production->labor_cost ?? 0 }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Overhead Cost</label>
                    <input type="number" step="0.01" name="overhead_cost" class="form-control" value="{{ $production->overhead_cost ?? 0 }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label">Other Cost</label>
                    <input type="number" step="0.01" name="other_cost" class="form-control" value="{{ $production->other_cost ?? 0 }}">
                </div>
                <div class="col-md-12">
                    <label class="form-label">Notes</label>
                    <textarea name="notes" class="form-control" rows="2">{{ $production->notes ?? '' }}</textarea>
                </div>
            </div>
        </div>
        <div class="text-end mb-4">
            <a href="{{ url('admin/production') }}" class="btn btn-outline-secondary">Cancel</a>
            <button type="submit" id="saveProductionBtn" class="btn btn-primary">Save Production (Draft)</button>
        </div>
    </form>
</div>
@endsection
@section('js')
<script>
    function loadEligiblePlans(selectedId) {
        ajaxRequest({ url: url_local + '/admin/manufacturing-plan/eligible', data: {} })
            .then((response) => {
                let options = '<option value="">--Select--</option>';
                (response.Data || []).forEach(p => {
                    options += `<option value="${p.manufacturing_plan_id}" data-remaining="${p.remaining_quantity}" ${selectedId == p.manufacturing_plan_id ? 'selected' : ''}>${p.plan_no} - ${p.product_name} (Remaining: ${p.remaining_quantity})</option>`;
                });
                $('#manufacturing_plan_id').html(options);
                updateRemainingDisplay();
            });
    }

    function updateRemainingDisplay() {
        const remaining = $('#manufacturing_plan_id').find(':selected').data('remaining');
        $('#remainingQtyDisplay').val(remaining ?? '');
        $('#produceQty').attr('max', remaining ?? '');
    }

    $('#manufacturing_plan_id').on('change', updateRemainingDisplay);

    $('#productionForm').submit(function(e) {
        const remaining = parseFloat($('#manufacturing_plan_id').find(':selected').data('remaining'));
        const qty = parseFloat($('#produceQty').val()) || 0;
        if (!isNaN(remaining) && qty > remaining) {
            e.preventDefault();
            errorMessage('Quantity cannot exceed the plan\'s remaining quantity (' + remaining + ').');
            return false;
        }
    });

    $(document).ready(function() {
        $('#warehouse_id, select[name="warehouse_id"]').select2();
        @if (!isset($production))
        $('#manufacturing_plan_id').select2();
        loadEligiblePlans('{{ $plan->manufacturing_plan_id ?? '' }}');
        @endif
    });
</script>
@endsection
