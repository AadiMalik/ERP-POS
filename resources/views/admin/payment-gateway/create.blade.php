@php
    use App\Enums\RoleNames;
    $is_edit = isset($gateway);
@endphp
@extends('layouts.app')
@section('content')
<div class="container-xxl flex-grow-1 container-p-y">
    <h4 class="fw-bold py-3 mb-4">{{ $is_edit ? 'Update' : 'New' }} Payment Gateway</h4>

    <div class="card">
        <div class="card-header bg-white border-bottom">
            <h5 class="mb-0">{{ __('Gateway Configuration') }}</h5>
        </div>

        <div class="card-body border-bottom pb-3" id="webhookUrlBox" style="display:none;">
            <label class="fw-semibold d-block">Webhook / Callback URL</label>
            <div class="input-group">
                <input type="text" class="form-control" id="webhook_url" readonly>
                <button type="button" class="btn btn-outline-secondary" id="copyWebhookUrl">
                    <i class="fa fa-copy"></i> Copy
                </button>
            </div>
            <div class="form-text">
                Based on the Business and Provider chosen below - available before you save, so you
                can create the webhook on the provider's dashboard first (they'll hand you back a
                signing secret), then paste that secret into the field below. This URL never
                changes for this business/provider once you're done.
            </div>
        </div>
        <div id="webhookUrlPlaceholder" class="card-body border-bottom pb-3 text-muted small">
            Select a Business and Provider to see your Webhook / Callback URL.
        </div>

        <form action="{{ url('admin/payment-gateway') }}" method="POST">
            @csrf
            <input type="hidden" name="payment_gateway_id" value="{{ $gateway->payment_gateway_id ?? '' }}">
            <div class="card-body">
                <div class="row g-4">
                    @if (RoleNames::SUPERADMIN == getRoleName())
                    <div class="col-md-6">
                        <label class="fw-semibold">Business <span class="text-danger">*</span></label>
                        <select class="form-select" name="business_id" id="business_id" required>
                            <option value="">-- Select Business --</option>
                            @foreach ($business as $item)
                            <option value="{{ $item->business_id }}" {{ old('business_id', $gateway->business_id ?? '') == $item->business_id ? 'selected' : '' }}>
                                {{ $item->code }} {{ $item->name }}
                            </option>
                            @endforeach
                        </select>
                    </div>
                    @endif

                    <div class="col-md-6">
                        <label class="fw-semibold">Provider <span class="text-danger">*</span></label>
                        <select class="form-select" name="provider_code" id="provider_code" {{ $is_edit ? 'disabled' : 'required' }}>
                            <option value="">-- Select Provider --</option>
                            @foreach ($providers as $code => $meta)
                            <option value="{{ $code }}" {{ ($gateway->provider_code ?? '') == $code ? 'selected' : '' }}>{{ $meta['label'] }}</option>
                            @endforeach
                        </select>
                        @if ($is_edit)
                        <input type="hidden" name="provider_code" value="{{ $gateway->provider_code }}">
                        <div class="form-text">The provider cannot be changed after creation - delete and re-add instead.</div>
                        @endif
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold">Display Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="display_name" value="{{ old('display_name', $gateway->display_name ?? '') }}" required>
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold">Country</label>
                        <input type="text" class="form-control" name="country" value="{{ old('country', $gateway->country ?? '') }}" placeholder="e.g. PK">
                    </div>

                    <div class="col-md-12">
                        <label class="fw-semibold">Description</label>
                        <textarea class="form-control" name="description" rows="2">{{ old('description', $gateway->description ?? '') }}</textarea>
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold">Environment / Mode <span class="text-danger">*</span></label>
                        <select class="form-select" name="active_mode" id="active_mode" required>
                            <option value="sandbox" {{ old('active_mode', $gateway->active_mode ?? 'sandbox') == 'sandbox' ? 'selected' : '' }}>Sandbox / Test</option>
                            <option value="live" {{ old('active_mode', $gateway->active_mode ?? '') == 'live' ? 'selected' : '' }}>Live / Production</option>
                        </select>
                        <div class="form-text">Sandbox and Live each keep their own credentials below - switching never loses the other.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold">Sort Order</label>
                        <input type="number" class="form-control" name="sort_order" value="{{ old('sort_order', $gateway->sort_order ?? 0) }}">
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold d-block">Platform Availability</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="website_enabled" id="website_enabled" value="1" {{ old('website_enabled', $gateway->website_enabled ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="website_enabled">Website</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="checkbox" name="mobile_enabled" id="mobile_enabled" value="1" {{ old('mobile_enabled', $gateway->mobile_enabled ?? true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="mobile_enabled">Mobile App</label>
                        </div>
                        <div class="form-text">Payment Gateways are never available in POS.</div>
                    </div>

                    <div class="col-md-6">
                        <label class="fw-semibold">Supported Currencies</label>
                        <input type="text" class="form-control" name="supported_currencies" id="supported_currencies"
                            value="{{ old('supported_currencies', implode(',', $gateway->supported_currencies ?? [])) }}" placeholder="Comma separated, e.g. PKR,USD">
                    </div>

                    <div class="col-md-12">
                        <label class="fw-semibold d-block">Supported Payment Methods</label>
                        <div id="supported_payment_methods_wrapper"></div>
                    </div>
                </div>

                <hr class="my-4">
                <ul class="nav nav-tabs mb-3" role="tablist">
                    <li class="nav-item">
                        <button type="button" class="nav-link active" data-bs-toggle="tab" data-bs-target="#tab_sandbox">Sandbox / Test Credentials</button>
                    </li>
                    <li class="nav-item">
                        <button type="button" class="nav-link" data-bs-toggle="tab" data-bs-target="#tab_live">Live / Production Credentials</button>
                    </li>
                </ul>
                <div class="tab-content">
                    <div class="tab-pane fade show active" id="tab_sandbox">
                        <div class="row g-3" id="config_sandbox_fields"></div>
                    </div>
                    <div class="tab-pane fade" id="tab_live">
                        <div class="row g-3" id="config_live_fields"></div>
                    </div>
                </div>
                <div class="form-text mt-2">Secret fields already configured show as <strong>configured</strong> - leave blank to keep the existing value, or enter a new value to replace it. Secrets are never sent back to the browser.</div>
            </div>
            <div class="card-footer border-top">
                <div class="d-flex justify-content-end gap-2">
                    <button type="button" class="btn btn-outline-secondary" onclick="window.history.back()">{{ __('common.cancel') }}</button>
                    <button class="btn btn-primary px-4">Save Gateway</button>
                </div>
            </div>
        </form>
    </div>
</div>
@endsection

@section('js')
@if ($errors->any())
<script>
    errorMessage("{{ $errors->first() }}");
</script>
@endif
@if(session('error'))
<script>
    errorMessage("{{ session('error') }}");
</script>
@endif
<script>
    const PGW_PROVIDERS = @json($providers);
    // Only used as a fallback when there's no #business_id select on the page
    // (a Business Admin's own business, implicit rather than chosen).
    const PGW_CURRENT_BUSINESS_ID = @json(auth()->user()->business_id ?? '');
    const PGW_SELECTED_METHODS = @json($gateway->supported_payment_methods ?? []);
    const PGW_MASKED = {
        sandbox: @json($masked_sandbox ?? []),
        live: @json($masked_live ?? []),
    };
    const PGW_IS_EDIT = {{ $is_edit ? 'true' : 'false' }};

    function pgwFieldHtml(mode, field) {
        let masked = PGW_MASKED[mode] && PGW_MASKED[mode][field.key];
        let inputType = field.secret ? 'password' : 'text';
        let placeholder = field.secret && masked ? '•••• configured - leave blank to keep' : '';
        return `
            <div class="col-md-6">
                <label class="fw-semibold">${field.label}${field.required ? ' <span class="text-danger">*</span>' : ''}</label>
                <input type="${inputType}" class="form-control" name="config_${mode}[${field.key}]" placeholder="${placeholder}" autocomplete="off">
            </div>
        `;
    }

    function pgwRenderProviderFields() {
        let code = $('#provider_code').val();
        let meta = PGW_PROVIDERS[code];
        if (!meta) {
            $('#config_sandbox_fields').html('');
            $('#config_live_fields').html('');
            $('#supported_payment_methods_wrapper').html('');
            return;
        }

        let sandboxHtml = (meta.config_fields.sandbox || []).map(f => pgwFieldHtml('sandbox', f)).join('');
        let liveHtml = (meta.config_fields.live || []).map(f => pgwFieldHtml('live', f)).join('');
        $('#config_sandbox_fields').html(sandboxHtml);
        $('#config_live_fields').html(liveHtml);

        if (!PGW_IS_EDIT && !$('#supported_currencies').val()) {
            $('#supported_currencies').val((meta.currencies || []).join(','));
        }

        let methodsHtml = (meta.payment_methods || []).map(m => {
            let checked = PGW_SELECTED_METHODS.includes(m) || (!PGW_IS_EDIT);
            return `
                <div class="form-check form-check-inline">
                    <input class="form-check-input" type="checkbox" name="supported_payment_methods[]" value="${m}" ${checked ? 'checked' : ''}>
                    <label class="form-check-label">${m}</label>
                </div>
            `;
        }).join('');
        $('#supported_payment_methods_wrapper').html(methodsHtml);
    }

    // The webhook URL only depends on the chosen Business + Provider - both
    // known before the gateway is ever saved - so it can be shown right away
    // instead of only after saving (see App\Services\Concrete\Api\
    // PaymentService::handleWebhook()'s doc comment for why it's resolved
    // this way rather than by payment_gateway_id).
    function pgwUpdateWebhookBox() {
        let businessId = $('#business_id').length ? $('#business_id').val() : PGW_CURRENT_BUSINESS_ID;
        let providerCode = $('#provider_code').val();
        let meta = PGW_PROVIDERS[providerCode];

        if (businessId && providerCode && meta && meta.supports_webhook) {
            $('#webhook_url').val(url_local + '/api/webhooks/payment-gateways/' + businessId + '/' + providerCode);
            $('#webhookUrlBox').show();
            $('#webhookUrlPlaceholder').hide();
        } else {
            $('#webhookUrlBox').hide();
            $('#webhookUrlPlaceholder').show();
        }
    }

    $(document).ready(function() {
        @if (RoleNames::SUPERADMIN == getRoleName())
        $('#business_id').select2();
        $('#business_id').on('change', pgwUpdateWebhookBox);
        @endif
        $('#provider_code').on('change', function() {
            pgwRenderProviderFields();
            pgwUpdateWebhookBox();
        });
        pgwRenderProviderFields();
        pgwUpdateWebhookBox();
    });

    $('#copyWebhookUrl').click(function() {
        let input = document.getElementById('webhook_url');
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(function() {
            successMessage('Webhook URL copied.');
        });
    });
</script>
@endsection
