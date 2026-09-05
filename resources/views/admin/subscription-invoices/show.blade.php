@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center py-3 mb-4">
            <h4 class="fw-bold mb-0">Invoice {{ $invoice->invoice_no }}</h4>
            <div>
                <a href="{{ route('subscription-invoices.pdf', $invoice->subscription_invoice_id) }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="fa fa-file-pdf"></i> Print / PDF
                </a>
                @if ($invoice->status !== 'void')
                    <button type="button" id="voidBtn" class="btn btn-outline-danger">Void Invoice</button>
                @endif
                <button type="button" id="deleteBtn" class="btn btn-outline-danger">{{ __('common.delete') }}</button>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light"><h6 class="mb-0">Invoice Details</h6></div>
                    <div class="card-body">
                        <p><strong>Business:</strong> {{ $invoice->business->name ?? '-' }}</p>
                        <p><strong>Business Status:</strong> {{ ucwords(str_replace('_', ' ', $invoice->business->status ?? '-')) }}</p>
                        <p><strong>Package:</strong> {{ $invoice->package->name ?? '-' }}</p>
                        <p>
                            <strong>Type:</strong>
                            @php $type = $invoice->request_type ?: 'new'; @endphp
                            <span class="badge bg-label-{{ $type === 'renew' ? 'info' : 'primary' }}">{{ $type === 'renew' ? 'Renew' : 'New' }}</span>
                        </p>
                        <p><strong>Billing Cycle:</strong> {{ ucfirst($invoice->billing_cycle) }}</p>
                        <p><strong>Period:</strong> {{ localDate($invoice->period_start) }} - {{ localDate($invoice->period_end) }}</p>
                        <p><strong>Status:</strong> {{ ucwords(str_replace('_', ' ', $invoice->status)) }}</p>
                        <p><strong>Subtotal:</strong> {{ currency($invoice->subtotal) }}</p>
                        <p><strong>Discount:</strong> {{ currency($invoice->discount_amount) }}</p>
                        <p><strong>Tax:</strong> {{ currency($invoice->tax_amount) }}</p>
                        <p><strong>Total:</strong> {{ currency($invoice->total) }}</p>
                        @if ($invoice->notes)
                            <p><strong>Notes:</strong> {{ $invoice->notes }}</p>
                        @endif
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light"><h6 class="mb-0">Payments</h6></div>
                    <div class="card-body">
                        @forelse ($invoice->payments->where('is_deleted', 0) as $payment)
                            @php
                                $method = $payment->payment_method ?? '';
                                $isBank = $method === 'bank_transfer';
                                $proofUrl = $payment->payment_proof
                                    ? asset('public/uploads/subscription_payments/' . $payment->payment_proof)
                                    : null;
                                $isImage = $payment->payment_proof && preg_match('/\.(jpe?g|png|gif|webp)$/i', $payment->payment_proof);
                            @endphp
                            <div class="border rounded p-3 mb-3">
                                <div class="d-flex justify-content-between align-items-start mb-2">
                                    <div>
                                        <strong>{{ currency($payment->amount) }}</strong>
                                        <span class="badge bg-label-{{ $payment->status === 'pending' ? 'warning' : ($payment->status === 'confirmed' ? 'success' : 'danger') }} ms-1">
                                            {{ ucfirst($payment->status) }}
                                        </span>
                                    </div>
                                    <div>
                                        @if ($payment->status === 'pending')
                                            <button type="button" class="btn btn-sm btn-outline-success approve-payment" data-id="{{ $payment->subscription_payment_id }}">Confirm</button>
                                            <button type="button" class="btn btn-sm btn-outline-danger reject-payment" data-id="{{ $payment->subscription_payment_id }}">Reject</button>
                                        @elseif ($payment->status === 'confirmed')
                                            <span class="text-muted small">Confirmed — cannot reject</span>
                                        @else
                                            <span class="text-muted small">Rejected — cannot confirm</span>
                                        @endif
                                    </div>
                                </div>
                                <p class="mb-1"><strong>Method:</strong> {{ ucwords(str_replace('_', ' ', $method)) }}</p>
                                <p class="mb-1">
                                    <strong>{{ $isBank ? 'Bank Reference No:' : 'Reference:' }}</strong>
                                    {{ $payment->payment_reference ?: '—' }}
                                </p>
                                @if ($payment->notes)
                                    <p class="mb-1"><strong>Notes:</strong> {{ $payment->notes }}</p>
                                @endif
                                <div class="mt-2">
                                    <strong>{{ $isBank ? 'Bank Receipt:' : 'Receipt / Proof:' }}</strong>
                                    @if ($proofUrl)
                                        <div class="mt-2">
                                            <a href="{{ $proofUrl }}" target="_blank" class="btn btn-sm btn-outline-primary">
                                                <i class="fa fa-file"></i> View Receipt
                                            </a>
                                            @if ($isImage)
                                                <div class="mt-2">
                                                    <a href="{{ $proofUrl }}" target="_blank">
                                                        <img src="{{ $proofUrl }}" alt="Payment receipt" class="img-fluid border rounded" style="max-height:220px;">
                                                    </a>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <span class="text-muted">No receipt uploaded</span>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-center text-muted mb-0">No payments yet.</p>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $('#voidBtn').on('click', function() {
            Swal.fire({
                title: 'Void this invoice?',
                input: 'text',
                inputPlaceholder: 'Reason',
                showCancelButton: true,
                confirmButtonText: 'Void'
            }).then((result) => {
                if (result.isConfirmed) {
                    ajaxRequest({
                        url: "{{ route('subscription-invoices.void', $invoice->subscription_invoice_id) }}",
                        method: 'POST',
                        data: { reason: result.value }
                    }).then(() => location.reload()).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
                }
            });
        });

        $('#deleteBtn').on('click', function() {
            Swal.fire({
                title: 'Delete this invoice?',
                text: 'Related payments will also be deleted.',
                showCancelButton: true,
                confirmButtonText: 'Delete',
                confirmButtonColor: '#d33'
            }).then((result) => {
                if (!result.isConfirmed) return;
                ajaxRequest({
                    url: "{{ route('subscription-invoices.destroy', $invoice->subscription_invoice_id) }}",
                    method: 'DELETE'
                }).then(() => {
                    window.location = "{{ route('subscription-invoices.index') }}";
                }).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
            });
        });

        $(document).on('click', '.approve-payment', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Confirm this payment?',
                text: 'The business will be activated and an email with the invoice will be sent.',
                showCancelButton: true,
                confirmButtonText: 'Confirm Payment'
            }).then((result) => {
                if (!result.isConfirmed) return;
                ajaxRequest({
                    url: `${url_local}/admin/subscription-payments/${id}/approve`,
                    method: 'POST'
                }).then(() => location.reload()).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
            });
        });

        $(document).on('click', '.reject-payment', function() {
            var id = $(this).data('id');
            Swal.fire({
                title: 'Reject this payment?',
                input: 'text',
                inputPlaceholder: 'Reason',
                showCancelButton: true,
                confirmButtonText: 'Reject'
            }).then((result) => {
                if (result.isConfirmed) {
                    ajaxRequest({
                        url: `${url_local}/admin/subscription-payments/${id}/reject`,
                        method: 'POST',
                        data: { reason: result.value }
                    }).then(() => location.reload()).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
                }
            });
        });
    </script>
@endsection
