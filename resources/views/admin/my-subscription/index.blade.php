@extends('layouts.app')
@section('css')
    <link rel="stylesheet" href="{{ asset('public/assets/css/admin/subscription-pricing.css') }}" />
@endsection
@section('content')
    <div class="container-xxl flex-grow-1 container-p-y">
        <h4 class="fw-bold py-3 mb-4">My Subscription</h4>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if (session('error') && !session('plan_change_blockers'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif

        @include('admin.subscriptions.partials.subscription-card', ['subscription' => $subscription, 'display_status' => $display_status])

        @include('admin.my-subscription.partials.pricing-cards')

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

        <div class="row g-4 mt-1">
            <div class="col-md-6">
                <div class="card">
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
                                <select class="form-select" name="payment_method" id="paymentMethod" required>
                                    <option value="cash">Cash</option>
                                    <option value="bank_transfer">Bank Transfer</option>
                                    <option value="cheque">Cheque</option>
                                    <option value="online">Online</option>
                                </select>
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold" id="paymentReferenceLabel">Bank Reference No</label>
                                <input type="text" class="form-control" name="payment_reference" placeholder="e.g. bank transaction / reference number">
                            </div>
                            <div class="mb-3">
                                <label class="fw-semibold" id="paymentProofLabel">Bank Receipt</label>
                                <input type="file" class="form-control" name="payment_proof" accept="image/*,.pdf">
                                <small class="text-muted">Upload the bank transfer receipt (image or PDF).</small>
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
            $('#paymentMethod').val('bank_transfer').trigger('change');
            $('#paymentModal').modal('show');
        });

        $(document).on('change', '#paymentMethod', function () {
            var isBank = $(this).val() === 'bank_transfer';
            $('#paymentReferenceLabel').text(isBank ? 'Bank Reference No' : 'Reference Number');
            $('#paymentProofLabel').text(isBank ? 'Bank Receipt' : 'Payment Proof');
        });

        $(document).on('click', '.plan-change-btn', function() {
            var name = $(this).data('package-name');
            var direction = $(this).data('direction');
            var price = $(this).data('price');
            var duration = $(this).data('duration') || 'monthly';
            var titles = {
                current: 'Request Renewal',
                upgrade: 'Upgrade Plan',
                downgrade: 'Downgrade Plan'
            };
            var summaries = {
                current: 'Submit a renewal request for <strong>' + name + '</strong> (' + price + ' / ' + duration + '). Super Admin will review it before the new period starts.',
                upgrade: 'Request an upgrade to <strong>' + name + '</strong> (' + price + ' / ' + duration + '). Super Admin will review the request before the plan changes.',
                downgrade: 'Request a downgrade to <strong>' + name + '</strong> (' + price + ' / ' + duration + '). Super Admin will review the request before the plan changes.'
            };
            $('#planChangePackageId').val($(this).data('package-id'));
            $('#planChangeBillingCycle').val(duration);
            $('#planChangeCycleNote').text('Billing period comes from the selected package (' + duration + ').');
            $('#planChangeTitle').text(titles[direction] || 'Request Plan Change');
            $('#planChangeSummary').html(summaries[direction] || '');
            $('#planChangeSubmit').text(direction === 'current' ? 'Submit Renewal Request' : 'Submit Request');
            $('#planChangeModal').modal('show');
        });

        $(document).on('click', '.plan-blocked-btn', function() {
            var name = $(this).data('package-name');
            var blockers = $(this).data('blockers') || [];
            $('#planBlockedIntro').text('You cannot switch to ' + name + ' yet. Reduce these first, then you can change plans:');
            var $list = $('#planBlockedList').empty();
            blockers.forEach(function(blocker) {
                var text = Number(blocker.allowed) === 0
                    ? blocker.label + ': ' + blocker.used + ' used, not included on this plan (remove ' + blocker.excess + ')'
                    : blocker.label + ': ' + blocker.used + ' used, plan allows ' + blocker.allowed + ' (remove ' + blocker.excess + ')';
                $list.append($('<li>').text(text));
            });
            $('#planBlockedModal').modal('show');
        });

        (function () {
            var initialPeriod = @json(($currentBillingCycle ?? 'monthly') === 'yearly' ? 'yearly' : 'monthly');
            function setPeriod(period) {
                $('.erp-period-btn').each(function () {
                    var active = $(this).data('period') === period;
                    $(this).toggleClass('is-active btn-primary', active);
                    $(this).toggleClass('btn-outline-primary', !active);
                });
                $('.erp-plan-col').each(function () {
                    $(this).toggle($(this).data('duration') === period);
                });
            }
            $(document).on('click', '.erp-period-btn', function () {
                setPeriod($(this).data('period'));
            });
            setPeriod(initialPeriod);
        })();
    </script>
@endsection
