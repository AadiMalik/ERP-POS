@php
    use App\Enums\SerialStatus;
@endphp

@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center py-3 mb-4">
            <h4 class="fw-bold mb-0">{{ __('serial_numbers.singular') }}: {{ $serial['serial_no'] }}</h4>
            <a href="{{ url('admin/serial-number') }}" class="btn btn-outline-secondary">
                <i class="fa fa-arrow-left"></i> Back to Search
            </a>
        </div>

        <div class="row">
            <div class="col-md-8">
                <div class="card mb-4">
                    <div class="card-header"><h5 class="mb-0">Details</h5></div>
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label text-muted">Product</label>
                                <div class="fw-semibold">{{ $serial['product_name'] }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Variation</label>
                                <div class="fw-semibold">{{ $serial['variation_name'] }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Status</label>
                                <div>
                                    <span class="badge {{ [
                                        SerialStatus::AVAILABLE => 'bg-success',
                                        SerialStatus::SOLD => 'bg-primary',
                                        SerialStatus::IN_TRANSIT => 'bg-info',
                                        SerialStatus::UNDER_REPAIR => 'bg-warning',
                                    ][$serial['status']] ?? 'bg-secondary' }}">{{ $serial['status_label'] }}</span>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Current Location</label>
                                <div class="fw-semibold">{{ $serial['warehouse_name'] }}</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Purchase / Receiving Info</label>
                                <div class="fw-semibold">
                                    {{ $serial['source_reference_type'] ? ucfirst(str_replace('_', ' ', $serial['source_reference_type'])) . ' - ' . $serial['source_doc_no'] : 'Added manually' }}
                                </div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label text-muted">Unit Cost</label>
                                <div class="fw-semibold">{{ number_format($serial['avg_price'], 2) }}</div>
                            </div>
                            @if ($serial['current_order_id'])
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Sale Info</label>
                                    <div class="fw-semibold">Order #{{ $serial['current_order_daily_id'] }}</div>
                                </div>
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Customer</label>
                                    <div class="fw-semibold">{{ $serial['current_customer_name'] ?? '-' }}</div>
                                </div>
                            @endif
                            @if ($serial['warranty_expires_at'])
                                <div class="col-md-6">
                                    <label class="form-label text-muted">Warranty Until</label>
                                    <div class="fw-semibold">{{ localDate($serial['warranty_expires_at']) }}</div>
                                </div>
                            @endif
                            <div class="col-md-6">
                                <label class="form-label text-muted">Received On</label>
                                <div class="fw-semibold">{{ $serial['date_created'] ? localDate($serial['date_created']) : '-' }}</div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header"><h5 class="mb-0">Movement History</h5></div>
                    <div class="card-body">
                        @if (empty($serial['movements']))
                            <p class="text-muted mb-0">No movement history yet.</p>
                        @else
                            <div class="table-responsive">
                                <table class="table table-sm">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Event</th>
                                            <th>From</th>
                                            <th>To</th>
                                            <th>By</th>
                                            <th>Notes</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach ($serial['movements'] as $movement)
                                            <tr>
                                                <td>{{ $movement['date_created'] ? localDate($movement['date_created']) : '-' }}</td>
                                                <td>{{ $movement['event_label'] }}</td>
                                                <td>{{ $movement['from_warehouse_name'] ?? '-' }}</td>
                                                <td>{{ $movement['to_warehouse_name'] ?? '-' }}</td>
                                                <td>{{ $movement['createdby_name'] ?: '-' }}</td>
                                                <td>{{ $movement['notes'] ?? '-' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="col-md-4">
                @canAccess('serial-number.edit')
                    <div class="card">
                        <div class="card-header"><h5 class="mb-0">Quick Actions</h5></div>
                        <div class="card-body d-flex flex-column gap-2">
                            @if ($serial['status'] === SerialStatus::AVAILABLE)
                                <button type="button" class="btn btn-outline-warning" id="sendForRepairBtn">
                                    <i class="fa fa-wrench"></i> Send for Repair
                                </button>
                            @endif
                            @if ($serial['status'] === SerialStatus::UNDER_REPAIR)
                                <button type="button" class="btn btn-outline-success" id="returnFromRepairBtn">
                                    <i class="fa fa-check"></i> Return from Repair
                                </button>
                            @endif
                            @if (in_array($serial['status'], [SerialStatus::AVAILABLE, SerialStatus::SOLD]))
                                <button type="button" class="btn btn-outline-danger" id="replaceSerialBtn">
                                    <i class="fa fa-arrows-rotate"></i> Replace This Unit
                                </button>
                            @endif
                            @can('waste-damage-expiry.create')
                                <a href="{{ url('admin/waste-damage-expiry/create') }}" class="btn btn-outline-secondary">
                                    <i class="fa fa-triangle-exclamation"></i> Mark Lost / Damaged
                                </a>
                            @endcan
                        </div>
                    </div>
                @endcanAccess
            </div>
        </div>
    </div>

    <div class="modal fade" id="replaceSerialModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Replace This Unit</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-muted">Retires this serial number and, if it is currently sold, hands the same
                        order/customer a replacement unit instead.</p>
                    <div class="mb-3">
                        <label class="form-label">Replacement {{ __('serial_numbers.singular') }}</label>
                        <select id="replace_new_serial_id" class="form-select">
                            <option value="">--Select an available serial--</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Notes</label>
                        <textarea class="form-control" id="replace_notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="replaceSaveBtn">Replace</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('js')
    <script>
        const SERIAL_ID = "{{ $serial['product_variation_serial_number_id'] }}";
        const SERIAL_VARIATION_ID = "{{ $serial['product_variation_id'] }}";

        $('#sendForRepairBtn').on('click', function() {
            let notes = prompt('Notes (optional):') || '';
            ajaxRequest({
                url: url_local + '/admin/serial-number/' + SERIAL_ID + '/send-for-repair',
                method: 'POST',
                data: { notes: notes },
            }).then(function(res) {
                successMessage(res.Message);
                location.reload();
            }).catch(function(err) {
                errorMessage(err.Message || 'Unable to send this unit for repair.');
            });
        });

        $('#returnFromRepairBtn').on('click', function() {
            let notes = prompt('Notes (optional):') || '';
            ajaxRequest({
                url: url_local + '/admin/serial-number/' + SERIAL_ID + '/return-from-repair',
                method: 'POST',
                data: { notes: notes },
            }).then(function(res) {
                successMessage(res.Message);
                location.reload();
            }).catch(function(err) {
                errorMessage(err.Message || 'Unable to return this unit from repair.');
            });
        });

        var replaceSerialModal = null;

        $('#replaceSerialBtn').on('click', function() {
            $('#replace_notes').val('');
            $('#replace_new_serial_id').html('<option value="">Loading...</option>');

            replaceSerialModal = replaceSerialModal || new bootstrap.Modal(document.getElementById('replaceSerialModal'));
            replaceSerialModal.show();

            ajaxRequest({
                url: url_local + '/admin/serial-number/by-variation/' + SERIAL_VARIATION_ID,
            }).then(function(response) {
                let options = '<option value="">--Select an available serial--</option>';
                (response.Data || []).forEach(function(s) {
                    if (s.product_variation_serial_number_id !== SERIAL_ID) {
                        options += `<option value="${s.product_variation_serial_number_id}">${s.serial_no}</option>`;
                    }
                });
                $('#replace_new_serial_id').html(options);
            });
        });

        $('#replaceSaveBtn').on('click', function() {
            let newSerialId = $('#replace_new_serial_id').val();
            if (!newSerialId) {
                errorMessage('Please select a replacement serial number.');
                return;
            }

            ajaxRequest({
                url: url_local + '/admin/serial-number/' + SERIAL_ID + '/replace',
                method: 'POST',
                data: { new_serial_id: newSerialId, notes: $('#replace_notes').val() },
            }).then(function(res) {
                successMessage(res.Message);
                replaceSerialModal.hide();
                window.location.href = url_local + '/admin/serial-number/' + newSerialId;
            }).catch(function(err) {
                errorMessage(err.Message || 'Unable to replace this unit.');
            });
        });
    </script>
@endsection
