@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">{{ $supplier->name ?? 'Supplier' }}</h4>
        <a href="{{ url('admin/supplier') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> Back
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Supplier Code</div>
                    <div class="fw-semibold">{{ $supplier->code ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Credit Limit</div>
                    <div class="fw-semibold">{{ currency($supplier->credit_limit ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Outstanding Payable</div>
                    <div class="fw-semibold">
                        {{ currency($ledger['balance'] ?? 0) }}
                        @if (!empty($ledger['type']))
                            <span class="badge bg-{{ $ledger['type'] == 'Cr' ? 'danger' : 'success' }}">{{ $ledger['type'] }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">Status</div>
                    <div class="fw-semibold text-capitalize">{{ $supplier->status ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="supplierTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#overview">Overview</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#history">Purchase &amp; Payment History</a>
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
                                <tr><th>Email</th><td>{{ $supplier->email ?? '-' }}</td></tr>
                                <tr><th>Phone</th><td>{{ $supplier->phone ?? '-' }}</td></tr>
                                <tr><th>Contact Person</th><td>{{ $supplier->contact_person ?? '-' }}</td></tr>
                                <tr><th>Company</th><td>{{ $supplier->company_name ?? '-' }}</td></tr>
                                <tr><th>Payment Terms</th><td>{{ $supplier->payment_terms ?? '-' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>Address</h6>
                            <p class="mb-3">
                                {{ $supplier->address ?? '-' }}<br>
                                {{ trim(($supplier->city ?? '') . ' ' . ($supplier->state ?? '') . ' ' . ($supplier->country ?? '')) ?: '-' }}
                            </p>
                            @if (!empty($supplier->description))
                            <h6>Notes</h6>
                            <p>{{ $supplier->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="history">
                    <h6>Purchase Invoices</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Invoice Date</th>
                                    <th>Invoice No</th>
                                    <th>Amount</th>
                                    <th>Paid</th>
                                    <th>Outstanding</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($history['invoices'] ?? []) as $invoice)
                                <tr>
                                    <td>{{ localDate($invoice->invoice_date ?? null) }}</td>
                                    <td>{{ $invoice->invoice_number ?? '-' }}</td>
                                    <td>{{ currency($invoice->invoiced_amount ?? 0) }}</td>
                                    <td>{{ currency($invoice->paid_amount ?? 0) }}</td>
                                    <td>{{ currency($invoice->outstanding_amount ?? 0) }}</td>
                                    <td class="text-capitalize">{{ $invoice->status ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">No purchase invoices found.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6>Payments Made</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>Payment Date</th>
                                    <th>Payment No</th>
                                    <th>Applied To Purchase</th>
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
                                    <td>{{ $payment->purchase_id ?? 'On Account' }}</td>
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
                                <span class="badge bg-{{ $event['type'] == 'purchase' ? 'primary' : 'success' }} me-2 text-capitalize">{{ $event['type'] }}</span>
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
