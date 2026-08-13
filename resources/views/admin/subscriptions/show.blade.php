@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center py-3 mb-4 flex-wrap gap-2">
            <h4 class="fw-bold mb-0">{{ $business->name }} - Subscription Profile</h4>
            <div>
                <a href="{{ route('subscriptions.dashboard') }}" class="btn btn-outline-secondary">
                    <i class="fa fa-arrow-left"></i> Back
                </a>
                <a href="{{ route('subscriptions.renew.form', $business->business_id) }}" class="btn btn-primary">
                    <i class="fa fa-refresh"></i> Renew Subscription
                </a>
            </div>
        </div>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        <div class="row g-4 mb-4">
            <div class="col-md-8">
                <div class="card h-100">
                    <div class="card-header bg-light"><h6 class="mb-0">Business Information</h6></div>
                    <div class="card-body">
                        <div class="row g-2">
                            <div class="col-md-6"><strong>Code:</strong> {{ $business->code ?? '-' }}</div>
                            <div class="col-md-6"><strong>Email:</strong> {{ $business->email ?? '-' }}</div>
                            <div class="col-md-6"><strong>Owner:</strong> {{ $business->owner_name ?? '-' }}</div>
                            <div class="col-md-6"><strong>Owner Email:</strong> {{ $business->owner_email ?? '-' }}</div>
                            <div class="col-md-6"><strong>Phone:</strong> {{ $business->phone ?? '-' }}</div>
                            <div class="col-md-6"><strong>Business Status:</strong> {{ ucfirst($business->status) }}</div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card h-100 border-primary">
                    <div class="card-header bg-primary text-white"><h6 class="mb-0">Current Subscription</h6></div>
                    <div class="card-body">
                        @if ($subscription)
                            <p class="mb-1"><strong>Plan:</strong> {{ $subscription->package->name ?? '-' }}</p>
                            <p class="mb-1"><strong>Status:</strong> @include('admin.subscriptions.partials.status-badge', ['status' => $display_status])</p>
                            <p class="mb-1"><strong>Billing Cycle:</strong> {{ ucfirst($subscription->billing_cycle ?? '-') }}</p>
                            <p class="mb-1"><strong>Start:</strong> {{ localDate($subscription->start_at) }}</p>
                            <p class="mb-1"><strong>End:</strong> {{ localDate($subscription->end_at) }}</p>
                            <p class="mb-1"><strong>Remaining Days:</strong> {{ $subscription->remaining_days }}</p>
                            <p class="mb-1"><strong>Price:</strong> {{ currency($subscription->total) }}</p>
                            <hr>
                            <div class="d-flex gap-2 flex-wrap">
                                @if ($subscription->status !== 'suspended')
                                    <button type="button" class="btn btn-sm btn-outline-warning" id="suspendBtn">Suspend</button>
                                @else
                                    <button type="button" class="btn btn-sm btn-outline-success" id="reactivateBtn">Reactivate</button>
                                @endif
                                <button type="button" class="btn btn-sm btn-outline-danger" id="cancelBtn">Cancel</button>
                            </div>
                        @else
                            <p class="text-muted mb-0">No subscription found for this business.</p>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    <li class="nav-item"><button class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab-invoices">Invoices</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-renewals">Renewal Requests</button></li>
                    <li class="nav-item"><button class="nav-link" data-bs-toggle="tab" data-bs-target="#tab-history">Audit History</button></li>
                </ul>
            </div>
            <div class="card-body tab-content">
                <div class="tab-pane fade show active" id="tab-invoices">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>Invoice #</th><th>Package</th><th>Period</th><th>Total</th><th>Status</th><th>Payments</th><th></th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_no }}</td>
                                        <td>{{ $invoice->package->name ?? '-' }}</td>
                                        <td>{{ localDate($invoice->period_start) }} - {{ localDate($invoice->period_end) }}</td>
                                        <td>{{ currency($invoice->total) }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $invoice->status)) }}</td>
                                        <td>
                                            @foreach ($invoice->payments as $payment)
                                                <span class="badge bg-label-{{ $payment->status === 'confirmed' ? 'success' : ($payment->status === 'pending' ? 'warning' : 'danger') }}">
                                                    {{ currency($payment->amount) }} ({{ $payment->payment_method }})
                                                </span>
                                            @endforeach
                                        </td>
                                        <td>
                                            <a class="btn btn-icon btn-outline-secondary" target="_blank" href="{{ route('subscription-invoices.pdf', $invoice->subscription_invoice_id) }}"><i class="fa fa-file-pdf"></i></a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-muted">No invoices yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-renewals">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr><th>Requested Package</th><th>Cycle</th><th>Status</th><th>Requested Notes</th><th>Reviewer Notes</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($renewal_requests as $req)
                                    <tr>
                                        <td>{{ $req->requestedPackage->name ?? '-' }}</td>
                                        <td>{{ ucfirst($req->requested_billing_cycle) }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $req->status)) }}</td>
                                        <td>{{ $req->requested_notes ?? '-' }}</td>
                                        <td>{{ $req->reviewer_notes ?? '-' }}</td>
                                        <td>{{ localDate($req->date_created) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-muted">No renewal requests.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="tab-pane fade" id="tab-history">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr><th>Event</th><th>From</th><th>To</th><th>Notes</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                @forelse ($history as $h)
                                    <tr>
                                        <td>{{ ucwords(str_replace('_', ' ', $h->event_type)) }}</td>
                                        <td>{{ $h->from_status ?? '-' }}</td>
                                        <td>{{ $h->to_status ?? '-' }}</td>
                                        <td>{{ $h->notes ?? '-' }}</td>
                                        <td>{{ localDate($h->date_created) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="5" class="text-center text-muted">No history yet.</td></tr>
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
        $('#suspendBtn').on('click', function() {
            Swal.fire({
                title: 'Suspend this business?',
                input: 'text',
                inputPlaceholder: 'Reason (optional)',
                showCancelButton: true,
                confirmButtonText: 'Suspend'
            }).then((result) => {
                if (result.isConfirmed) {
                    ajaxRequest({
                        url: "{{ route('subscriptions.suspend', $business->business_id) }}",
                        method: 'POST',
                        data: {
                            reason: result.value
                        }
                    }).then(() => location.reload()).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
                }
            });
        });

        $('#reactivateBtn').on('click', function() {
            ajaxRequest({
                url: "{{ route('subscriptions.reactivate', $business->business_id) }}",
                method: 'POST'
            }).then(() => location.reload()).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
        });

        $('#cancelBtn').on('click', function() {
            Swal.fire({
                title: 'Cancel this subscription?',
                text: 'The business will lose access once the subscription ends. No data is deleted.',
                input: 'text',
                inputPlaceholder: 'Reason (optional)',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Cancel Subscription'
            }).then((result) => {
                if (result.isConfirmed) {
                    ajaxRequest({
                        url: "{{ route('subscriptions.cancel', $business->business_id) }}",
                        method: 'POST',
                        data: {
                            reason: result.value
                        }
                    }).then(() => location.reload()).catch((e) => errorMessage(e.Message ?? 'Something went wrong'));
                }
            });
        });
    </script>
@endsection
