@php
    $wt = $website_theme_setting;
@endphp

<h5 class="mb-3">{{ __('settings.website_title') }}</h5>
<p class="text-muted">
    {!! __('settings.website_description', [
        'business' => '<a href="' . e(url('admin/business')) . '">' . e(__('settings.website_description_business_link')) . '</a>',
        'cms' => '<a href="' . e(url('admin/social-media')) . '">' . e(__('settings.website_description_cms_link')) . '</a>',
    ]) !!}
</p>

<form id="websiteSettingsForm" onsubmit="event.preventDefault(); saveWebsiteSettings(this);" enctype="multipart/form-data">
    @csrf

    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('settings.website_general') }}</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.website_favicon') }}</label>
                <input type="file" class="form-control" name="favicon" accept="image/*">
                <small class="text-muted d-block mt-1">
                    {{ __('settings.website_favicon_help') }}
                </small>
                @if (!empty($wt->favicon))
                    <img src="{{ asset('public/uploads/website/' . $wt->favicon) }}" alt="{{ __('settings.website_favicon_alt') }}" style="height:32px;width:32px;object-fit:contain;" class="mt-2">
                @else
                    <div class="d-flex align-items-center gap-2 mt-2">
                        <img src="{{ asset('public/assets/img/favicon/favicon-32.png') }}" alt="{{ __('settings.website_favicon_default_alt') }}" style="height:32px;width:32px;object-fit:contain;">
                        <small class="text-muted">{{ __('settings.website_favicon_using_default') }}</small>
                    </div>
                @endif
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.website_business_hours') }}</label>
                <input type="text" class="form-control" name="business_hours" value="{{ $wt->business_hours }}"
                    placeholder="{{ __('settings.website_business_hours_placeholder') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.website_whatsapp_number') }}</label>
                <input type="text" class="form-control" name="whatsapp_number" value="{{ $wt->whatsapp_number }}"
                    placeholder="+1 555 010 2024">
                <small class="text-muted">{{ __('settings.website_whatsapp_help') }}</small>
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('settings.website_seo') }}</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-12">
                <label class="form-label">{{ __('settings.website_seo_title') }}</label>
                <input type="text" class="form-control" name="seo_title" value="{{ $wt->seo_title }}">
            </div>
            <div class="col-md-12">
                <label class="form-label">{{ __('settings.website_seo_description') }}</label>
                <textarea class="form-control" name="seo_description" rows="3">{{ $wt->seo_description }}</textarea>
            </div>
            <div class="col-md-12">
                <label class="form-label">{{ __('settings.website_seo_keywords') }}</label>
                <input type="text" class="form-control" name="seo_keywords" value="{{ $wt->seo_keywords }}"
                    placeholder="{{ __('settings.website_seo_keywords_placeholder') }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.website_og_image') }}</label>
                <input type="file" class="form-control" name="og_image" accept="image/*">
                @if (!empty($wt->og_image))
                    <img src="{{ asset('public/uploads/website/' . $wt->og_image) }}" alt="{{ __('settings.website_og_image') }}" style="height:60px;margin-top:8px;" class="d-block">
                @endif
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('settings.website_delivery') }}</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <div class="form-check form-switch">
                    <input class="form-check-input" type="checkbox" role="switch" id="free_delivery_enabled"
                        name="free_delivery_enabled" value="1" {{ $wt->free_delivery_enabled ? 'checked' : '' }}>
                    <label class="form-check-label" for="free_delivery_enabled">{{ __('settings.website_free_delivery') }}</label>
                </div>
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.website_free_delivery_min') }}</label>
                <input type="number" step="0.01" min="0" class="form-control" name="free_delivery_min_amount"
                    value="{{ $wt->free_delivery_min_amount }}">
            </div>
        </div>
    </div>

    <div class="card mb-3">
        <div class="card-header"><strong>{{ __('settings.website_bank_transfer') }}</strong></div>
        <div class="card-body row g-3">
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.website_bank_name') }}</label>
                <input type="text" class="form-control" name="bank_name" value="{{ $wt->bank_name }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.website_bank_account_title') }}</label>
                <input type="text" class="form-control" name="bank_account_title" value="{{ $wt->bank_account_title }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.website_bank_account_number') }}</label>
                <input type="text" class="form-control" name="bank_account_number" value="{{ $wt->bank_account_number }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.website_bank_iban') }}</label>
                <input type="text" class="form-control" name="bank_iban" value="{{ $wt->bank_iban }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.website_bank_branch') }}</label>
                <input type="text" class="form-control" name="bank_branch" value="{{ $wt->bank_branch }}">
            </div>
            <div class="col-md-6">
                <label class="form-label">{{ __('settings.website_bank_swift') }}</label>
                <input type="text" class="form-control" name="bank_swift_code" value="{{ $wt->bank_swift_code }}">
            </div>
            <div class="col-md-12">
                <label class="form-label">{{ __('settings.website_bank_instructions') }}</label>
                <textarea class="form-control" name="bank_instructions" rows="3"
                    placeholder="{{ __('settings.website_bank_instructions_placeholder') }}">{{ $wt->bank_instructions }}</textarea>
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary">{{ __('settings.website_save') }}</button>
</form>
