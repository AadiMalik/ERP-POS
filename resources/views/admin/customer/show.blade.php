@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">{{ $user->name ?? 'Customer' }}</h4>
        <a href="{{ url('admin/customer') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Customer Code</div>
                    <div class="fw-semibold">{{ $customer_profile->code ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Credit Limit</div>
                    <div class="fw-semibold">{{ currency($customer_profile->credit_limit ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Outstanding Balance</div>
                    <div class="fw-semibold">
                        {{ currency($ledger['balance'] ?? 0) }}
                        @if (!empty($ledger['type']))
                            <span class="badge bg-{{ $ledger['type'] == 'Dr' ? 'danger' : 'success' }}">{{ $ledger['type'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold text-capitalize">{{ $customer_profile->status ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="customerTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#overview">Overview</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#history">Order &amp; Payment History</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#timeline">Transaction Timeline</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="overview">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6>Contact</h6>
                            <table class="table table-sm">
                                <tr><th>Email</th><td>{{ $user->email ?? '-' }}</td></tr>
                                <tr><th>Phone</th><td>{{ $user->phone ?? '-' }}</td></tr>
                                <tr><th>Contact Person</th><td>{{ $customer_profile->contact_person ?? '-' }}</td></tr>
                                <tr><th>Company</th><td>{{ $customer_profile->company_name ?? '-' }}</td></tr>
                                <tr><th>Payment Terms</th><td>{{ $customer_profile->payment_terms ?? '-' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Billing Address</h6>
                            <p class="mb-3">
                                {{ $customer_profile->address ?? '-' }}<br>
                                {{ trim(($customer_profile->city ?? '') . ' ' . ($customer_profile->state ?? '') . ' ' . ($customer_profile->country ?? '')) ?: '-' }}
                            </p>
                            <h6>Shipping Address</h6>
                            <p>
                                {{ $customer_profile->shipping_address ?? '-' }}<br>
                                {{ trim(($customer_profile->shipping_city ?? '') . ' ' . ($customer_profile->shipping_state ?? '') . ' ' . ($customer_profile->shipping_country ?? '')) ?: '-' }}
                            </p>
                        </div>
                        @if (!empty($customer_profile->notes))
                        <div class="col-md-12">
                            <h6>Notes</h6>
                            <p>{{ $customer_profile->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="tab-pane fade" id="history">
                    <h6>Orders</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Order Date</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Due</th>
                                    <th>Credit?</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($history['orders'] ?? []) as $order)
                                <tr>
                                    <td>{{ localDate($order->order_date ?? $order->sale_date ?? null) }}</td>
                                    <td>{{ currency($order->total ?? 0) }}</td>
                                    <td>{{ currency($order->paid_amount ?? 0) }}</td>
                                    <td>{{ currency($order->due_amount ?? 0) }}</td>
                                    <td>{{ ($order->is_credit ?? false) ? 'Yes' : 'No' }}</td>
                                    <td class="text-capitalize">{{ $order->status ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">No orders found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6>Payments Received</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Payment No</th>
                                    <th>Applied To Order</th>
                                    <th>Amount</th>
                                    <th>Method</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($history['payments'] ?? []) as $payment)
                                <tr>
                                    <td>{{ localDate($payment->payment_date ?? null) }}</td>
                                    <td>{{ $payment->payment_no ?? '-' }}</td>
                                    <td>{{ $payment->order_id ?? 'On Account' }}</td>
                                    <td>{{ currency($payment->amount ?? 0) }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $payment->payment_method ?? '') }}</td>
                                    <td class="text-capitalize">{{ $payment->status ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">No payments found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="tab-pane fade" id="timeline">
                    <ul class="list-group">
                        @forelse ($timeline as $event)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <span class="badge bg-{{ $event['type'] == 'order' ? 'primary' : 'success' }} me-2 text-capitalize">{{ $event['type'] }}</span>
                                {{ $event['description'] }}
                                <div class="text-muted small">{{ localDate($event['date'] ?? null) }} &middot; Ref: {{ $event['reference'] }}</div>
                            </div>
                            <div class="fw-semibold">{{ currency($event['amount'] ?? 0) }}</div>
                        </li>
                        @empty
                        <li class="list-group-item text-center text-muted">No transactions yet.</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
