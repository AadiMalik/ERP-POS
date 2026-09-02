@php
    use App\Enums\FixedAssetStatuses;
    use App\Enums\FixedAssetDisposalTypes;
    use App\Enums\FixedAssetTransactionTypes;
    use App\Enums\DepreciationFrequencies;
    use App\Enums\DepreciationAdjustmentModes;

    $statusLabels = FixedAssetStatuses::labels();
    $freqLabels = DepreciationFrequencies::labels();
    $adjLabels = DepreciationAdjustmentModes::labels();
    $txLabels = FixedAssetTransactionTypes::labels();
    $isTerminal = in_array($fixed_asset->depreciation_status, FixedAssetStatuses::terminal());
    $isActive = $fixed_asset->depreciation_status === FixedAssetStatuses::ACTIVE;
    $isPaused = $fixed_asset->depreciation_status === FixedAssetStatuses::PAUSED;
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-4">
        <h4 class="fw-bold py-3 mb-0">
            Fixed Asset: {{ $fixed_asset->asset_code }} — {{ $fixed_asset->name }}
        </h4>
        <div class="d-flex gap-2 flex-wrap">
            <a href="{{ url('admin/fixed-asset') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back
            </a>
            @can('fixed-asset.edit')
            @if (!$isTerminal)
            <a href="{{ url('admin/fixed-asset/edit/' . $fixed_asset->fixed_asset_id) }}" class="btn btn-primary">
                <i class="bx bx-edit"></i> Edit
            </a>
            @endif
            @endcan
            @can('fixed-asset.pause')
            @if ($isActive)
            <button type="button" class="btn btn-warning" id="btn_pause">
                <i class="fa fa-pause"></i> Pause
            </button>
            @endif
            @endcan
            @can('fixed-asset.resume')
            @if ($isPaused)
            <button type="button" class="btn btn-success" id="btn_resume">
                <i class="fa fa-play"></i> Resume
            </button>
            @endif
            @endcan
            @can('fixed-asset.depreciate')
            @if ($isActive)
            <button type="button" class="btn btn-info" id="btn_depreciate">
                <i class="fa fa-calculator"></i> Depreciate Now
            </button>
            @endif
            @endcan
            @can('fixed-asset.transfer')
            @if (!$isTerminal)
            <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#transferModal">
                <i class="fa fa-exchange"></i> Transfer
            </button>
            @endif
            @endcan
            @can('fixed-asset.adjust')
            @if (!$isTerminal)
            <button type="button" class="btn btn-outline-secondary" data-bs-toggle="modal" data-bs-target="#adjustModal">
                <i class="fa fa-sliders"></i> Adjust
            </button>
            @endif
            @endcan
            @can('fixed-asset.dispose')
            @if (!$isTerminal)
            <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#disposeModal">
                <i class="fa fa-trash"></i> Dispose / Sale
            </button>
            @endif
            @endcan
        </div>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Purchase Cost</div>
                    <div class="fs-5 fw-semibold">{{ currency($fixed_asset->purchase_cost) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Current Book Value</div>
                    <div class="fs-5 fw-semibold">{{ currency($fixed_asset->current_book_value) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Previous Book Value</div>
                    <div class="fs-5 fw-semibold">{{ currency($fixed_asset->previous_book_value) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Last Depreciation</div>
                    <div class="fs-5 fw-semibold">{{ currency($fixed_asset->last_depreciation_amount) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Accumulated Depreciation</div>
                    <div class="fs-5 fw-semibold">{{ currency($fixed_asset->accumulated_depreciation) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Residual Value</div>
                    <div class="fs-5 fw-semibold">{{ currency($fixed_asset->residual_value) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Frequency</div>
                    <div class="fs-5 fw-semibold">{{ $freqLabels[$fixed_asset->depreciation_frequency] ?? $fixed_asset->depreciation_frequency }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Status</div>
                    <div class="fs-5 fw-semibold">{{ $statusLabels[$fixed_asset->depreciation_status] ?? $fixed_asset->depreciation_status }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Next Depreciation Date</div>
                    <div class="fs-5 fw-semibold">{{ $fixed_asset->next_depreciation_date ? localDate($fixed_asset->next_depreciation_date) : '—' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">Asset Details</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-4"><strong>Category:</strong> {{ $fixed_asset->category->name ?? '—' }}</div>
                <div class="col-md-4"><strong>Branch:</strong> {{ $fixed_asset->branch->name ?? '—' }}</div>
                <div class="col-md-4"><strong>Location:</strong> {{ $fixed_asset->location ?: '—' }}</div>
                <div class="col-md-4"><strong>Serial:</strong> {{ $fixed_asset->serial_number ?: '—' }}</div>
                <div class="col-md-4"><strong>Purchase Date:</strong> {{ $fixed_asset->purchase_date ? localDate($fixed_asset->purchase_date) : '—' }}</div>
                <div class="col-md-4"><strong>Useful Life:</strong> {{ $fixed_asset->useful_life_years }} years</div>
                <div class="col-md-4"><strong>Method:</strong> Straight Line</div>
                <div class="col-md-4"><strong>Adjustment:</strong> {{ $adjLabels[$fixed_asset->depreciation_adjustment_mode] ?? $fixed_asset->depreciation_adjustment_mode }}</div>
                <div class="col-md-4"><strong>Supplier:</strong> {{ $fixed_asset->supplier->name ?? '—' }}</div>
                <div class="col-md-12"><strong>Description:</strong> {{ $fixed_asset->description ?: '—' }}</div>
            </div>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">Depreciation History</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Period</th>
                        <th class="text-end">Previous</th>
                        <th class="text-end">Amount</th>
                        <th class="text-end">New Value</th>
                        <th class="text-end">Accumulated</th>
                        <th>JV</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fixed_asset->depreciations as $dep)
                    <tr>
                        <td>{{ $dep->depreciation_date ? localDate($dep->depreciation_date) : '' }}</td>
                        <td>{{ $dep->period_key }}</td>
                        <td class="text-end">{{ currency($dep->previous_value) }}</td>
                        <td class="text-end">{{ currency($dep->depreciation_amount) }}</td>
                        <td class="text-end">{{ currency($dep->new_value) }}</td>
                        <td class="text-end">{{ currency($dep->accumulated_depreciation) }}</td>
                        <td>
                            @if ($dep->journal_entry_id)
                            <a href="{{ url('admin/journal-entry/show/' . $dep->journal_entry_id) }}" target="_blank">
                                {{ $dep->journalEntry->entry_no ?? $dep->journal_entry_id }}
                            </a>
                            @else
                            —
                            @endif
                        </td>
                        <td>{{ $dep->status }}</td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="8" class="text-center text-muted">No depreciation records</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div class="card mb-4">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">Transaction History</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-sm">
                <thead>
                    <tr>
                        <th>Date</th>
                        <th>Type</th>
                        <th>Description</th>
                        <th class="text-end">Amount</th>
                        <th>JV</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($fixed_asset->transactions as $tx)
                    <tr>
                        <td>{{ $tx->transaction_date ? localDate($tx->transaction_date) : '' }}</td>
                        <td>{{ $txLabels[$tx->transaction_type] ?? $tx->transaction_type }}</td>
                        <td>{{ $tx->description }}</td>
                        <td class="text-end">{{ $tx->amount !== null ? currency($tx->amount) : '—' }}</td>
                        <td>
                            @if ($tx->journal_entry_id)
                            <a href="{{ url('admin/journal-entry/show/' . $tx->journal_entry_id) }}" target="_blank">
                                {{ $tx->journalEntry->entry_no ?? $tx->journal_entry_id }}
                            </a>
                            @else
                            —
                            @endif
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="5" class="text-center text-muted">No transactions</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

{{-- Transfer Modal --}}
<div class="modal fade" id="transferModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Transfer Asset</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">To Branch</label>
                    <select class="form-select" id="transfer_branch_id">
                        <option value="">-- Keep Current / Select --</option>
                        @foreach ($branches as $item)
                        <option value="{{ $item->branch_id }}" {{ $fixed_asset->branch_id == $item->branch_id ? 'selected' : '' }}>
                            {{ $item->name }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Location</label>
                    <input type="text" class="form-control" id="transfer_location" value="{{ $fixed_asset->location }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" id="transfer_remarks" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn_transfer_save">Transfer</button>
            </div>
        </div>
    </div>
</div>

{{-- Adjust Modal --}}
<div class="modal fade" id="adjustModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Adjust Depreciation Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Adjustment Mode</label>
                    <select class="form-select" id="adjust_mode">
                        @foreach ($adjustment_modes as $value => $label)
                        <option value="{{ $value }}" {{ $fixed_asset->depreciation_adjustment_mode == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Adjustment Rate %</label>
                    <input type="number" class="form-control" id="adjust_rate" min="0" max="100" step="0.01"
                        value="{{ $fixed_asset->depreciation_adjustment_rate }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Min Book Value %</label>
                    <input type="number" class="form-control" id="adjust_min_book" min="0" max="100" step="0.01"
                        value="{{ $fixed_asset->min_book_value_percent }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Residual Value</label>
                    <input type="number" class="form-control" id="adjust_residual" min="0" step="0.01"
                        value="{{ $fixed_asset->residual_value }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Frequency</label>
                    <select class="form-select" id="adjust_frequency">
                        @foreach ($frequencies as $value => $label)
                        <option value="{{ $value }}" {{ $fixed_asset->depreciation_frequency == $value ? 'selected' : '' }}>
                            {{ $label }}
                        </option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Useful Life (Years)</label>
                    <input type="number" class="form-control" id="adjust_life" min="1" max="100"
                        value="{{ $fixed_asset->useful_life_years }}">
                </div>
                <div class="mb-3">
                    <label class="form-label">Remarks</label>
                    <textarea class="form-control" id="adjust_remarks" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="btn_adjust_save">Save Adjustment</button>
            </div>
        </div>
    </div>
</div>

{{-- Dispose Modal --}}
<div class="modal fade" id="disposeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Dispose / Sale</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label">Disposal Type <span class="text-danger">*</span></label>
                    <select class="form-select" id="disposal_type">
                        @foreach ($disposal_types as $value => $label)
                        <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Disposal Date <span class="text-danger">*</span></label>
                    <input type="text" class="form-control datepicker" id="disposal_date" value="{{ localDate(date('Y-m-d')) }}">
                </div>
                <div class="mb-3" id="sale_price_wrap" style="display:none;">
                    <label class="form-label">Sale Price <span class="text-danger">*</span></label>
                    <input type="number" class="form-control" id="sale_price" min="0" step="0.01">
                </div>
                <div class="mb-3">
                    <label class="form-label">Proceeds Account</label>
                    <select class="form-select" id="disposal_proceeds_account_id">
                        <option value="">-- Select Account --</option>
                        @foreach ($accounts as $item)
                        <option value="{{ $item->account_id }}">{{ $item->code ?? '' }} {{ $item->name ?? '' }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label">Disposal Reason</label>
                    <textarea class="form-control" id="disposal_reason" rows="2"></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="btn_dispose_save">Confirm Disposal</button>
            </div>
        </div>
    </div>
</div>
@endsection

@section('js')
<script>
    const assetId = @json($fixed_asset->fixed_asset_id);
    const csrfToken = $('meta[name="csrf-token"]').attr('content');
    const saleType = @json(FixedAssetDisposalTypes::SALE);

    function assetAction(url, data, reloadOnSuccess) {
        ajaxRequest({
                url: url,
                method: 'POST',
                data: Object.assign({ _token: csrfToken }, data || {})
            })
            .then((response) => {
                successMessage(response.Message || 'Success');
                if (reloadOnSuccess !== false) {
                    setTimeout(() => location.reload(), 800);
                }
            })
            .catch((err) => {
                errorMessage(err.Message || 'Action failed');
            });
    }

    $('#btn_pause').click(function() {
        Swal.fire({
            title: 'Pause depreciation?',
            input: 'text',
            inputPlaceholder: 'Reason (optional)',
            showCancelButton: true,
            confirmButtonText: 'Pause'
        }).then((result) => {
            if (result.isConfirmed) {
                assetAction(url_local + '/admin/fixed-asset/' + assetId + '/pause', { reason: result.value || '' });
            }
        });
    });

    $('#btn_resume').click(function() {
        Swal.fire({
            title: 'Resume depreciation?',
            input: 'text',
            inputPlaceholder: 'Reason (optional)',
            showCancelButton: true,
            confirmButtonText: 'Resume'
        }).then((result) => {
            if (result.isConfirmed) {
                assetAction(url_local + '/admin/fixed-asset/' + assetId + '/resume', { reason: result.value || '' });
            }
        });
    });

    $('#btn_depreciate').click(function() {
        Swal.fire({
            title: 'Post depreciation now?',
            icon: 'question',
            showCancelButton: true,
            confirmButtonText: 'Depreciate'
        }).then((result) => {
            if (result.isConfirmed) {
                assetAction(url_local + '/admin/fixed-asset/' + assetId + '/depreciate', {});
            }
        });
    });

    $('#btn_transfer_save').click(function() {
        assetAction(url_local + '/admin/fixed-asset/' + assetId + '/transfer', {
            branch_id: $('#transfer_branch_id').val(),
            location: $('#transfer_location').val(),
            remarks: $('#transfer_remarks').val()
        });
    });

    $('#btn_adjust_save').click(function() {
        assetAction(url_local + '/admin/fixed-asset/' + assetId + '/adjust', {
            depreciation_adjustment_mode: $('#adjust_mode').val(),
            depreciation_adjustment_rate: $('#adjust_rate').val(),
            min_book_value_percent: $('#adjust_min_book').val(),
            residual_value: $('#adjust_residual').val(),
            depreciation_frequency: $('#adjust_frequency').val(),
            useful_life_years: $('#adjust_life').val(),
            remarks: $('#adjust_remarks').val()
        });
    });

    function toggleSalePrice() {
        if ($('#disposal_type').val() === saleType) {
            $('#sale_price_wrap').show();
        } else {
            $('#sale_price_wrap').hide();
        }
    }
    $('#disposal_type').on('change', toggleSalePrice);
    toggleSalePrice();

    $('#btn_dispose_save').click(function() {
        Swal.fire({
            title: 'Confirm disposal?',
            text: 'This action cannot be undone.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonText: 'Dispose'
        }).then((result) => {
            if (result.isConfirmed) {
                assetAction(url_local + '/admin/fixed-asset/' + assetId + '/dispose', {
                    disposal_type: $('#disposal_type').val(),
                    disposal_date: $('#disposal_date').val(),
                    sale_price: $('#sale_price').val(),
                    disposal_reason: $('#disposal_reason').val(),
                    disposal_proceeds_account_id: $('#disposal_proceeds_account_id').val()
                });
            }
        });
    });
</script>
@endsection
