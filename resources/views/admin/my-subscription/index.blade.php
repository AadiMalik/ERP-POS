@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">My Subscription</h4>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @include('admin.subscriptions.partials.subscription-card', ['subscription' => $subscription, 'display_status' => $display_status])

        <div class="card mt-4">
            <div class="card-header bg-light"><h6 class="mb-0">Modules &amp; Usage</h6></div>
            <div class="card-body">
                @foreach ($moduleUsage as $category => $modules)
                    <h6 class="mt-2">{{ $category }}</h6>
                    <div class="row mb-3">
                        @foreach ($modules as $key => $row)
                            <div class="col-md-4 mb-2">
                                <span class="badge {{ $row['enabled'] ? 'bg-label-success' : 'bg-label-secondary' }}">
                                    {{ $row['enabled'] ? 'Included' : 'Not Included' }}
                                </span>
                                {{ $row['label'] }}
                                @if ($row['type'] === 'limited' && $row['enabled'])
                                    <div class="small text-muted">
                                        @if ($row['unlimited'])
                                            Unlimited
                                        @else
                                            {{ $row['used'] }}/{{ $row['limit'] }} used
                                            <div class="progress" style="height:4px;">
                                                <div class="progress-bar {{ $row['percent'] >= 80 ? 'bg-danger' : 'bg-primary' }}"
                                                    style="width: {{ $row['percent'] }}%"></div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </div>

        <div class="card mt-4">
            <div class="card-header bg-light"><h6 class="mb-0">Compare Packages</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table mb-0 align-middle">
                        <thead>
                            <tr>
                                <th></th>
                                <th>Package</th>
                                <th>Price</th>
                                <th>Billing</th>
                                <th>Modules Included</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($packages as $item)
                                @php
                                    $isCurrent = ($subscription->package_id ?? '') == $item->package_id;
                                    $enabledCount = $item->modules->where('is_enabled', true)->count();
                                    $totalCount = $item->modules->count();
                                @endphp
                                <tr class="{{ $isCurrent ? 'table-primary' : '' }}">
                                    <td>
                                        @if ($isCurrent)
                                            <span class="badge bg-primary">Current</span>
                                        @endif
                                    </td>
                                    <td>{{ $item->name }}</td>
                                    <td>{{ currency($item->price) }}</td>
                                    <td>{{ ucfirst($item->duration_type) }}</td>
                                    <td>{{ $enabledCount }}/{{ $totalCount }}</td>
                                    <td>
                                        <button type="button" class="btn btn-sm btn-outline-secondary" data-bs-toggle="collapse"
                                            data-bs-target="#pkg-details-{{ $item->package_id }}">Details</button>
                                    </td>
                                </tr>
                                <tr class="collapse" id="pkg-details-{{ $item->package_id }}">
                                    <td colspan="6" class="bg-light">
                                        @foreach (\App\Support\Subscription\SubscriptionModuleRegistry::grouped() as $category => $categoryModules)
                                            @php
                                                $moduleState = $item->modules->keyBy('module_key');
                                            @endphp
                                            <strong>{{ $category }}:</strong>
                                            @foreach ($categoryModules as $key => $meta)
                                                @php
                                                    $row = $moduleState->get($key);
                                                    $enabled = $row ? $row->is_enabled : ($meta['default_enabled'] ?? true);
                                                @endphp
                                                <span class="badge {{ $enabled ? 'bg-label-success' : 'bg-label-secondary' }} me-1 mb-1">
                                                    {{ $meta['label'] }}
                                                    @if ($enabled && $meta['type'] === 'limited')
                                                        ({{ $row && $row->is_unlimited ? 'Unlimited' : ($row->limit_value ?? ($meta['default_limit'] ?? 5)) }})
                                                    @endif
                                                </span>
                                            @endforeach
                                            <br>
                                        @endforeach
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light"><h6 class="mb-0">Request a Renewal / Plan Change</h6></div>
                    <div class="card-body">
                        @if ($open_request)
                            <div class="alert alert-info mb-0">
                                <i class="fa fa-clock"></i>
                                You already have a renewal request for
                                <strong>{{ $open_request->requestedPackage->name ?? '-' }}</strong>
                                pending Super Admin approval.
                            </div>
                        @else
                            <form action="{{ route('my-subscription.renewal-requests.store') }}" method="POST">
                                @csrf
                                <div class="mb-3">
                                    <label class="fw-semibold">Package</label>
                                    <select class="form-select" name="requested_package_id" required>
                                        @foreach ($packages as $item)
                                            <option value="{{ $item->package_id }}" {{ ($subscription->package_id ?? '') == $item->package_id ? 'selected' : '' }}>
                                                {{ $item->name }} ({{ currency($item->price) }} / {{ $item->duration_type }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-semibold">Billing Cycle</label>
                                    <select class="form-select" name="requested_billing_cycle" required>
                                        <option value="monthly">Monthly</option>
                                        <option value="yearly">Yearly</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label class="fw-semibold">Notes (optional)</label>
                                    <textarea class="form-control" name="requested_notes" rows="2"></textarea>
                                </div>
                                <button class="btn btn-primary">Submit Renewal Request</button>
                            </form>
                        @endif
                    </div>
                </div>

                <div class="card mt-4">
                    <div class="card-header bg-light"><h6 class="mb-0">Renewal Request History</h6></div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead><tr><th>Package</th><th>Status</th><th>Date</th></tr></thead>
                            <tbody>
                                @forelse ($renewal_requests as $req)
                                    <tr>
                                        <td>{{ $req->requestedPackage->name ?? '-' }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $req->status)) }}</td>
                                        <td>{{ localDate($req->date_created) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="text-center text-muted">No requests yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card">
                    <div class="card-header bg-light"><h6 class="mb-0">Invoices &amp; Payments</h6></div>
                    <div class="card-body p-0">
                        <table class="table mb-0">
                            <thead><tr><th>Invoice #</th><th>Total</th><th>Status</th><th></th></tr></thead>
                            <tbody>
                                @forelse ($invoices as $invoice)
                                    <tr>
                                        <td>{{ $invoice->invoice_no }}</td>
                                        <td>{{ currency($invoice->total) }}</td>
                                        <td>{{ ucwords(str_replace('_', ' ', $invoice->status)) }}</td>
                                        <td>
                                            <a class="btn btn-sm btn-outline-secondary" target="_blank" href="{{ route('my-subscription.invoice-pdf', $invoice->subscription_invoice_id) }}">PDF</a>
                                            @if (in_array($invoice->status, ['unpaid', 'partially_paid']))
                                                <button type="button" class="btn btn-sm btn-outline-primary submit-payment-btn"
                                                    data-id="{{ $invoice->subscription_invoice_id }}"
                                                    data-total="{{ $invoice->total }}">Submit Payment</button>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="text-center text-muted">No invoices yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- Submit Payment Modal -->
        <div class="modal fade" id="paymentModal" tabindex="-1">
            <div class="modal-dialog">
                <form id="paymentForm" method="POST" enctype="multipart/form-data">
                    @csrf
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Submit Payment</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                        </div>
                        <div class="modal-body">
                            <div class="mb-3">
                                <label class="fw-semibold">Amount</label>
                                <input type="number" step="0.01" min="0.01" class="form-control" name="amount" id="paymentAmount" required>
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold">Payment Method</label>
                                <select class="form-select" name="payment_method" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="online">Online</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold">Reference Number</label>
                                <input type="text" class="form-control" name="payment_reference">
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold">Payment Proof</label>
                                <input type="file" class="form-control" name="payment_proof" accept="image/*,.pdf">
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold">Notes</label>
                                <textarea class="form-control" name="notes" rows="2"></textarea>
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                            <button type="submit" class="btn btn-primary">Submit</button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection
@section('js')
    <script>
        $(document).on('click', '.submit-payment-btn', function() {
            var id = $(this).data('id');
            var total = $(this).data('total');
            $('#paymentAmount').val(total);
            $('#paymentForm').attr('action', `${url_local}/admin/my-subscription/invoices/${id}/payments`);
            $('#paymentModal').modal('show');
        });
    </script>
@endsection
