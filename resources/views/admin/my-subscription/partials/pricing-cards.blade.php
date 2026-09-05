@php
    $currentBillingCycle = $currentBillingCycle ?? ($subscription?->billing_cycle ?? 'monthly');
    $planIcons = [
        'Starter' => 'fa-store',
        'Growth' => 'fa-rocket',
        'Business' => 'fa-briefcase',
        'Enterprise' => 'fa-building',
        'Professional' => 'fa-briefcase',
        'Basic Plan' => 'fa-cube',
    ];
@endphp

<section class="erp-pricing">
    <div class="erp-pricing-intro">
        <h5>{{ __('Plans &amp; Pricing') }}</h5>
        <p class="text-muted">Compare packages and request an upgrade, downgrade, or renewal. Changes are reviewed by the platform operator before they take effect.</p>
    </div>

    @if (session('plan_change_blockers'))
        <div class="alert alert-danger">
            <div class="fw-semibold mb-2">{{ session('error') }}</div>
            <ul class="mb-0">
                @foreach (session('plan_change_blockers') as $blocker)
                    <li>
                        @if ((int) $blocker['allowed'] === 0)
                            {{ $blocker['label'] }}: {{ $blocker['used'] }} used, not included on this plan (remove {{ $blocker['excess'] }})
                        @else
                            {{ $blocker['label'] }}: {{ $blocker['used'] }} used, plan allows {{ $blocker['allowed'] }} (remove {{ $blocker['excess'] }})
                        @endif
                    </li>
                @endforeach
            </ul>
        </div>
    @endif

    @if ($open_request)
        <div class="alert alert-info">
            <i class="fa fa-clock me-1"></i>
            You already have a request for
            <strong>{{ $open_request->requestedPackage->name ?? '-' }}</strong>
            ({{ ucfirst($open_request->requested_billing_cycle ?? '') }})
            pending Super Admin approval. Another plan change cannot be submitted until that request is reviewed.
        </div>
    @endif

    <div class="d-flex justify-content-center mb-4">
        <div class="btn-group" role="group" aria-label="Billing period">
            <button type="button" class="btn btn-outline-primary erp-period-btn is-active" data-period="monthly">Monthly</button>
            <button type="button" class="btn btn-outline-primary erp-period-btn" data-period="yearly">Yearly</button>
        </div>
    </div>

    <div class="row erp-pricing-row justify-content-center">
        @foreach ($pricingPlans as $plan)
            @php
                $pkg = $plan['package'];
                $duration = $pkg->duration_type ?: 'monthly';
                $icon = $planIcons[$pkg->name] ?? 'fa-layer-group';
                $cardClass = 'erp-pricing-card';
                if ($plan['is_popular']) {
                    $cardClass .= ' is-popular';
                }
                if ($plan['is_current']) {
                    $cardClass .= ' is-current';
                }
                $listPrice = $pkg->listPrice();
                $effective = $pkg->effectivePrice();
                $discount = $pkg->discountPercent();
                $periodLabel = $duration === 'yearly' ? 'year' : 'month';
            @endphp
            <div class="col-12 col-md-6 col-xl-3 erp-plan-col" data-duration="{{ $duration }}" @if($duration !== $currentBillingCycle) style="display:none" @endif>
                <article class="{{ $cardClass }}">
                    @if ($plan['is_popular'])
                        <span class="erp-pricing-badge">Most Popular</span>
                    @elseif ($plan['is_current'])
                        <span class="erp-pricing-badge is-current">Current Plan</span>
                    @endif

                    <div class="erp-pricing-icon" aria-hidden="true">
                        <i class="fa {{ $icon }}"></i>
                    </div>
                    <h6 class="erp-pricing-name">{{ $pkg->name }}</h6>
                    <p class="erp-pricing-desc">{{ $pkg->description }}</p>

                    <div class="erp-pricing-price">
                        @if ($effective === null)
                            <strong>—</strong>
                        @else
                            <strong>{{ currency($effective) }}</strong>
                            <span>/ {{ $periodLabel }}</span>
                            @if ($discount > 0 && $listPrice !== null)
                                <div class="small text-muted">
                                    <span class="text-decoration-line-through">{{ currency($listPrice) }}</span>
                                    <span class="text-success ms-1">{{ rtrim(rtrim(number_format($discount, 2), '0'), '.') }}% off</span>
                                </div>
                            @endif
                        @endif
                    </div>

                    <ul class="erp-pricing-features">
                        @foreach ($plan['umbrellas'] as $umbrella)
                            <li>
                                @if ($umbrella['enabled'])
                                    <span class="erp-pricing-check is-on"><i class="fa fa-check"></i></span>
                                @else
                                    <span class="erp-pricing-check is-off"><i class="fa fa-times"></i></span>
                                @endif
                                <span>{{ $umbrella['label'] }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <ul class="erp-pricing-limits">
                        @foreach ($plan['limits'] as $limit)
                            <li>
                                <span>{{ $limit['label'] }}</span>
                                <span class="erp-pricing-limit-value">{{ $limit['value'] }}</span>
                            </li>
                        @endforeach
                    </ul>

                    <div class="erp-pricing-cta">
                        @if ($open_request)
                            <button type="button" class="btn btn-outline-secondary w-100" disabled>Request pending</button>
                        @elseif ($plan['is_current'])
                            <button type="button"
                                class="btn btn-outline-primary w-100 plan-change-btn"
                                data-package-id="{{ $pkg->package_id }}"
                                data-package-name="{{ $pkg->name }}"
                                data-duration="{{ $duration }}"
                                data-direction="current"
                                data-price="{{ $effective !== null ? currency($effective) : '—' }}">
                                Request Renewal
                            </button>
                        @elseif (!$plan['can_switch'])
                            <button type="button"
                                class="btn btn-outline-warning w-100 plan-blocked-btn"
                                data-package-name="{{ $pkg->name }}"
                                data-blockers='@json($plan['blockers'])'>
                                {{ $plan['direction'] === 'upgrade' ? 'Upgrade' : 'Downgrade' }}
                            </button>
                        @else
                            <button type="button"
                                class="btn {{ $plan['direction'] === 'upgrade' ? 'btn-primary' : 'btn-outline-primary' }} w-100 plan-change-btn"
                                data-package-id="{{ $pkg->package_id }}"
                                data-package-name="{{ $pkg->name }}"
                                data-duration="{{ $duration }}"
                                data-direction="{{ $plan['direction'] }}"
                                data-price="{{ $effective !== null ? currency($effective) : '—' }}">
                                {{ $plan['direction'] === 'upgrade' ? 'Upgrade' : 'Downgrade' }}
                            </button>
                        @endif
                    </div>
                </article>
            </div>
        @endforeach
    </div>
</section>

<div class="modal fade" id="planChangeModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" action="{{ route('my-subscription.renewal-requests.store') }}">
            @csrf
            <input type="hidden" name="requested_package_id" id="planChangePackageId">
            <input type="hidden" name="requested_billing_cycle" id="planChangeBillingCycle" value="{{ $currentBillingCycle }}">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="planChangeTitle">Request Plan Change</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-3" id="planChangeSummary"></p>
                    <p class="small text-muted mb-3" id="planChangeCycleNote"></p>
                    <div class="mb-0">
                        <label class="fw-semibold">Notes (optional)</label>
                        <textarea class="form-control" name="requested_notes" rows="2" placeholder="Anything the platform operator should know"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">{{ __('common.cancel') }}</button>
                    <button type="submit" class="btn btn-primary" id="planChangeSubmit">Submit Request</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="planBlockedModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cannot change plan yet</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="mb-2" id="planBlockedIntro"></p>
                <ul id="planBlockedList" class="mb-0"></ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>
