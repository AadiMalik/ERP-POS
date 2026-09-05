@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">{{ __('manufacturing.productions_title') }} {{ $production->production_no }}</h4>
        <div class="d-flex gap-2">
            @can('production.complete')
            @if ($production->status === 'draft')
            <button type="button" class="btn btn-success" id="completeBtn" data-id="{{ $production->production_id }}">
                <i class="fa fa-check"></i> Approve / Complete Production
            </button>
            @endif
            @endcan
            @can('production.cancel')
            @if ($production->status !== 'cancelled')
            <button type="button" class="btn btn-outline-danger" id="cancelBtn" data-id="{{ $production->production_id }}">
                <i class="fa fa-times"></i> {{ $production->status === 'completed' ? 'Void / Revert' : 'Cancel' }}
            </button>
            @endif
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Status</small><div class="fw-bold">{{ ucwords(str_replace('_', ' ', $production->status)) }}</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Quantity</small><div class="fw-bold">{{ $production->quantity }}</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Batch No.</small><div class="fw-bold">{{ $production->batch_no ?? '-' }}</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Expiry</small><div class="fw-bold">{{ $production->expiry_date ?? '-' }}</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Warehouse</small><div class="fw-bold">{{ $production->warehouse->name ?? '-' }}</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Unit Cost</small><div class="fw-bold">{{ currency($production->unit_cost) }}</div></div></div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3"><div class="card p-3"><small class="text-muted">Material Cost</small><div class="fw-bold">{{ currency($production->material_cost) }}</div></div></div>
        <div class="col-md-3"><div class="card p-3"><small class="text-muted">Labor Cost</small><div class="fw-bold">{{ currency($production->labor_cost) }}</div></div></div>
        <div class="col-md-3"><div class="card p-3"><small class="text-muted">Overhead + Other</small><div class="fw-bold">{{ currency($production->overhead_cost + $production->other_cost) }}</div></div></div>
        <div class="col-md-3"><div class="card p-3"><small class="text-muted">Total Cost</small><div class="fw-bold">{{ currency($production->total_cost) }}</div></div></div>
    </div>

    @if ($production->plan)
    <div class="mb-4">
        <a href="{{ url('admin/manufacturing-plan/show/' . $production->manufacturing_plan_id) }}">
            <i class="fa fa-arrow-left"></i> Parent Plan: {{ $production->plan->plan_no }}
        </a>
    </div>
    @endif

    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Raw Material Consumption (Traceability)</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Raw Material</th><th>Batch Consumed</th><th>Qty</th><th>Unit Cost</th><th>Total Cost</th></tr></thead>
                <tbody>
                    @forelse ($production->consumptions as $c)
                    <tr>
                        <td>{{ $c->productVariation->name ?? '-' }}</td>
                        <td>{{ $c->batch->batch_no ?? '-' }}</td>
                        <td>{{ $c->base_quantity }}</td>
                        <td>{{ currency($c->unit_cost) }}</td>
                        <td>{{ currency($c->total_cost) }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="5" class="text-center text-muted">Not completed yet - no consumption recorded.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ url('admin/production') }}" class="btn btn-outline-secondary">Back</a>
</div>
@endsection
@section('js')
<script>
    $('#completeBtn').click(function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Approve this production?',
            text: 'This will consume raw materials and add finished goods to stock.',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Approve'
        }).then((result) => {
            if (!result.isConfirmed) return;
            ajaxRequest({ url: url_local + '/admin/production/' + id + '/complete', method: 'POST', data: {} })
                .then((response) => { successMessage(response.Message); location.reload(); })
                .catch((err) => errorMessage(err.Message));
        });
    });
    $('#cancelBtn').click(function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Cancel / revert this production?',
            input: 'text',
            inputPlaceholder: 'Reason (optional)',
            showCancelButton: true,
            confirmButtonText: 'Cancel Production'
        }).then((result) => {
            if (!result.isConfirmed) return;
            ajaxRequest({ url: url_local + '/admin/production/' + id + '/cancel', method: 'POST', data: { cancel_reason: result.value || '' } })
                .then((response) => { successMessage(response.Message); location.reload(); })
                .catch((err) => errorMessage(err.Message));
        });
    });
</script>
@endsection
