@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">{{ __('manufacturing.plans_title') }} {{ $plan->plan_no }} - {{ $plan->productVariation->name ?? $plan->product->name ?? '' }}</h4>
        <div class="d-flex gap-2">
            @can('manufacturing-plan.confirm')
            @if ($plan->status === 'draft')
            <button type="button" class="btn btn-success" id="confirmPlanBtn" data-id="{{ $plan->manufacturing_plan_id }}">
                <i class="fa fa-check"></i> Approve &amp; Reserve Materials
            </button>
            @endif
            @endcan
            @can('production.create')
            @if ($plan->status === 'not_complete')
            <a href="{{ url('admin/production/create?manufacturing_plan_id=' . $plan->manufacturing_plan_id) }}" class="btn btn-primary">
                <i class="fa fa-plus"></i> New Production
            </a>
            @endif
            @endcan
            @can('manufacturing-plan.cancel')
            @if (!in_array($plan->status, ['completed', 'cancelled']))
            <button type="button" class="btn btn-outline-danger" id="cancelPlanBtn" data-id="{{ $plan->manufacturing_plan_id }}">
                <i class="fa fa-times"></i> Cancel Plan
            </button>
            @endif
            @endcan
        </div>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Plan Date</small><div class="fw-bold">{{ $plan->plan_date ?? '-' }}</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Status</small><div class="fw-bold">{{ ucwords(str_replace('_', ' ', $plan->status)) }}</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Planned Qty</small><div class="fw-bold">{{ $plan->planned_quantity }}</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Produced Qty</small><div class="fw-bold">{{ $plan->produced_quantity }}</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Remaining Qty</small><div class="fw-bold">{{ $plan->remaining_quantity }}</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Progress</small><div class="fw-bold">{{ $plan->progress_percentage }}%</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Recipe</small><div class="fw-bold">{{ $plan->recipe ? $plan->recipe->items->count() . ' raw material(s)' : '-' }}</div></div></div>
        <div class="col-md-2"><div class="card p-3"><small class="text-muted">Approved By</small><div class="fw-bold">{{ $plan->approvedby->name ?? '-' }}</div></div></div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Reserved Raw Materials</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Raw Material</th><th>Warehouse</th><th>Required</th><th>Reserved</th><th>Consumed</th><th>Outstanding Reserved</th><th>Unit</th></tr></thead>
                <tbody>
                    @forelse ($plan->materials as $material)
                    <tr>
                        <td>{{ $material->productVariation->name ?? '-' }}</td>
                        <td>{{ $material->warehouse->name ?? '-' }}</td>
                        <td>{{ $material->required_base_quantity }}</td>
                        <td>{{ $material->reserved_quantity }}</td>
                        <td>{{ $material->consumed_quantity }}</td>
                        <td>{{ $material->outstanding_reserved_quantity }}</td>
                        <td>{{ $material->unit->name ?? '-' }}</td>
                    </tr>
                    @empty
                    <tr><td colspan="7" class="text-center text-muted">Not confirmed yet - no materials reserved.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header"><h6 class="mb-0">Productions</h6></div>
        <div class="card-body table-responsive">
            <table class="table table-bordered">
                <thead><tr><th>Production #</th><th>Warehouse</th><th>Batch</th><th>Qty</th><th>Mfg. Date</th><th>Expiry</th><th>Status</th><th></th></tr></thead>
                <tbody>
                    @forelse ($plan->productions as $production)
                    <tr>
                        <td>{{ $production->production_no }}</td>
                        <td>{{ $production->warehouse->name ?? '-' }}</td>
                        <td>{{ $production->batch_no ?? '-' }}</td>
                        <td>{{ $production->quantity }}</td>
                        <td>{{ $production->manufacturing_date }}</td>
                        <td>{{ $production->expiry_date ?? '-' }}</td>
                        <td>{{ ucfirst($production->status) }}</td>
                        <td><a href="{{ url('admin/production/show/' . $production->production_id) }}" class="btn btn-sm btn-icon btn-info"><i class="fa fa-eye"></i></a></td>
                    </tr>
                    @empty
                    <tr><td colspan="8" class="text-center text-muted">No productions yet.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <a href="{{ url('admin/manufacturing-plan') }}" class="btn btn-outline-secondary">Back</a>
</div>
@endsection
@section('js')
<script>
    $('#confirmPlanBtn').click(function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Confirm this plan and reserve raw materials now?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Confirm'
        }).then((result) => {
            if (!result.isConfirmed) return;
            ajaxRequest({ url: url_local + '/admin/manufacturing-plan/' + id + '/confirm', method: 'POST', data: {} })
                .then((response) => { successMessage(response.Message); location.reload(); })
                .catch((err) => errorMessage(err.Message));
        });
    });
    $('#cancelPlanBtn').click(function() {
        const id = $(this).data('id');
        Swal.fire({
            title: 'Cancel this plan?',
            input: 'text',
            inputPlaceholder: 'Reason (optional)',
            showCancelButton: true,
            confirmButtonText: 'Cancel Plan'
        }).then((result) => {
            if (!result.isConfirmed) return;
            ajaxRequest({ url: url_local + '/admin/manufacturing-plan/' + id + '/cancel', method: 'POST', data: { cancel_reason: result.value || '' } })
                .then((response) => { successMessage(response.Message); location.reload(); })
                .catch((err) => errorMessage(err.Message));
        });
    });
</script>
@endsection
