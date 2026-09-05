@extends('layouts.app')

@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <div class="d-flex justify-content-between align-items-center py-3 mb-4">
        <h4 class="fw-bold mb-0">{{ $user->name ?? __('customers.singular') }}</h4>
        <a href="{{ url('admin/customer') }}" class="btn btn-outline-secondary">
            <i class="fa fa-arrow-left"></i> {{ __('common.back') }}
        </a>
    </div>

    <div class="row g-4 mb-4">
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('customers.customer_code') }}</div>
                    <div class="fw-semibold">{{ $customer_profile->code ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('customers.credit_limit') }}</div>
                    <div class="fw-semibold">{{ currency($customer_profile->credit_limit ?? 0) }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('customers.outstanding_balance') }}</div>
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
                    <div class="text-muted small">{{ __('common.status') }}</div>
                    <div class="fw-semibold text-capitalize">{{ $customer_profile->status ?? '-' }}</div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="text-muted small">{{ __('customers.store_credit_available') }}</div>
                    <div class="fw-semibold">
                        {{ currency($ledger['store_credit_balance'] ?? 0) }}
                        @if (($ledger['store_credit_balance'] ?? 0) > 0)
                            <span class="badge bg-info">{{ __('customers.redeemable_at_pos') }}</span>
                        @endif
                    </div>
                </div>
            </div>
        </div>
        @can('loyalty.view')
            @if ($customer_setting->loyalty_program ?? false)
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small">{{ __('customers.loyalty_points_available') }}</div>
                            <div class="fw-semibold">{{ decimal($customer_profile->loyalty_points ?? 0) }}</div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card h-100">
                        <div class="card-body">
                            <div class="text-muted small">{{ __('customers.loyalty_points_reserved') }}</div>
                            <div class="fw-semibold">{{ decimal($customer_profile->loyalty_points_reserved ?? 0) }}</div>
                            <div class="text-muted small">{{ __('customers.locked_to_unpaid_order') }}</div>
                        </div>
                    </div>
                </div>
            @endif
        @endcan
    </div>

    <div class="card">
        <div class="card-header">
            <ul class="nav nav-tabs card-header-tabs" id="customerTabs" role="tablist">
                <li class="nav-item">
                    <a class="nav-link active" data-bs-toggle="tab" href="#overview">{{ __('customers.tab_overview') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#history">{{ __('customers.tab_history') }}</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" data-bs-toggle="tab" href="#timeline">{{ __('customers.tab_timeline') }}</a>
                </li>
                @can('loyalty.history')
                    @if ($customer_setting->loyalty_program ?? false)
                        <li class="nav-item">
                            <a class="nav-link" data-bs-toggle="tab" href="#loyalty">{{ __('customers.tab_loyalty') }}</a>
                        </li>
                    @endif
                @endcan
            </ul>
        </div>
        <div class="card-body">
            <div class="tab-content">
                <div class="tab-pane fade show active" id="overview">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <h6>{{ __('customers.contact') }}</h6>
                            <table class="table table-sm">
                                <tr><th>{{ __('common.email') }}</th><td>{{ $user->email ?? '-' }}</td></tr>
                                <tr><th>{{ __('common.phone') }}</th><td>{{ $user->phone ?? '-' }}</td></tr>
                                <tr><th>{{ __('customers.contact_person') }}</th><td>{{ $customer_profile->contact_person ?? '-' }}</td></tr>
                                <tr><th>{{ __('customers.company') }}</th><td>{{ $customer_profile->company_name ?? '-' }}</td></tr>
                                <tr><th>{{ __('customers.payment_terms') }}</th><td>{{ $customer_profile->payment_terms ?? '-' }}</td></tr>
                            </table>
                        </div>
                        <div class="col-md-6">
                            <h6>{{ __('customers.billing_address') }}</h6>
                            <p class="mb-3">
                                {{ $customer_profile->address ?? '-' }}<br>
                                {{ trim(($customer_profile->city ?? '') . ' ' . ($customer_profile->state ?? '') . ' ' . ($customer_profile->country ?? '')) ?: '-' }}
                            </p>
                            <h6>{{ __('customers.shipping_address') }}</h6>
                            <p>
                                {{ $customer_profile->shipping_address ?? '-' }}<br>
                                {{ trim(($customer_profile->shipping_city ?? '') . ' ' . ($customer_profile->shipping_state ?? '') . ' ' . ($customer_profile->shipping_country ?? '')) ?: '-' }}
                            </p>
                        </div>
                        @if (!empty($customer_profile->notes))
                        <div class="col-md-12">
                            <h6>{{ __('common.notes') }}</h6>
                            <p>{{ $customer_profile->notes }}</p>
                        </div>
                        @endif
                    </div>
                </div>

                <div class="tab-pane fade" id="history">
                    <h6>{{ __('customers.orders') }}</h6>
                    <div class="table-responsive mb-4">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('customers.order_date') }}</th>
                                    <th>{{ __('common.total') }}</th>
                                    <th>{{ __('common.paid') }}</th>
                                    <th>{{ __('common.due') }}</th>
                                    <th>{{ __('customers.credit_question') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($history['orders'] ?? []) as $order)
                                <tr>
                                    <td>{{ localDate($order->order_date ?? $order->sale_date ?? null) }}</td>
                                    <td>{{ currency($order->total ?? 0) }}</td>
                                    <td>{{ currency($order->paid_amount ?? 0) }}</td>
                                    <td>{{ currency($order->due_amount ?? 0) }}</td>
                                    <td>{{ ($order->is_credit ?? false) ? __('common.yes') : __('common.no') }}</td>
                                    <td class="text-capitalize">{{ $order->status ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('customers.no_orders_found') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>

                    <h6>{{ __('customers.payments_received') }}</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('customers.payment_date') }}</th>
                                    <th>{{ __('customers.payment_no') }}</th>
                                    <th>{{ __('customers.applied_to_order') }}</th>
                                    <th>{{ __('common.amount') }}</th>
                                    <th>{{ __('customers.method') }}</th>
                                    <th>{{ __('common.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($history['payments'] ?? []) as $payment)
                                <tr>
                                    <td>{{ localDate($payment->payment_date ?? null) }}</td>
                                    <td>{{ $payment->payment_no ?? '-' }}</td>
                                    <td>{{ $payment->order_id ?? __('customers.on_account') }}</td>
                                    <td>{{ currency($payment->amount ?? 0) }}</td>
                                    <td class="text-capitalize">{{ str_replace('_', ' ', $payment->payment_method ?? '') }}</td>
                                    <td class="text-capitalize">{{ $payment->status ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="6" class="text-center text-muted">{{ __('customers.no_payments_found') }}</td></tr>
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
                                <div class="text-muted small">{{ localDate($event['date'] ?? null) }} &middot; {{ __('customers.ref') }}: {{ $event['reference'] }}</div>
                            </div>
                            <div class="fw-semibold">{{ currency($event['amount'] ?? 0) }}</div>
                        </li>
                        @empty
                        <li class="list-group-item text-center text-muted">{{ __('customers.no_transactions_yet') }}</li>
                        @endforelse
                    </ul>
                </div>

                @can('loyalty.history')
                @if ($customer_setting->loyalty_program ?? false)
                <div class="tab-pane fade" id="loyalty">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered">
                            <thead>
                                <tr>
                                    <th>{{ __('common.date') }}</th>
                                    <th>{{ __('common.type') }}</th>
                                    <th class="text-end">{{ __('customers.points') }}</th>
                                    <th class="text-end">{{ __('common.value') }}</th>
                                    <th class="text-end">{{ __('customers.available_balance_after') }}</th>
                                    <th>{{ __('common.reference') }}</th>
                                    <th>{{ __('common.description') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($loyalty_transactions ?? []) as $tx)
                                <tr>
                                    <td>{{ localDateTime($tx->date_created ?? null) }}</td>
                                    <td class="text-capitalize">{{ $tx->transaction_type }}</td>
                                    <td class="text-end">{{ decimal($tx->points) }}</td>
                                    <td class="text-end">{{ $tx->monetary_value !== null ? currency($tx->monetary_value) : '-' }}</td>
                                    <td class="text-end">{{ decimal($tx->available_balance_after) }}</td>
                                    <td>
                                        @if ($tx->reference_type == 'order' && !empty($tx->reference_id))
                                            <a href="{{ route('order.show', $tx->reference_id) }}">{{ __('customers.order') }}</a>
                                        @else
                                            {{ $tx->reference_type ? ucfirst($tx->reference_type) : '-' }}
                                        @endif
                                    </td>
                                    <td>{{ $tx->description ?? '-' }}</td>
                                </tr>
                                @empty
                                <tr><td colspan="7" class="text-center text-muted">{{ __('customers.no_loyalty_transactions') }}</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
                @endif
                @endcan
            </div>
        </div>
    </div>
</div>
@endsection
