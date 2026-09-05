@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">{{ $supplier->name ?? __('suppliers.singular') }}</h4>
        <a href="{{ url('admin/supplier') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('suppliers.supplier_code') }}</div>
                    <div class="fw-semibold">{{ $supplier->code ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('suppliers.credit_limit') }}</div>
                    <div class="fw-semibold">{{ currency($supplier->credit_limit ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('suppliers.outstanding_payable') }}</div>
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
                    <div class="text-muted small">{{ __('common.status') }}</div>
                    <div class="fw-semibold text-capitalize">{{ $supplier->status ?? '-' }}</div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="supplierTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#overview">{{ __('suppliers.tab_overview') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#history">{{ __('suppliers.tab_history') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#timeline">{{ __('suppliers.tab_timeline') }}</a>
                </li>
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="overview">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6>{{ __('suppliers.contact') }}</h6>
                            <table class="table table-sm">
                                <tr><th>{{ __('common.email') }}</th><td>{{ $supplier->email ?? '-' }}</td></tr>
                                <tr><th>{{ __('common.phone') }}</th><td>{{ $supplier->phone ?? '-' }}</td></tr>
                                <tr><th>{{ __('suppliers.contact_person') }}</th><td>{{ $supplier->contact_person ?? '-' }}</td></tr>
                                <tr><th>{{ __('suppliers.company') }}</th><td>{{ $supplier->company_name ?? '-' }}</td></tr>
                                <tr><th>{{ __('suppliers.payment_terms') }}</th><td>{{ $supplier->payment_terms ?? '-' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>{{ __('common.address') }}</h6>
                            <p class="mb-3">
                                {{ $supplier->address ?? '-' }}<br>
                                {{ trim(($supplier->city ?? '') . ' ' . ($supplier->state ?? '') . ' ' . ($supplier->country ?? '')) ?: '-' }}
                            </p>
                            @if (!empty($supplier->description))
                            <h6>{{ __('common.notes') }}</h6>
                            <p>{{ $supplier->description }}</p>
                            @endif
                        </div>
                    </div>
                </div>

                <div class="tab-pane fade" id="history">
                    <h6>{{ __('suppliers.purchase_invoices') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('suppliers.invoice_date') }}</th>
                                    <th>{{ __('suppliers.invoice_no') }}</th>
                                    <th>{{ __('common.amount') }}</th>
                                    <th>{{ __('common.paid') }}</th>
                                    <th>{{ __('suppliers.outstanding') }}</th>
                                    <th>{{ __('common.status') }}</th>
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
                                <tr><td colspan="6" class="text-center text-muted">{{ __('suppliers.no_purchase_invoices') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6>{{ __('suppliers.payments_made') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('suppliers.payment_date') }}</th>
                                    <th>{{ __('suppliers.payment_no') }}</th>
                                    <th>{{ __('suppliers.applied_to_purchase') }}</th>
                                    <th>{{ __('common.amount') }}</th>
                                    <th>{{ __('suppliers.method') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($history['payments'] ?? []) as $payment)
                                <tr>
                                    <td>{{ localDate($payment->payment_date ?? null) }}</td>
                                    <td>{{ $payment->payment_no ?? '-' }}</td>
                                    <td>{{ $payment->purchase_id ?? __('suppliers.on_account') }}</td>
                                    <td>{{ currency($payment->amount ?? 0) }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $payment->payment_method ?? '') }}</td>
                                    <td class="text-capitalize">{{ $payment->status ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('suppliers.no_payments_found') }}</td></tr>
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
                                <div class="text-muted small">{{ localDate($event['date'] ?? null) }} &middot; {{ __('suppliers.ref') }}: {{ $event['reference'] }}</div>
                            </div>
                            <div class="fw-semibold">{{ currency($event['amount'] ?? 0) }}</div>
                        </li>
                        @empty
                        <li class="list-group-item text-center text-muted">{{ __('suppliers.no_transactions_yet') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
