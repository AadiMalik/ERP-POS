@php
    $tabs = ['orders' => ['label' => 'Recent Orders']];
    if (isset($purchases)) {
        $tabs['purchases'] = ['label' => 'Recent Purchases'];
    }
    if (isset($finance)) {
        $tabs['payments'] = ['label' => 'Recent Payments'];
    }
    $firstTab = array_key_first($tabs);

    $statusDotClass = function ($status) {
        $status = strtolower((string) $status);
        return match (true) {
            in_array($status, ['completed', 'paid', 'active', 'approved']) => 'erp-status-dot--success',
            in_array($status, ['processing', 'pending', 'partial']) => 'erp-status-dot--warning',
            in_array($status, ['cancelled', 'due', 'rejected', 'failed']) => 'erp-status-dot--danger',
            default => 'erp-status-dot--secondary',
        };
    };
@endphp
<div class="card mb-4">
    <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
        <ul class="nav nav-pills" role="tablist">
            @foreach ($tabs as $key => $tab)
                <li class="nav-item">
                    <button class="nav-link @if ($key === $firstTab) active @endif" data-bs-toggle="tab" data-bs-target="#recent-{{ $key }}" type="button">{{ $tab['label'] }}</button>
                </li>
            @endforeach
        </ul>
        <a href="{{ route('order.history') }}" class="btn btn-sm btn-outline-primary">View All Sales</a>
    </div>
    <div class="tab-content p-0">
        <div class="tab-pane fade @if ($firstTab === 'orders') show active @endif" id="recent-orders">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Order #</th>
                            <th>Date</th>
                            <th>Branch</th>
                            <th>Customer</th>
                            <th class="text-end">Total</th>
                            <th>Status</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($sales['recent_orders'] as $order)
                            <tr>
                                <td><span class="erp-activity-avatar me-2"><i class="fa fa-receipt"></i></span>{{ $order->daily_order_id ?? substr($order->order_id, 0, 8) }}</td>
                                <td>{{ $order->order_date ? localDate($order->order_date) : '-' }}</td>
                                <td>{{ $order->branch->name ?? '-' }}</td>
                                <td>{{ $order->user->name ?? 'Walk-in' }}</td>
                                <td class="text-end">{{ currency($order->total) }}</td>
                                <td><span class="erp-status-dot {{ $statusDotClass($order->status) }}">{{ ucfirst($order->status) }}</span></td>
                                <td><a href="{{ route('order.show', $order->order_id) }}" class="btn btn-icon btn-outline-primary btn-sm"><i class="fa fa-eye"></i></a></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted">No recent orders.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        @if (isset($purchases))
            <div class="tab-pane fade" id="recent-purchases">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Purchase #</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th class="text-end">Total</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($purchases['recent_purchases'] as $purchase)
                                <tr>
                                    <td><span class="erp-activity-avatar me-2"><i class="fa fa-truck-loading"></i></span>{{ $purchase->purchase_no }}</td>
                                    <td>{{ $purchase->purchase_date ? localDate($purchase->purchase_date) : '-' }}</td>
                                    <td>{{ $purchase->supplier->name ?? '-' }}</td>
                                    <td class="text-end">{{ currency($purchase->total) }}</td>
                                    <td><span class="erp-status-dot {{ $statusDotClass($purchase->status) }}">{{ ucfirst($purchase->status) }}</span></td>
                                    <td><a href="{{ route('purchase.show', $purchase->purchase_id) }}" class="btn btn-icon btn-outline-primary btn-sm"><i class="fa fa-eye"></i></a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No recent purchases.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        @if (isset($finance))
            <div class="tab-pane fade" id="recent-payments">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr>
                                <th>Payment #</th>
                                <th>Date</th>
                                <th>Supplier</th>
                                <th class="text-end">Amount</th>
                                <th>Status</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($finance['recent_payments'] as $payment)
                                <tr>
                                    <td><span class="erp-activity-avatar me-2"><i class="fa fa-money-check-alt"></i></span>{{ $payment->payment_no }}</td>
                                    <td>{{ $payment->payment_date ? localDate($payment->payment_date) : '-' }}</td>
                                    <td>{{ $payment->supplier->name ?? '-' }}</td>
                                    <td class="text-end">{{ currency($payment->net_amount ?? $payment->amount) }}</td>
                                    <td><span class="erp-status-dot {{ $statusDotClass($payment->status) }}">{{ ucfirst($payment->status) }}</span></td>
                                    <td><a href="{{ route('supplier-payment.edit', $payment->supplier_payment_id) }}" class="btn btn-icon btn-outline-primary btn-sm"><i class="fa fa-eye"></i></a></td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">No recent payments.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    </div>
</div>
