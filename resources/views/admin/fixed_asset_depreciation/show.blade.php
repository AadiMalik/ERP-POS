@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h4 class="fw-bold py-3 mb-0">{{ __('fixed_asset_depreciations.run_details') }}</h4>
        <a href="{{ url('admin/fixed-asset-depreciation') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3"><strong>Date</strong><div>{{ $depreciation->depreciation_date ? localDate($depreciation->depreciation_date) : '' }}</div></div>
                <div class="col-md-3"><strong>Period</strong><div>{{ $depreciation->period_key }}</div></div>
                <div class="col-md-3"><strong>Status</strong><div>{{ ucfirst($depreciation->status) }}</div></div>
                <div class="col-md-3"><strong>Source</strong><div>{{ ucfirst($depreciation->source) }}</div></div>
                <div class="col-md-3"><strong>Asset</strong>
                    <div>
                        @if($depreciation->fixedAsset)
                        <a href="{{ url('admin/fixed-asset/show/' . $depreciation->fixed_asset_id) }}">
                            {{ $depreciation->fixedAsset->asset_code }} — {{ $depreciation->fixedAsset->name }}
                        </a>
                        @endif
                    </div>
                </div>
                <div class="col-md-3"><strong>Branch</strong><div>{{ $depreciation->branch->name ?? '' }}</div></div>
                <div class="col-md-3"><strong>Business</strong><div>{{ $depreciation->business->name ?? '' }}</div></div>
                <div class="col-md-3"><strong>JV</strong><div>{{ $depreciation->journalEntry->entry_no ?? 'N/A' }}</div></div>
                <div class="col-md-3"><strong>Previous Value</strong><div>{{ currency($depreciation->previous_value) }}</div></div>
                <div class="col-md-3"><strong>Depreciation Amount</strong><div>{{ currency($depreciation->depreciation_amount) }}</div></div>
                <div class="col-md-3"><strong>New Value</strong><div>{{ currency($depreciation->new_value) }}</div></div>
                <div class="col-md-3"><strong>Accumulated</strong><div>{{ currency($depreciation->accumulated_depreciation) }}</div></div>
            </div>
        </div>
        @canAccess('fixed-asset-depreciation.delete')
        <div class="card-footer">
            <button type="button" class="btn btn-danger" id="btn_reverse">Reverse Latest Entry</button>
            <small class="text-muted ms-2">Only the latest depreciation for this asset can be reversed.</small>
        </div>
        @endcanAccess
    </div>
</div>
@endsection
@section('js')
@if(session('success'))
<script>successMessage("{{ session('success') }}");</script>
@endif
<script>
$('#btn_reverse').on('click', function() {
    confirmDelete().then(function(result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: "{{ url('admin/fixed-asset-depreciation/' . $depreciation->fixed_asset_depreciation_id) }}",
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(res) {
                if (res.status) {
                    successMessage(res.message);
                    window.location.href = "{{ url('admin/fixed-asset-depreciation') }}";
                } else {
                    errorMessage(res.message);
                }
            },
            error: function(xhr) {
                errorMessage(xhr.responseJSON?.message || 'Failed to reverse.');
            }
        });
    });
});
</script>
@endsection
