@php
    $tax_current_branch_name = $tax_branch_id
        ? optional($tax_branches->firstWhere('branch_id', $tax_branch_id))->name
        : null;
    $tax_type = optional($branch_tax_setting)->tax_type ?? 'exclusive';
@endphp

<div class="row g-3 mb-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('settings.tax_configuring') }}</label>
        <select class="form-select select2" id="taxScopeSelect">
            @foreach ($tax_branches as $branch)
                <option value="{{ $branch->branch_id }}" {{ $tax_branch_id === $branch->branch_id ? 'selected' : '' }}>
                    {{ $branch->name }}
                </option>
            @endforeach
        </select>
        @if ($tax_current_branch_name)
            <small class="text-muted">{{ __('settings.tax_editing_branch', ['name' => $tax_current_branch_name]) }}</small>
        @endif
    </div>
</div>

<form id="branchTaxSettingForm">
    @csrf
    <input type="hidden" name="branch_id" value="{{ $tax_branch_id }}">
    <h4>{{ __('settings.tax_title') }}</h4>
    <p class="text-muted">{{ __('settings.tax_description') }}</p>
    <hr>

    <div class="row">
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.overall_tax_rate') }}<span class="text-danger">*</span></label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="overall_tax_rate"
                value="{{ optional($branch_tax_setting)->overall_tax_rate ?? 0 }}">
            <small class="text-muted">{{ __('settings.overall_tax_rate_help') }}</small>
        </div>
        <div class="col-md-6 mb-3">
            <label>{{ __('settings.card_tax_rate') }}<span class="text-danger">*</span></label>
            <input type="text" onkeypress="return isNumberKey(event)" class="form-control" name="card_tax_rate"
                value="{{ optional($branch_tax_setting)->card_tax_rate ?? 0 }}">
            <small class="text-muted">{{ __('settings.card_tax_rate_help') }}</small>
        </div>
        <div class="col-md-12 mb-3">
            <label class="d-block">{{ __('settings.tax_type') }}<span class="text-danger">*</span></label>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="tax_type" id="tax_type_exclusive"
                    value="exclusive" {{ $tax_type === 'exclusive' ? 'checked' : '' }}>
                <label class="form-check-label" for="tax_type_exclusive">{{ __('settings.tax_type_exclusive') }}</label>
            </div>
            <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="tax_type" id="tax_type_inclusive"
                    value="inclusive" {{ $tax_type === 'inclusive' ? 'checked' : '' }}>
                <label class="form-check-label" for="tax_type_inclusive">{{ __('settings.tax_type_inclusive') }}</label>
            </div>
            <small class="text-muted d-block">{{ __('settings.tax_type_help') }}</small>
        </div>
        <div class="col-md-12">
            <hr>
            <div class="text-end">
                <button type="button" class="btn btn-primary"
                    onclick="saveSetting('#branchTaxSettingForm','{{ route('branch_tax.update') }}')">
                    {{ __('common.save_changes') }}
                </button>
            </div>
        </div>
    </div>
</form>

@once
    <script>
        $(document).ready(function() {
            // Restore the Tax tab after a scope-switch reload.
            if (window.location.search.indexOf('tax_branch_id') !== -1) {
                var tabEl = document.querySelector('[data-bs-target="#tax"]');
                if (tabEl && window.bootstrap) {
                    new bootstrap.Tab(tabEl).show();
                }
            }
        });

        $(document).on('change', '#taxScopeSelect', function() {
            var branchId = $(this).val();
            var url = new URL(window.location.href);
            if (branchId) {
                url.searchParams.set('tax_branch_id', branchId);
            } else {
                url.searchParams.delete('tax_branch_id');
            }
            window.location.href = url.toString();
        });
    </script>
@endonce
