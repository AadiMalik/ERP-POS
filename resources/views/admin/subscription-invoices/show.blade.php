@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center py-3 mb-4">
            <h4 class="fw-bold mb-0">Invoice {{ $invoice->invoice_no }}</h4>
            <div>
                <a href="{{ route('subscription-invoices.pdf', $invoice->subscription_invoice_id) }}" target="_blank" class="btn btn-outline-secondary">
                    <i class="fa fa-file-pdf"></i> PDF
                </a>
                @if ($invoice->status !== 'void')
                    <button type="button" id="voidBtn" class="btn btn-outline-danger">Void Invoice</button>
                @endif
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light"><h6 class="mb-0">Invoice Details</h6></div>
                    <div class="card-body">
                        <p><strong>Business:</strong> {{ $invoice->business->name ?? '-' }}</p>
                        <p><strong>Package:</strong> {{ $invoice->package->name ?? '-' }}</p>
                        <p><strong>Billing Cycle:</strong> {{ ucfirst($invoice->billing_cycle) }}</p>
                        <p><strong>Period:</strong> {{ localDate($invoice->period_start) }} - {{ localDate($invoice->period_end) }}</p>
                        <p><strong>Status:</strong> {{ ucwords(str_replace('_', ' ', $invoice->status)) }}</p>
                        <p><strong>Subtotal:</strong> {{ currency($invoice->subtotal) }}</p>
                        <p><strong>Discount:</strong> {{ currency($invoice->discount_amount) }}</p>
                        <p><strong>Tax:</strong> {{ currency($invoice->tax_amount) }}</p>
                        <p><strong>Total:</strong> {{ currency($invoice->total) }}</p>
                    </div>
                </div>
            </div>
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light"><h6 class="mb-0">Payments</h6></div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead>
                                <tr><th>Amount</th><th>Method</th><th>Status</th><th></th></tr>
                            </thead>
                            <tbody>
                                @forelse ($invoice->payments as $payment)
                                    <tr>
                                        <td>{{ currency($payment->amount) }}</td>
                                        <td>{{ ucfirst(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                        <td>{{ ucfirst($payment->status) }}</td>
                                        <td>
                                            @if ($payment->status === 'pending')
                                                <button type="button" class="btn btn-sm btn-outline-success approve-payment" data-id="{{ $payment->subscription_payment_id }}">Approve</button>
                                                <button type="button" class="btn btn-sm btn-outline-danger reject-payment" data-id="{{ $payment->subscription_payment_id }}">Reject</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No payments yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
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

        $(document).on('click', '.approve-payment', function() {
            var id = $(this).data('id');
            ajaxRequest({
                url: `${url_local}/admin/subscription-payments/${id}/approve`,
                method: 'POST'
            }).then(() => location.reload()).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
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
