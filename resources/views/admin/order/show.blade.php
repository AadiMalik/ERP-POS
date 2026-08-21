@extends('layouts.app')
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <div class="d-flex justify-content-between align-items-center py-3 mb-4">
            <h4 class="fw-bold mb-0">
                Order - {{ $order->daily_order_id ?? '' }}
            </h4>
            <div>
                <a href="{{ route('order.print', $order->order_id) }}" target="_blank"
                    class="btn btn-outline-secondary">
                    <i class="fa fa-print"></i>
                    Print / Reprint
                </a>
                <a href="{{ route('order.thermal-print', $order->order_id) }}" target="_blank"
                    class="btn btn-outline-info">
                    <i class="fa fa-receipt"></i>
                    Thermal Print
                </a>
                <a href="{{ route('pos-screen') }}?reorder_from={{ $order->order_id }}" target="_blank"
                    class="btn btn-outline-success">
                    <i class="fa fa-rotate-right"></i>
                    Reorder
                </a>
                @if (in_array($order->status, ['posted', 'returned']))
                    <button type="button" class="btn btn-outline-dark view-jv-btn"
                        data-source-type="{{ \App\Enums\JournalSourceTypes::POS_SALE }}"
                        data-source-id="{{ $order->order_id }}">
                        <i class="fa fa-book"></i>
                        View JV
                    </button>
                    <button type="button" class="btn btn-outline-dark stock-consumption-btn"
                        data-reference-type="{{ \App\Enums\ReferenceType::SALE }}"
                        data-reference-id="{{ $order->order_id }}">
                        <i class="fa fa-cubes"></i>
                        Stock Consumption
                    </button>
                @endif
                <a href="{{ url('admin/order') }}" class="btn btn-outline-primary">
                    <i class="fa fa-arrow-left"></i>
                    Back
                </a>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Order Information</h6>
            </div>
            <div class="card-body">
                @php
                    $due = max(($order->total ?? 0) - ($order->paid_amount ?? 0), 0);
                    if ($due <= 0) {
                        $payment_status = \App\Enums\Status::PAID;
                    } elseif (($order->paid_amount ?? 0) > 0) {
                        $payment_status = \App\Enums\Status::PARTIALLY_PAID;
                    } else {
                        $payment_status = \App\Enums\Status::UNPAID;
                    }
                    $payment_status_badges = [
                        \App\Enums\Status::PAID => 'bg-label-success',
                        \App\Enums\Status::PARTIALLY_PAID => 'bg-label-warning',
                        \App\Enums\Status::UNPAID => 'bg-label-danger',
                    ];
                @endphp
                <div class="row g-3">
                    <div class="col-md-3">
                        <strong>Daily Order ID:</strong><br>
                        {{ $order->daily_order_id ?? '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Order Date:</strong><br>
                        {{ $order->order_date ? localDateTime($order->order_date) : '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Sale Date:</strong><br>
                        {{ $order->sale_date ? localDate($order->sale_date) : '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Status:</strong><br>
                        <span class="badge bg-label-primary">{{ ucfirst($order->status ?? '-') }}</span>
                    </div>
                    <div class="col-md-3">
                        <strong>Payment Status:</strong><br>
                        <span class="badge {{ $payment_status_badges[$payment_status] ?? 'bg-label-secondary' }}">
                            {{ ucwords(str_replace('_', ' ', $payment_status)) }}
                        </span>
                    </div>
                    <div class="col-md-3">
                        <strong>Paid Amount:</strong><br>
                        {{ currency($order->paid_amount ?? 0) }}
                    </div>
                    <div class="col-md-3">
                        <strong>Due Amount:</strong><br>
                        {{ currency($due) }}
                    </div>
                    @if (!empty($order->voucher))
                        <div class="col-md-3">
                            <strong>Voucher:</strong><br>
                            {{ $order->voucher->code ?? '-' }} (-{{ currency($order->voucher_discount_amount ?? 0) }})
                        </div>
                    @endif
                    <div class="col-md-3">
                        <strong>Business:</strong><br>
                        {{ $order->business->name ?? '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Branch:</strong><br>
                        {{ $order->branch->name ?? '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Warehouse:</strong><br>
                        {{ $order->warehouse->name ?? '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Register:</strong><br>
                        {{ $order->register->name ?? '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Order Taker:</strong><br>
                        {{ $order->cashier->name ?? '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Customer:</strong><br>
                        {{ $order->user->name ?? '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Order Type:</strong><br>
                        {{ $order->orderType->name ?? '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Order Source:</strong><br>
                        {{ $order->orderSource->name ?? '-' }}
                    </div>
                    <div class="col-md-3">
                        <strong>Sale Type:</strong><br>
                        {!! $sale_type_badge !!}
                    </div>
                    @if (!empty($order->delivery_address))
                        <div class="col-md-6">
                            <strong>Delivery Address:</strong><br>
                            {{ $order->delivery_address }}
                        </div>
                    @endif
                    @if (!empty($order->fbr_invoice_number))
                        <div class="col-md-3">
                            <strong>FBR Invoice No.:</strong><br>
                            {{ $order->fbr_invoice_number }}
                        </div>
                    @endif
                    @if (!empty($order->pra_invoice_number))
                        <div class="col-md-3">
                            <strong>PRA Invoice No.:</strong><br>
                            {{ $order->pra_invoice_number }}
                        </div>
                    @endif
                    @if (!empty($order->notes))
                        <div class="col-md-12">
                            <strong>Notes:</strong><br>
                            {{ $order->notes }}
                        </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Line Items</h6>
            </div>
            <div class="table-responsive p-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Product</th>
                            <th>Variation</th>
                            <th>Unit</th>
                            <th class="text-end">Qty</th>
                            <th class="text-end">Unit Price</th>
                            <th>Sale Type</th>
                            <th class="text-end">Discount</th>
                            <th class="text-end">Final Unit Price</th>
                            <th class="text-end">Tax</th>
                            <th class="text-end">Total</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->details as $index => $detail)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $detail->product->name ?? '-' }}</td>
                                <td>{{ $detail->productVariation->name ?? '-' }}</td>
                                <td>{{ $detail->unit->name ?? '-' }}</td>
                                <td class="text-end">{{ decimal($detail->quantity) }}</td>
                                <td class="text-end">{{ currency($detail->unit_price) }}</td>
                                <td>{{ $detail->sale_type_label ?? '-' }}</td>
                                <td class="text-end">
                                    {{ currency($detail->discount_amount) }}
                                    @if ($detail->discount > 0)
                                        <small class="text-muted d-block">({{ decimal($detail->discount) }}%)</small>
                                    @endif
                                </td>
                                <td class="text-end">{{ currency($detail->final_unit_price) }}</td>
                                <td class="text-end">{{ currency($detail->tax_amount) }}</td>
                                <td class="text-end">{{ currency($detail->total) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="11" class="text-center">No items found</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="card-body border-top">
                <div class="row justify-content-end">
                    <div class="col-md-4">
                        @php
                            // orders.discount_amount = item discounts + order-level Discount
                            // + voucher discount (see OrderService::saveLinesAndComputeTotals())
                            // - the voucher's own amount is already broken out above, so it's
                            // excluded here to avoid showing it twice.
                            $item_discount_total = $order->details->sum('discount_amount');
                            $order_level_discount = max(($order->discount_amount ?? 0) - $item_discount_total - ($order->voucher_discount_amount ?? 0), 0);
                        @endphp
                        <table class="table table-borderless mb-0">
                            <tr>
                                <td>Subtotal</td>
                                <td class="text-end">{{ currency($order->subtotal) }}</td>
                            </tr>
                            <tr>
                                <td>Item Discounts</td>
                                <td class="text-end">{{ currency($item_discount_total) }}</td>
                            </tr>
                            <tr>
                                <td>Order Discount</td>
                                <td class="text-end">{{ currency($order_level_discount) }}</td>
                            </tr>
                            <tr>
                                <td>Tax</td>
                                <td class="text-end">{{ currency($order->tax_amount) }}</td>
                            </tr>
                            <tr class="fw-bold">
                                <td>Total</td>
                                <td class="text-end">{{ currency($order->total) }}</td>
                            </tr>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Payments</h6>
            </div>
            <div class="table-responsive p-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Payment Method</th>
                            <th>Reference No.</th>
                            <th class="text-end">Amount</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($order->payments as $index => $payment)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $payment->paymentMethod->name ?? '-' }}</td>
                                <td>{{ $payment->reference_no ?? '-' }}</td>
                                <td class="text-end">{{ currency($payment->amount) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center">No payments recorded</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @php
            $customer_payments = $order->customerPayments->sortBy('payment_date')->values();
            $running_paid = 0;
        @endphp
        <div class="card mb-4">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h6 class="mb-0">Payment History</h6>
                @can('customer-payment.create')
                    @if ($due > 0)
                        <a href="{{ route('customer-payment.create') }}?order_id={{ $order->order_id }}" class="btn btn-sm btn-primary">
                            <i class="fa fa-plus"></i>
                            Add Payment
                        </a>
                    @endif
                @endcan
            </div>
            <div class="table-responsive p-4">
                <table class="table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Method</th>
                            <th>Reference</th>
                            <th>Status</th>
                            <th class="text-end">Amount</th>
                            <th class="text-end">Paid to Date</th>
                            <th class="text-end">Remaining Balance</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($customer_payments as $index => $payment)
                            @php
                                $running_paid += $payment->status === 'posted' ? (float) $payment->net_amount : 0;
                                $remaining = max(($order->total ?? 0) - $running_paid, 0);
                            @endphp
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $payment->payment_date ? localDate($payment->payment_date) : '-' }}</td>
                                <td>{{ ucwords(str_replace('_', ' ', $payment->payment_method)) }}</td>
                                <td>{{ $payment->reference_no ?? '-' }}</td>
                                <td>
                                    <span class="badge {{ $payment->status === 'posted' ? 'bg-label-success' : ($payment->status === 'cancelled' ? 'bg-label-danger' : 'bg-label-warning') }}">
                                        {{ ucfirst($payment->status) }}
                                    </span>
                                </td>
                                <td class="text-end">{{ currency($payment->net_amount) }}</td>
                                <td class="text-end">{{ currency($running_paid) }}</td>
                                <td class="text-end">{{ currency($remaining) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="text-center">No partial/credit payments recorded yet</td>
                            </tr>
                        @endforelse
                    </tbody>
                    @if ($customer_payments->isNotEmpty())
                        <tfoot>
                            <tr class="fw-bold">
                                <td colspan="5" class="text-end">Total Paid to Date</td>
                                <td class="text-end">{{ currency($running_paid) }}</td>
                                <td colspan="2"></td>
                            </tr>
                        </tfoot>
                    @endif
                </table>
            </div>
        </div>

        <div class="card mb-4">
            <div class="card-header">
                <h6 class="mb-0">Status History</h6>
            </div>
            <div class="card-body">
                <ul class="list-group">
                    @forelse ($order->statusHistory as $history)
                        <li class="list-group-item d-flex justify-content-between align-items-center">
                            <div>
                                <strong>{{ ucfirst($history->from_status ?? 'New') }}</strong>
                                &rarr;
                                <strong>{{ ucfirst($history->to_status ?? '-') }}</strong>
                                @if (!empty($history->reason))
                                    <br><small class="text-muted">{{ $history->reason }}</small>
                                @endif
                            </div>
                            <div class="text-end">
                                <small class="text-muted">
                                    {{ $history->changedby->name ?? '-' }}<br>
                                    {{ $history->date_created ? localDateTime($history->date_created) : '-' }}
                                </small>
                            </div>
                        </li>
                    @empty
                        <li class="list-group-item text-center">No status history found</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
@endsection
